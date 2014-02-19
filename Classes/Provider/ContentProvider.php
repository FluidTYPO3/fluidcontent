<?php
namespace FluidTYPO3\Fluidcontent\Provider;
/*****************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Claus Due <claus@namelesscoder.net>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 *****************************************************************/

use FluidTYPO3\Fluidcontent\Service\ConfigurationService;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Provider\ContentProvider as FluxContentProvider;
use FluidTYPO3\Flux\Utility\PathUtility;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Content object configuration provider
 *
 * @author Claus Due <claus@namelesscoder.net>
 * @package Fluidcontent
 * @subpackage Provider
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
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \FluidTYPO3\Fluidcontent\Service\ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param \FluidTYPO3\Fluidcontent\Service\ConfigurationService $configurationService
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
			$templatePathAndFilename = GeneralUtility::getFileAbsFileName($this->templatePathAndFilename);
			if (TRUE === file_exists($templatePathAndFilename)) {
				return $templatePathAndFilename;
			}
		} else {
			$templatePathAndFilename = $row['tx_fed_fcefile'];
			if (FALSE === strpos($templatePathAndFilename, ':')) {
				return NULL;
			}
		}
		list (, $filename) = explode(':', $templatePathAndFilename);
		$paths = $this->getTemplatePaths($row);
		$templateRootPath = $paths['templateRootPath'];
		if ('/' === substr($templateRootPath, -1)) {
			$templateRootPath = substr($templateRootPath, 0, -1);
		}
		if (TRUE === file_exists($templateRootPath . '/' . $this->controllerName)) {
			$templateRootPath = $templateRootPath . '/' . $this->controllerName;
		}
		$templatePathAndFilename = $templateRootPath . '/' . $filename;
		if (TRUE === isset($paths['overlays']) && TRUE === is_array($paths['overlays'])) {
			foreach ($paths['overlays'] as $possibleOverlayPaths) {
				if (TRUE === isset($possibleOverlayPaths['templateRootPath'])) {
					$overlayTemplateRootPath = $possibleOverlayPaths['templateRootPath'];
					$overlayTemplateRootPath = rtrim($overlayTemplateRootPath, '/');
					$possibleOverlayFile = GeneralUtility::getFileAbsFileName($overlayTemplateRootPath . '/' . $this->controllerName . '/' . $filename);
					if (TRUE === file_exists($possibleOverlayFile)) {
						$templatePathAndFilename = $possibleOverlayFile;
						break;
					}
				}
			}
		}
		$templatePathAndFilename = GeneralUtility::getFileAbsFileName($templatePathAndFilename);
		return $templatePathAndFilename;
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
			return $paths;
		}

		return parent::getTemplatePaths($row);
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getExtensionKey(array $row) {
		$action = $row['tx_fed_fcefile'];
		if (FALSE !== strpos($action, ':')) {
			$extensionName = array_shift(explode(':', $action));
		}
		if (FALSE === empty($extensionName)) {
			$extensionKey = ExtensionNamingUtility::getExtensionKey($extensionName);
			return $extensionKey;
		}
		return parent::getExtensionKey($row);
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
		$actionName{0} = strtolower($actionName{0});
		return $actionName;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerActionReferenceFromRecord(array $row) {
		$fileReference = $row['tx_fed_fcefile'];
		return $fileReference;
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
