<?php
namespace FluidTYPO3\Fluidcontent\Backend;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Claus Due <claus@namelesscoder.net>
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

use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;

/**
 * Class that renders a selection field for Fluid FCE template selection
 *
 * @package	Fluidcontent
 * @subpackage Backend
 */
class ContentSelector {

	/**
	 * Render a Flexible Content Element type selection field
	 *
	 * @param array $parameters
	 * @param mixed $parentObject
	 * @return string
	 */
	public function renderField(array &$parameters, &$parentObject) {
		/** @var \TYPO3\CMS\Core\Cache\CacheManager $cacheManager */
		$cacheManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager')->get('TYPO3\CMS\Core\Cache\CacheManager');
		/** @var StringFrontend $cache */
		$cache = $cacheManager->getCache('fluidcontent');
		$pageTypoScript = TRUE === $cache->has('pageTsConfig') ? $cache->get('pageTsConfig') : array();
		$tsParser = new TypoScriptParser();
		$conditions = new ConditionMatcher();
		$pageUid = GeneralUtility::_GET('id');
		$pageUid = intval($pageUid);
		if (0 === $pageUid) {
		    $pageUid = intval($parameters['row']['pid']);
		}
		$conditions->setPageId($pageUid);
		$tsParser->parse($pageTypoScript, $conditions);
		$setup = $tsParser->setup['mod.']['wizards.']['newContentElement.']['wizardItems.'];
		if (FALSE === is_array($setup)) {
			return LocalizationUtility::translate('pages.no_content_types', 'Fluidcontent');
		}
		$setup = GeneralUtility::removeDotsFromTS($setup);
		$name = $parameters['itemFormElName'];
		$value = $parameters['itemFormElValue'];
		$select = '<div><select name="' . htmlspecialchars($name) . '"  class="formField select" onchange="if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };">' . LF;
		$select .= '<option value="">' . $GLOBALS['LANG']->sL('LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xml:tt_content.tx_fed_fcefile', TRUE) . '</option>' . LF;
		foreach ($setup as $groupLabel => $configuration) {
			$select .= '<optgroup label="' . htmlspecialchars($groupLabel) . '">' . LF;
			foreach ($configuration['elements'] as $elementConfiguration) {
				$optionValue = $elementConfiguration['tt_content_defValues']['tx_fed_fcefile'];
				$selected = ($optionValue === $value ? ' selected="selected"' : '');
				$label = $elementConfiguration['title'];
				$label = $GLOBALS['LANG']->sL($label);
				$select .= '<option value="' . htmlspecialchars($optionValue) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>' . LF;
			}
			$select .= '</optgroup>' . LF;
		}
		$select .= '</select></div>' . LF;
		unset($parentObject);
		return $select;
	}

}
