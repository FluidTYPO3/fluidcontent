<?php
namespace FluidTYPO3\Fluidcontent\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Backend\ContentTypeFilter;
use FluidTYPO3\Fluidcontent\Service\ConfigurationService;
use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Provider\ContentProvider as FluxContentProvider;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\Utility\MiscellaneousUtility;
use FluidTYPO3\Flux\Utility\PathUtility;
use FluidTYPO3\Flux\View\TemplatePaths;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Content object configuration provider
 */
class ContentProvider extends FluxContentProvider implements ProviderInterface {

	/**
	 * @var string
	 */
	protected $controllerName = 'Content';

	/**
	 * @var string
	 */
	protected $tableName = 'tt_content';

	/**
	 * @var string
	 */
	protected $fieldName = 'pi_flexform';

	/**
	 * @var string
	 */
	protected $extensionKey = 'fluidcontent';

	/**
	 * @var string
	 */
	protected $contentObjectType = 'fluidcontent_content';

	/**
	 * @var string
	 */
	protected $configurationSectionName = 'Configuration';

	/**
	 * @var ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var ConfigurationService
	 */
	protected $contentConfigurationService;

	/**
	 * @param ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param ConfigurationService $configurationService
	 * @return void
	 */
	public function injectContentConfigurationService(ConfigurationService $configurationService) {
		$this->contentConfigurationService = $configurationService;
	}

	/**
	 * @param array $items
	 * @return ContentTypeFilter
	 */
	protected function getContentTypeFilter(array $items) {
		return new ContentTypeFilter($items);
	}

	/**
	 * @param array $record
	 * @param array $configuration
	 * @return array
	 */
	public function processTableConfiguration(array $row, array $configuration) {
		if ($row['CType'] === $this->contentObjectType) {
			// Create values for the fluidcontent type selector
			$configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items'] = array_merge(
				$configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items'],
				$this->contentConfigurationService->getContentTypeSelectorItems()
			);

			// Filter by which fluidcontent types are allowed by backend user group
			$configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items'] = $this->getContentTypeFilter(
				$configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items']
			)->filterItemsByGroupAccess();

			// Filter by which fluidcontent types are allowed by content area, if element is nested
			if (!empty($row['tx_flux_parent']) && !empty($row['tx_flux_column'])) {
				$filter = $this->getContentTypeFilter($configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items']);
				$configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items'] = $this->getContentTypeFilter(
					$configuration['processedTca']['columns']['tx_fed_fcefile']['config']['items']
				)->filterItemsByGridColumn(
					$this->getGrid($this->recordService->getSingle('tt_content', '*', (integer) $row['tx_flux_parent'])),
					$row['tx_flux_column']
				);
			}
		}
		return $configuration;
	}

	/**
	 * Check if an update with a new sys_language_uid did occur. if yes, apply this new sys_language_uid recursively
	 *
	 * @param string $operation TYPO3 operation identifier, i.e. "update", "new" etc.
	 * @param integer $id The ID of the current record (which is sometimes now included in $row
	 * @param array $row the record data, by reference. Changing fields' values changes the record's values just before saving
	 * @param DataHandler $reference A reference to the \TYPO3\CMS\Core\DataHandling\DataHandler object that is currently saving the record
	 * @param array $removals Allows overridden methods to pass an additional array of field names to remove from the stored Flux value
	 * @return void
	 */
	public function postProcessRecord($operation, $id, array &$row, DataHandler $reference, array $removals = array()) {
		if (isset($row['sys_language_uid']) !== TRUE || $operation !== 'update') {
			return;
		}

		$record = $this->recordService->getSingle('tt_content', '*', (integer) $id);
		if ($record['CType'] !== $this->contentObjectType) {
			return;
		}
		
		$this->applyLanguageRecursively($id, $row['sys_language_uid']);
	}

	/**
	 * walk fluidcontent elements recusively downwards and update their sys_language_uid
	 *
	 * @param integer $parentUid
	 * @param integer $languageUid
	 * return void
	 */
	public function applyLanguageRecursively($parentUid, $languageUid) {
		$children = $this->recordService->get('tt_content', '*', 'tx_flux_parent = ' . $parentUid);
		foreach	($children as $child) {
			$child['sys_language_uid'] = $languageUid;
			$this->recordService->update('tt_content', $child);
			$this->applyLanguageRecursively($child['uid'], $languageUid);
		}
	}

	/**
	 * @param array $row
	 * @return \FluidTYPO3\Flux\Form|NULL
	 */
	public function getForm(array $row) {
		$form = parent::getForm($row);
		if (NULL !== $form) {
			$moveSortingProperty = (
				defined('FluidTYPO3\\Flux\\Form::OPTION_SORTING')
				&& FALSE === $form->hasOption(Form::OPTION_SORTING)
				&& TRUE === $form->hasOption('Fluidcontent.sorting')
			);
			if (TRUE === $moveSortingProperty) {
				$form->setOption(Form::OPTION_SORTING, $form->getOption('Fluidcontent.sorting'));
			}
		}
		return $form;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getTemplatePathAndFilename(array $row) {
		if (FALSE === empty($this->templatePathAndFilename)) {
			$templateReference = $this->templatePathAndFilename;
			if ('/' !== $templateReference{0}) {
				$templateReference = GeneralUtility::getFileAbsFileName($templateReference);
			}
			if (TRUE === file_exists($templateReference)) {
				return $templateReference;
			}
			return NULL;
		}
		$templateReference = $this->getControllerActionReferenceFromRecord($row);
		list (, $filename) = explode(':', $templateReference);
		list ($controllerAction, $format) = explode('.', $filename);
		$controllerAction = $this->getControllerActionFromRecord($row);
		$paths = $this->getTemplatePaths($row);
		$templatePaths = new TemplatePaths($paths);
		return $templatePaths->resolveTemplateFileForControllerAndActionAndFormat('Content', $controllerAction, $format);
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public function getTemplatePaths(array $row) {
		$extensionName = $this->getExtensionKey($row);
		$paths = $this->contentConfigurationService->getContentConfiguration($extensionName);
		if (TRUE === is_array($paths) && FALSE === empty($paths)) {
			$paths = PathUtility::translatePath($paths);
		}
		return $paths;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getExtensionKey(array $row) {
		$extensionKey = $this->extensionKey;
		$action = $row['tx_fed_fcefile'];
		if (FALSE !== strpos($action, ':')) {
			$extensionName = array_shift(explode(':', $action));
			if (FALSE === empty($extensionName)) {
				$extensionKey = ExtensionNamingUtility::getExtensionKey($extensionName);
			}
		}
		return $extensionKey;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerExtensionKeyFromRecord(array $row) {
		$fileReference = $this->getControllerActionReferenceFromRecord($row);
		$identifier = explode(':', $fileReference);
		$extensionName = array_shift($identifier);
		return $extensionName;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerActionFromRecord(array $row) {
		$fileReference = $this->getControllerActionReferenceFromRecord($row);
		$identifier = explode(':', $fileReference);
		$actionName = array_pop($identifier);
		$actionName = basename($actionName, '.html');
		$actionName = lcfirst($actionName);
		return $actionName;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerActionReferenceFromRecord(array $row) {
		$fileReference = $row['tx_fed_fcefile'];
		return TRUE === empty($fileReference) ? 'Fluidcontent:error.html' : $fileReference;
	}

	/**
	 * Switchable priority: highest possible for records matching
	 * this Provider's targeted CType - lowest possible for others.
	 *
	 * @param array $row
	 * @return integer
	 */
	public function getPriority(array $row) {
		if (TRUE === isset($row['CType']) && $this->contentObjectType === $row['CType']) {
			return 100;
		}
		return 0;
	}

	/**
	 * Get preview chunks - header and content - as
	 * array(string $headerContent, string $previewContent, boolean $continueRendering)
	 *
	 * @param array $row The record data to be analysed for variables to use in a rendered preview
	 * @return array
	 */
	public function getPreview(array $row) {
		if ($this->contentObjectType !== $row['CType']) {
			return array(NULL, NULL, TRUE);
		}

		$previewContent = $this->getPreviewView()->getPreview($this, $row);
		return array(NULL, $previewContent, empty($previewContent));
	}

}
