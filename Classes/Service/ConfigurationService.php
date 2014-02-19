<?php
namespace FluidTYPO3\Fluidcontent\Service;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Claus Due <claus@namelesscoder.net>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use FluidTYPO3\Flux\Core;
use FluidTYPO3\Flux\Service\FluxService;
use FluidTYPO3\Flux\Utility\PathUtility;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\StringFrontend;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration Service
 *
 * Provides methods to read various configuration related
 * to Fluid Content Elements.
 *
 * @author Claus Due
 * @package Fluidcontent
 * @subpackage Service
 */
class ConfigurationService extends FluxService implements SingletonInterface {

	/**
	 * @var CacheManager
	 */
	protected $manager;

	/**
	 * @var string
	 */
	protected $defaultIcon;

	/**
	 * @param CacheManager $manager
	 * @return void
	 */
	public function injectCacheManager(CacheManager $manager) {
		$this->manager = $manager;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->defaultIcon = '../' . ExtensionManagementUtility::siteRelPath('fluidcontent') . 'Resources/Public/Icons/Plugin.png';
	}

	/**
	 * @return void
	 */
	public function initializeObject() {
		$this->writeCachedConfigurationIfMissing();
	}

	/**
	 * Get definitions of paths for FCEs defined in TypoScript
	 *
	 * @param string $extensionName
	 * @return array
	 * @api
	 */
	public function getContentConfiguration($extensionName = NULL) {
		$cacheKey = NULL === $extensionName ? 0 : $extensionName;
		$cacheKey = 'content_' . $cacheKey;
		if (TRUE === isset(self::$cache[$cacheKey])) {
			return self::$cache[$cacheKey];
		}
		$newLocation = (array) $this->getTypoScriptSubConfiguration($extensionName, 'collections', 'fluidcontent');
		$oldLocation = (array) $this->getTypoScriptSubConfiguration($extensionName, 'fce', 'fed');
		$merged = GeneralUtility::array_merge_recursive_overrule($oldLocation, $newLocation);
		$registeredExtensionKeys = Core::getRegisteredProviderExtensionKeys('Content');
		if (NULL === $extensionName) {
			foreach ($registeredExtensionKeys as $registeredExtensionKey) {
				$nativeViewLocation = $this->getContentConfiguration($registeredExtensionKey);
				if (FALSE === isset($nativeViewLocation['extensionKey'])) {
					$nativeViewLocation['extensionKey'] = ExtensionNamingUtility::getExtensionKey($registeredExtensionKey);
				}
				self::$cache[$registeredExtensionKey] = $nativeViewLocation;
				$merged[$registeredExtensionKey] = $nativeViewLocation;
			}
		} else {
			$nativeViewLocation = $this->getViewConfigurationForExtensionName($extensionName);
			if (TRUE === is_array($nativeViewLocation)) {
				$merged = GeneralUtility::array_merge_recursive_overrule($nativeViewLocation, $merged);
			}
			if (FALSE === isset($merged['extensionKey'])) {
				$merged['extensionKey'] = ExtensionNamingUtility::getExtensionKey($extensionName);
			}
		}
		self::$cache[$cacheKey] = $merged;
		return $merged;
	}

	/**
	 * @return NULL
	 */
	public function writeCachedConfigurationIfMissing() {
		/** @var StringFrontend $cache */
		$cache = $this->manager->getCache('fluidcontent');
		$hasCache = $cache->has('pageTsConfig');
		if (TRUE === $hasCache) {
			return;
		}
		$templates = $this->getAllRootTypoScriptTemplates();
		$paths = $this->getPathConfigurationsFromRootTypoScriptTemplates($templates);
		$pageTsConfig = '';
		foreach ($paths as $pageUid => $collection) {
			if (FALSE === $collection) {
				continue;
			}
			try {
				$wizardTabs = $this->buildAllWizardTabGroups($collection);
				$collectionPageTsConfig = $this->buildAllWizardTabsPageTsConfig($wizardTabs);
				$pageTsConfig .= '[PIDinRootline = ' . strval($pageUid) . ']' . LF;
				$pageTsConfig .= $collectionPageTsConfig . LF;
				$pageTsConfig .= '[GLOBAL]' . LF;
				$this->message('Built content setup for page ' . $pageUid, GeneralUtility::SYSLOG_SEVERITY_INFO, 'Fluidcontent');
			} catch (\Exception $error) {
				$this->debug($error);
			}
		}
		$cache->set('pageTsConfig', $pageTsConfig, array(), 806400);
		return NULL;
	}

	/**
	 * Gets a collection of path configurations for content elements
	 * based on each root TypoScript template in the provided array
	 * of templates. Returns an array of paths indexed by the root
	 * page UID.
	 *
	 * @param array $templates
	 * @return array
	 */
	protected function getPathConfigurationsFromRootTypoScriptTemplates($templates) {
		$allTemplatePaths = array();
		$registeredExtensionKeys = Core::getRegisteredProviderExtensionKeys('Content');
		foreach ($templates as $templateRecord) {
			$pageUid = $templateRecord['pid'];
			/** @var \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService $template */
			$template = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');
			$template->tt_track = 0;
			$template->init();
			/** @var \TYPO3\CMS\Frontend\Page\PageRepository $sys_page */
			$sys_page = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
			$rootLine = $sys_page->getRootLine($pageUid);
			$template->runThroughTemplates($rootLine);
			$template->generateConfig();
			$oldTemplatePathLocation = (array) $template->setup['plugin.']['tx_fed.']['fce.'];
			$newTemplatePathLocation = (array) $template->setup['plugin.']['tx_fluidcontent.']['collections.'];
			$registeredPathCollections = array();
			foreach ($registeredExtensionKeys as $registeredExtensionKey) {
				$nativeViewLocation = $this->getContentConfiguration($registeredExtensionKey);
				if (FALSE === isset($nativeViewLocation['extensionKey'])) {
					$nativeViewLocation['extensionKey'] = ExtensionNamingUtility::getExtensionKey($registeredExtensionKey);
				}
				$registeredPathCollections[$registeredExtensionKey] = $nativeViewLocation;
			}
			$merged = GeneralUtility::array_merge_recursive_overrule($oldTemplatePathLocation, $newTemplatePathLocation);
			$merged = GeneralUtility::removeDotsFromTS($merged);
			$merged = GeneralUtility::array_merge($merged, $registeredPathCollections);
			$allTemplatePaths[$pageUid] = $merged;
		}
		return $allTemplatePaths;
	}

	/**
	 * @return array
	 */
	protected function getAllRootTypoScriptTemplates() {
		static $statement = NULL;
		if (NULL === $statement) {
			$statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('pid', 'sys_template', 'deleted = 0 AND hidden = 0 AND starttime <= :starttime AND (endtime = 0 OR endtime > :endtime)');
		}

		$statement->bindValue(':starttime', $GLOBALS['SIM_ACCESS_TIME']);
		$statement->bindValue(':endtime', $GLOBALS['SIM_ACCESS_TIME']);
		$statement->execute();
		$rootTypoScriptTemplates = $statement->fetchAll();
		$statement->free();

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
		foreach ($allTemplatePaths as $key => $templatePathSet) {
			$key = trim($key, '.');
			$extensionKey = TRUE === isset($templatePathSet['extensionKey']) ? $templatePathSet['extensionKey'] : $key;
			$extensionKey = ExtensionNamingUtility::getExtensionKey($extensionKey);
			$paths = array(
				'templateRootPath' => TRUE === isset($templatePathSet['templateRootPath']) ? $templatePathSet['templateRootPath'] : 'EXT:' . $extensionKey . '/Resources/Private/Templates/',
				'layoutRootPath' => TRUE === isset($templatePathSet['layoutRootPath']) ? $templatePathSet['layoutRootPath'] : 'EXT:' . $extensionKey . '/Resources/Private/Layouts/',
				'partialRootPath' => TRUE === isset($templatePathSet['partialRootPath']) ? $templatePathSet['partialRootPath'] : 'EXT:' . $extensionKey . '/Resources/Private/Partials/',
			);
			$paths = PathUtility::translatePath($paths);
			$templateRootPath = $paths['templateRootPath'];
			if ('/' !== substr($templateRootPath, -1)) {
				$templateRootPath .= '/';
			}
			if (TRUE === file_exists($templateRootPath . 'Content/')) {
				$templateRootPath = $templateRootPath . 'Content/';
			}
			$files = array();
			$files = GeneralUtility::getAllFilesAndFoldersInPath($files, $templateRootPath, 'html');
			if (0 < count($files)) {
				foreach ($files as $templateFilename) {
					$fileRelPath = substr($templateFilename, strlen($templateRootPath));
					$form = $this->getFormFromTemplateFile($templateFilename, 'Configuration', 'form', $paths, $extensionKey);
					if (TRUE === empty($form)) {
						$this->sendDisabledContentWarning($templateFilename);
						continue;
					}
					if (FALSE === $form->getEnabled()) {
						$this->sendDisabledContentWarning($templateFilename);
						continue;
					}
					$group = $form->getGroup();
					if (FALSE === empty($group)) {
						$tabId = $this->sanitizeString($group);
						$wizardTabs[$tabId]['title'] = $group;
					} else {
						$tabId = 'Content';
					}
					$id = $extensionKey . '_' . preg_replace('/[\.\/]/', '_', $fileRelPath);
					$elementTsConfig = $this->buildWizardTabItem($tabId, $id, $form, $key . ':' . $fileRelPath);
					$wizardTabs[$tabId]['elements'][$id] = $elementTsConfig;
					$wizardTabs[$tabId]['key'] = $extensionKey;
				}
			}
		}
		return $wizardTabs;
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
					show = %s
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
	 * @param \FluidTYPO3\Flux\Form $form
	 * @param string $templateFileIdentity
	 * @return string
	 */
	protected function buildWizardTabItem($tabId, $id, $form, $templateFileIdentity) {
		$icon = $form->getIcon();
		$description = $form->getDescription();
		if (TRUE === empty($description)) {
			$description = '-';
		}
		$iconFileRelativePath = ($icon ? $icon : $this->defaultIcon);
		return sprintf('
			mod.wizards.newContentElement.wizardItems.%s.elements.%s {
				icon = %s
				title = %s
				description = %s
				tt_content_defValues {
					CType = fluidcontent_content
					tx_fed_fcefile = %s
				}
			}
			',
			$tabId,
			$id,
			$iconFileRelativePath,
			$form->getLabel(),
			$description,
			$templateFileIdentity
		);
	}

	/**
	 * @param string $string
	 * @return string
	 */
	protected function sanitizeString($string) {
		$pattern = '/([^a-z0-9\-]){1,}/i';
		$string = preg_replace($pattern, '-', $string);
		return trim($string, '-');
	}

	/**
	 * @param string $templatePathAndFilename
	 * @return void
	 */
	protected function sendDisabledContentWarning($templatePathAndFilename) {
		$this->message('Disabled Fluid Content Element: ' . $templatePathAndFilename, GeneralUtility::SYSLOG_SEVERITY_NOTICE);
	}

}
