<?php
/*****************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Claus Due <claus@wildside.dk>, Wildside A/S
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

/**
 * Content object configuration provider
 *
 * @author Claus Due <claus@wildside.dk>, Wildside A/S
 * @package Fluidcontent
 * @subpackage Provider
 */
class Tx_Fluidcontent_Provider_ContentConfigurationProvider extends Tx_Flux_Provider_AbstractContentObjectConfigurationProvider implements Tx_Flux_Provider_ContentObjectConfigurationProviderInterface {

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
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var Tx_Fluidcontent_Service_ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param Tx_Fluidcontent_Service_ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(Tx_Fluidcontent_Service_ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getTemplatePathAndFilename(array $row) {
		$templatePathAndFilename = $row['tx_fed_fcefile'];
		if (strpos($templatePathAndFilename, ':') === FALSE) {
			return NULL;
		}
		list (, $filename) = explode(':', $templatePathAndFilename);
		$paths = $this->getTemplatePaths($row);
		if ($paths === NULL) {
			return NULL;
		}
		$templateRootPath = $paths['templateRootPath'];
		if ('/' === substr($templateRootPath, -1)) {
			$templateRootPath = substr($templateRootPath, 0, -1);
		}
		if (TRUE === file_exists($templateRootPath . '/Content')) {
			$templateRootPath = $templateRootPath . '/Content';
		}
		$templatePathAndFilename = $templateRootPath . '/' . $filename;
		if (TRUE === isset($paths['overlays']) && TRUE === is_array($paths['overlays'])) {
			foreach ($paths['overlays'] as $possibleOverlayPaths) {
				if (TRUE === isset($possibleOverlayPaths['templateRootPath'])) {
					$overlayTemplateRootPath = $possibleOverlayPaths['templateRootPath'];
					$overlayTemplateRootPath = rtrim($overlayTemplateRootPath, '/');
					$possibleOverlayFile = t3lib_div::getFileAbsFileName($overlayTemplateRootPath . '/Content/' . $filename);
					if (TRUE === file_exists($possibleOverlayFile)) {
						$templatePathAndFilename = $possibleOverlayFile;
						break;
					}
				}
			}
		}
		$templatePathAndFilename = t3lib_div::getFileAbsFileName($templatePathAndFilename);
		return $templatePathAndFilename;
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public function getTemplateVariables(array $row) {
		$paths = $this->getTemplatePaths($row);
		$extensionKey = (TRUE === isset($paths['extensionKey']) ? $paths['extensionKey'] : $this->getExtensionKey($row));
		$extensionName = t3lib_div::underscoredToUpperCamelCase($extensionKey);
		$templatePathAndFilename = $this->getTemplatePathAndFilename($row);
		$flexFormVariables = $this->configurationService->convertFlexFormContentToArray($row['pi_flexform']);
		$stored = $this->configurationService->getStoredVariable($templatePathAndFilename, 'storage', 'Configuration', $paths, $extensionName, $flexFormVariables);
		$variables = $this->configurationService->convertFlexFormContentToArray($row['pi_flexform'], $stored);
		$variables = t3lib_div::array_merge_recursive_overrule($flexFormVariables, $variables);
		return $variables;
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public function getTemplatePaths(array $row) {
		$templatePathAndFilename = $row['tx_fed_fcefile'];
		$extensionName = array_shift(explode(':', $templatePathAndFilename));
		$paths = $this->configurationService->getContentConfiguration($extensionName);
		$paths = Tx_Flux_Utility_Path::translatePath($paths);
		return $paths;
	}

	/**
	 * Perform various cleanup operations upon clearing cache
	 *
	 * @param array $command
	 * @return void
	 */
	public function clearCacheCommand($command = array()) {
		if (TRUE === isset($command['uid'])) {
			return;
		}
		if (file_exists(FLUIDCONTENT_TEMPFILE) === TRUE) {
			unlink(FLUIDCONTENT_TEMPFILE);
		}
		$this->configurationService->writeCachedConfigurationIfMissing();
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getExtensionKey(array $row) {
		$action = $row['tx_fed_fcefile'];
		if (FALSE === strpos($action, ':')) {
			$paths = $this->getTemplatePaths($row);
			if (TRUE === isset($paths['extensionKey'])) {
				return $paths['extensionKey'];
			}
		}
		list ($extensionName, ) = explode(':', $action);
		if (FALSE === empty($extensionName)) {
			$extensionKey = t3lib_div::camelCaseToLowerCaseUnderscored($extensionName);
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
		$extensionKey = t3lib_div::camelCaseToLowerCaseUnderscored($extensionName);
		return $extensionKey;
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

}
