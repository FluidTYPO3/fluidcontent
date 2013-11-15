<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Claus Due <claus@wildside.dk>, Wildside A/S
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

/**
 * Checks the configuration stored in a Flux form to see
 * if the particular field being rendered should be
 * suppressed from output.
 *
 * @package	Fluidcontent
 * @subpackage UserFunction
 */
class Tx_Fluidcontent_UserFunction_ContentFieldSuppressor {

	/**
	 * @param string $content
	 * @param array $parameters
	 * @return NULL|string
	 */
	public function renderField($content, $parameters) {
		list ($table, $uid) = explode(':', $GLOBALS['TSFE']->currentRecord);
		if ('tt_content' !== $table) {
			return NULL;
		}
		$field = $parameters['field'];
		$record = array_pop($GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $table, "uid = '" . $uid . "'"));
		if ('fluidcontent_content' !== $record['CType']) {
			return NULL;
		}
		unset($content);
		/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var $flexformService Tx_Flux_Service_FluxService */
		$flexformService = $objectManager->get('Tx_Flux_Service_FluxService');
		/** @var $configurationService Tx_Fluidcontent_Service_ConfigurationService */
		$configurationService = $objectManager->get('Tx_Fluidcontent_Service_ConfigurationService');
		list ($extensionName, $filename) = explode(':', $record['tx_fed_fcefile']);
		$paths = $configurationService->getContentConfiguration($extensionName);
		$templatePathAndFilename = $paths['templateRootPath'] . $filename;
		$values = $flexformService->convertFlexFormContentToArray($record['pi_flexform']);
		$config = $flexformService->getFlexFormConfigurationFromFile($templatePathAndFilename, $values, 'Configuration', $paths, $extensionName);
		if (TRUE === in_array($field, $config['hidefields'])) {
			return NULL;
		}
		switch ($field) {
			case 'header': return $this->renderTitle($record);
			default: return $record[$field];
		}
	}

	/**
	 * Renders the TypoScript object in the given TypoScript setup path.
	 *
	 * @param mixed $data the data to be used for rendering the cObject. Can be an object, array or string. If this argument is not set, child nodes will be used
	 * @throws Exception
	 * @return string the content of the rendered TypoScript object
	 */
	public function renderTitle($data) {
		$typoscriptObjectPath = 'lib.stdheader';
		/** @var $contentObject tslib_cObj */
		$contentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tslib_cObj');
		$contentObject->start($data);
		$pathSegments = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('.', $typoscriptObjectPath);
		$lastSegment = array_pop($pathSegments);
		$setup = $GLOBALS['TSFE']->tmpl->setup;
		foreach ($pathSegments as $segment) {
			if (FALSE === array_key_exists(($segment . '.'), $setup)) {
				throw new Exception('TypoScript object path "' . htmlspecialchars($typoscriptObjectPath) . '" does not exist', 1253191023);
			}
			$setup = $setup[$segment . '.'];
		}
		$content = $contentObject->cObjGetSingle($setup[$lastSegment], $setup[$lastSegment . '.']);
		return $content;
	}

}
