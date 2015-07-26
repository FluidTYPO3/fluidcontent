<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', [
        'tx_fed_fcefile' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xml:tt_content.tx_fed_fcefile',
                'config' => [
                        'type' => 'user',
                        'userFunc' => TRUE === version_compare(TYPO3_version, '7.1', '<')
                                ? 'FluidTYPO3\Fluidcontent\Backend\LegacyContentSelector->renderField'
                                : 'FluidTYPO3\Fluidcontent\Backend\ContentSelector->renderField',
				]
		],
]);

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

