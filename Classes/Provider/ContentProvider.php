<?php
namespace FluidTYPO3\Fluidcontent\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Service\ConfigurationService;
use FluidTYPO3\Flux\Provider\ContentProvider as FluxContentProvider;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\Utility\PathUtility;
use FluidTYPO3\Flux\Utility\ResolveUtility;
use FluidTYPO3\Flux\View\TemplatePaths;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
	protected $configurationService;

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
	public function injectConfigurationService(ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
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
		$paths = $this->configurationService->getContentConfiguration($extensionName);
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
		}
		if (FALSE === empty($extensionName)) {
			$extensionKey = ExtensionNamingUtility::getExtensionKey($extensionName);
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
		$actionName = lcfirst(basename($actionName, '.html'));
		return $actionName;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerActionReferenceFromRecord(array $row) {
		$fileReference = $row['tx_fed_fcefile'];
		return TRUE === empty($fileReference) ? 'Fluidcontent:index.html' : $fileReference;
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

}
