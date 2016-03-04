<?php
namespace FluidTYPO3\Fluidcontent\Service;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Configuration\ConfigurationManager;
use FluidTYPO3\Flux\Core;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\View\TemplatePaths;
use FluidTYPO3\Flux\View\ViewContext;
use FluidTYPO3\Flux\Service\FluxService;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\Utility\MiscellaneousUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\StringFrontend;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Configuration Service
 *
 * Provides methods to read various configuration related
 * to Fluid Content Elements.
 */
class ConfigurationService extends FluxService implements SingletonInterface {

	/**
	 * Default Width for icon
	 */
	const ICON_WIDTH = '24m';

	/**
	 * Default Height for icon
	 */
	const ICON_HEIGHT = '24m';

	/**
	 * @var array
	 */
	protected $extConf;

	/**
	 * @var CacheManager
	 */
	protected $manager;

	/**
	 * @var PageRepository
	 */
	protected $pageRepository;

	/**
	 * @var WorkspacesAwareRecordService
	 */
	protected $recordService;

	/**
	 * @var string
	 */
	protected $defaultIcon;

	/**
	 * Storage for the current page UID to restore after this Service abuses
	 * ConfigurationManager to override the page UID used when resolving
	 * configurations for all TypoScript templates defined in the site.
	 *
	 * @var integer
	 */
	protected $pageUidBackup;

	/**
	 * @param CacheManager $manager
	 * @return void
	 */
	public function injectCacheManager(CacheManager $manager) {
		$this->manager = $manager;
	}

	/**
	 * @param WorkspacesAwareRecordService $recordService
	 * @return void
	 */
	public function injectRecordService(WorkspacesAwareRecordService $recordService) {
		$this->recordService = $recordService;
	}

	/**
	 * @param PageRepository $pageRepository
	 * @return void
	 */
	public function injectPageRepository(PageRepository $pageRepository) {
		$this->pageRepository = $pageRepository;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->defaultIcon = '../' . ExtensionManagementUtility::siteRelPath('fluidcontent') . 'Resources/Public/Icons/Plugin.svg';

		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluidcontent']);
		$this->extConf['iconWidth'] = $this->extConf['iconWidth'] ? : self::ICON_WIDTH;
		$this->extConf['iconHeight'] = $this->extConf['iconHeight'] ? : self::ICON_HEIGHT;
	}

	/**
	 * @return string
	 */
	public function getDefaultIcon() {
		return $this->defaultIcon;
	}

	/**
	 * @return boolean
	 */
	protected function isBackendMode() {
		return ('BE' === TYPO3_MODE);
	}

	/**
	 * Get definitions of paths for FCEs defined in TypoScript
	 *
	 * @param string $extensionName
	 * @return array
	 * @api
	 */
	public function getContentConfiguration($extensionName = NULL) {
		if (NULL !== $extensionName) {
			return $this->getViewConfigurationForExtensionName($extensionName);
		}
		$registeredExtensionKeys = (array) Core::getRegisteredProviderExtensionKeys('Content');
		$configuration = array();
		foreach ($registeredExtensionKeys as $registeredExtensionKey) {
			$configuration[$registeredExtensionKey] = $this->getContentConfiguration($registeredExtensionKey);
		}
		return $configuration;
	}

	/**
	 * @return string
	 */
	public function getPageTsConfig() {
		$pageTsConfig = '';
		if (TRUE === $this->configurationManager instanceof ConfigurationManager) {
			$templates = $this->getTypoScriptTemplatesInRootline();
		} else {
			$templates = $this->getAllRootTypoScriptTemplates();
		}
		foreach ($templates as $template) {
			$pageUid = (integer) $template['pid'];
			$pageTsConfig .= $this->renderPageTypoScriptForPageUid($pageUid);
		}
		return $pageTsConfig;
	}

	/**
	 * @return void
	 */
	public function writeCachedConfigurationIfMissing() {
		/** @var StringFrontend $cache */
		$cache = $this->manager->getCache('fluidcontent');
		$hasCache = $cache->has('pageTsConfig');
		if (FALSE === $hasCache) {
			$cache->set('pageTsConfig', $this->getPageTsConfig(), array(), 86400);
		}
	}

	/**
	 * @param $pageUid
	 * @return string
	 */
	protected function renderPageTypoScriptForPageUid($pageUid) {
		$this->backupPageUidForConfigurationManager();
		$this->overrideCurrentPageUidForConfigurationManager($pageUid);
		$pageTsConfig = '';
		try {
			$collection = $this->getContentConfiguration();
			$wizardTabs = $this->buildAllWizardTabGroups($collection);
			$collectionPageTsConfig = $this->buildAllWizardTabsPageTsConfig($wizardTabs);
			$pageTsConfig .= '[PIDinRootline = ' . strval($pageUid) . ']' . LF;
			$pageTsConfig .= $collectionPageTsConfig . LF;
			$pageTsConfig .= '[GLOBAL]' . LF;
			$this->message('Built content setup for page ' . $pageUid, GeneralUtility::SYSLOG_SEVERITY_INFO, 'Fluidcontent');
		} catch (\RuntimeException $error) {
			$this->debug($error);
		}
		$this->restorePageUidForConfigurationManager();
		return $pageTsConfig;
	}

	/**
	 * @param integer $newPageUid
	 * @return void
	 */
	protected function overrideCurrentPageUidForConfigurationManager($newPageUid) {
		if (TRUE === $this->configurationManager instanceof ConfigurationManager) {
			$this->configurationManager->setCurrentPageUid($newPageUid);
		}
	}

	/**
	 * @return void
	 */
	protected function backupPageUidForConfigurationManager() {
		if (TRUE === $this->configurationManager instanceof ConfigurationManager) {
			$this->pageUidBackup = $this->configurationManager->getCurrentPageId();
		}
	}

	/**
	 * @return void
	 */
	protected function restorePageUidForConfigurationManager() {
		if (TRUE === $this->configurationManager instanceof ConfigurationManager) {
			$this->configurationManager->setCurrentPageUid($this->pageUidBackup);
		}
	}

	/**
	 * @return array
	 */
	protected function getAllRootTypoScriptTemplates() {
		$condition = 'deleted = 0 AND hidden = 0 AND starttime <= :starttime AND (endtime = 0 OR endtime > :endtime)';
		$parameters = array(
			':starttime' => $GLOBALS['SIM_ACCESS_TIME'],
			':endtime' => $GLOBALS['SIM_ACCESS_TIME']
		);
		$rootTypoScriptTemplates = $this->recordService->preparedGet('sys_template', 'pid', $condition, $parameters);
		return $rootTypoScriptTemplates;
	}

	/**
	 * @return array
	 */
	protected function getTypoScriptTemplatesInRootline() {
		$rootline = $this->pageRepository->getRootLine($this->configurationManager->getCurrentPageId());
		$pageUids = array();
		foreach ($rootline as $page) {
			$pageUids[] = $page['uid'];
		}
		if (empty($pageUids)) {
			return array();
		}
		$condition = 'deleted = 0 AND hidden = 0 AND starttime <= :starttime AND (endtime = 0 OR endtime > :endtime) AND pid IN (' . implode(',', $pageUids) . ')';
		$parameters = array(
			':starttime' => $GLOBALS['SIM_ACCESS_TIME'],
			':endtime' => $GLOBALS['SIM_ACCESS_TIME']
		);
		$rootTypoScriptTemplates = $this->recordService->preparedGet('sys_template', 'pid', $condition, $parameters);
		return $rootTypoScriptTemplates;
	}

	/**
	 * Scans all folders in $allTemplatePaths for template
	 * files, reads information about each file and collects
	 * the groups of files into groups of pageTSconfig setup.
	 *
	 * @param array $allTemplatePaths
	 * @return array
	 */
	protected function buildAllWizardTabGroups($allTemplatePaths) {
		$wizardTabs = array();
		$forms = $this->getContentElementFormInstances();
		foreach ($forms as $extensionKey => $formSet) {
			$formSet = $this->sortObjectsByProperty($formSet, 'options.Fluidcontent.sorting', 'ASC');
			foreach ($formSet as $id => $form) {
				/** @var Form $form */
				$group = $form->getOption(Form::OPTION_GROUP);
				if (TRUE === empty($group)) {
					$group = 'Content';
				}
				$sanitizedGroup = $this->sanitizeString($group);
				$tabId = $group === $sanitizedGroup ? $group : 'group_' . $sanitizedGroup;
				$wizardTabs[$tabId]['title'] = LocalizationUtility::translate('fluidcontent.newContentWizard.group.' . $group, ExtensionNamingUtility::getExtensionKey($extensionKey));
				if ($wizardTabs[$tabId]['title'] === NULL) {
					$coreTranslationReference = 'LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:' . $group;
					$wizardTabs[$tabId]['title'] = LocalizationUtility::translate($coreTranslationReference, 'backend');
					if ($wizardTabs[$tabId]['title'] === NULL || $coreTranslationReference == $wizardTabs[$tabId]['title']) {
						$wizardTabs[$tabId]['title'] = $group;
					}
				}
				$contentElementId = $form->getOption('contentElementId');
				$elementTsConfig = $this->buildWizardTabItem($tabId, $id, $form, $contentElementId);
				$wizardTabs[$tabId]['elements'][$id] = $elementTsConfig;
				$wizardTabs[$tabId]['key'] = $extensionKey;
			}
		}
		return $wizardTabs;
	}

	/**
	 * @return Form[][]
	 */
	public function getContentElementFormInstances() {
		$elements = array();
		$allTemplatePaths = $this->getContentConfiguration();
		$controllerName = 'Content';
		foreach ($allTemplatePaths as $registeredExtensionKey => $templatePathSet) {
			$files = array();
			$extensionKey = TRUE === isset($templatePathSet['extensionKey']) ? $templatePathSet['extensionKey'] : $registeredExtensionKey;
			$extensionKey = ExtensionNamingUtility::getExtensionKey($extensionKey);
			$templatePaths = new TemplatePaths($templatePathSet);
			$viewContext = new ViewContext(NULL, $extensionKey);
			$viewContext->setTemplatePaths($templatePaths);
			$viewContext->setSectionName('Configuration');
			foreach ($templatePaths->getTemplateRootPaths() as $templateRootPath) {
				$files = GeneralUtility::getAllFilesAndFoldersInPath($files, rtrim($templateRootPath, '/') . '/' . $controllerName .'/', 'html');
				if (0 < count($files)) {
					foreach ($files as $templateFilename) {
						$actionName = pathinfo($templateFilename, PATHINFO_FILENAME);
						$fileRelPath = $actionName . '.html';
						$viewContext->setTemplatePathAndFilename($templateFilename);
						$form = $this->getFormFromTemplateFile($viewContext);
						if (TRUE === empty($form)) {
							$this->sendDisabledContentWarning($templateFilename);
							continue;
						}
						if (FALSE === $form->getEnabled()) {
							$this->sendDisabledContentWarning($templateFilename);
							continue;
						}
						$id = preg_replace('/[\.\/]/', '_', $registeredExtensionKey . '/' . $actionName . '.html');
						$form->setOption('contentElementId', $registeredExtensionKey . ':' . $fileRelPath);
						$elements[$registeredExtensionKey][$id] = $form;
					}
				}
			}
		}
		return $elements;
	}

	/**
	 * @return array
	 */
	public function getContentTypeSelectorItems() {
		$items = array();
		$types = $this->getContentElementFormInstances();
		foreach ($types as $group => $forms) {
			$enabledElements = array();
			foreach ($forms as $form) {
				$enabledElements[] = array(
					$form->getLabel(),
					$form->getOption('contentElementId'),
					'..' . MiscellaneousUtility::getIconForTemplate($form)
				);
			}
			if (!empty($enabledElements)) {
				$items[] = array(
					$group,
					'--div--'
				);
				$items = array_merge($items, $enabledElements);
			}
		}
		return $items;
	}

	/**
	 * Builds a big piece of pageTSconfig setup, defining
	 * every detected content element's wizard tabs and items.
	 *
	 * @param array $wizardTabs
	 * @return string
	 */
	protected function buildAllWizardTabsPageTsConfig($wizardTabs) {
		$pageTsConfig = '';
		foreach ($wizardTabs as $tab) {
			foreach ($tab['elements'] as $elementTsConfig) {
				$pageTsConfig .= $elementTsConfig;
			}
		}
		foreach ($wizardTabs as $tabId => $tab) {
			$pageTsConfig .= sprintf('
				mod.wizards.newContentElement.wizardItems.%s {
					header = %s
					show := addToList(%s)
					position = 0
					key = %s
				}
				',
				$tabId,
				$tab['title'],
				implode(',', array_keys($tab['elements'])),
				$tab['key']
			);
		}
		return $pageTsConfig;
	}

	/**
	 * Builds a single Wizard item (one FCE) based on the
	 * tab id, element id, configuration array and special
	 * template identity (groupName:Relative/Path/File.html)
	 *
	 * @param string $tabId
	 * @param string $id
	 * @param Form $form
	 * @param string $templateFileIdentity
	 * @return string
	 */
	protected function buildWizardTabItem($tabId, $id, $form, $templateFileIdentity) {
		if (TRUE === method_exists('FluidTYPO3\\Flux\\Utility\\MiscellaneousUtility', 'getIconForTemplate')) {
			$icon = MiscellaneousUtility::getIconForTemplate($form);
			$icon = ($icon ? $icon : $this->defaultIcon);
		} else {
			$icon = $this->defaultIcon;
		}
		$description = $form->getDescription();
		if (0 === strpos($icon, '../')) {
			$icon = substr($icon, 2);
		}

		$iconIdentifier = NULL;
		if (TRUE === method_exists('FluidTYPO3\\Flux\\Utility\\MiscellaneousUtility', 'createIcon')) {
			if ('/' === $icon[0]) {
				$icon = PATH_site . ltrim($icon, '/');
			}
			if (TRUE === file_exists($icon) && TRUE === is_file($icon)) {
				$extension = pathinfo($icon, PATHINFO_EXTENSION);
				switch (strtolower($extension)) {
					case 'svg':
					case 'svgz':
						$iconProvider = SvgIconProvider::class;
						break;
					default:
						$iconProvider = BitmapIconProvider::class;
				}
				$iconIdentifier = 'fluidcontent-' . $id;
				$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
				$iconRegistry->registerIcon($iconIdentifier, $iconProvider, array('source' => $icon));
			}
		}
		$defaultValues = array();
		if ($form->hasOption(Form::OPTION_DEFAULT_VALUES)) {
			foreach ($form->getOption(Form::OPTION_DEFAULT_VALUES) as $key => $value) {
				$defaultValues[] = $key . ' = ' . $value;
			}
		}

		return sprintf('
			mod.wizards.newContentElement.wizardItems.%s.elements.%s {
				iconIdentifier = %s
				title = %s
				description = %s
				tt_content_defValues {
					%s
					CType = fluidcontent_content
					tx_fed_fcefile = %s
				}
			}
			',
			$tabId,
			$id,
			$iconIdentifier,
			$form->getLabel(),
			$description,
			implode(chr(10), $defaultValues),
			$templateFileIdentity
		);
	}

	/**
	 * @param string $string
	 * @return string
	 */
	protected function sanitizeString($string) {
		$pattern = '/([^a-z0-9\-]){1,}/i';
		$replaced = preg_replace($pattern, '_', $string);
		$replaced = trim($replaced, '_');
		return empty($replaced) ? md5($string) : $replaced;
	}

	/**
	 * @param string $templatePathAndFilename
	 * @return void
	 */
	protected function sendDisabledContentWarning($templatePathAndFilename) {
		$this->message('Disabled Fluid Content Element: ' . $templatePathAndFilename, GeneralUtility::SYSLOG_SEVERITY_NOTICE);
	}

}
