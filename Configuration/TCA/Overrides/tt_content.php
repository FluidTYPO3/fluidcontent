<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TRUE === version_compare(TYPO3_version, '7.1', '<')) {
	$contentSelector = 'FluidTYPO3\Fluidcontent\Backend\LegacyContentSelector->renderField';
} else {
	$contentSelector = 'FluidTYPO3\Fluidcontent\Backend\ContentSelector->renderField';
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', array(
	'tx_fed_fcefile' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xml:tt_content.tx_fed_fcefile',
		'displayCond' => 'FIELD:CType:=:fluidcontent_content',
		'config' => array(
			'type' => 'user',
			'userFunc' => $contentSelector,
		)
	),
));

$GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = '
                --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
                --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.headers;headers,
                pi_flexform,
        --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
                --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
        --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
                --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
                --palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
        --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended
';

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['fluidcontent_content'] = 'apps-pagetree-root';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('tt_content', 'general', 'tx_fed_fcefile', 'after:CType');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'pi_flexform', 'fluidcontent_content', 'after:header');

// Hide child records if the content element is not a fluidcontent element.
$GLOBALS['TCA']['tt_content']['columns']['tx_flux_children']['displayCond'] = 'FIELD:CType:=:fluidcontent_content';
