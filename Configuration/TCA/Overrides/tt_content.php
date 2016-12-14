<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(function () {

    $languageFilePrefix         = 'LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xlf:';
    $frontendLanguageFilePrefix = 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', [
       'tx_fed_fcefile' => [
          'exclude'     => 1,
          'label'       => $languageFilePrefix . 'tt_content.tx_fed_fcefile',
          'displayCond' => 'FIELD:CType:=:fluidcontent_content',
          'config'      => [
             'type'          => 'select',
             'renderType'    => 'selectSingle',
             'items'         => [
                [$languageFilePrefix . 'tt_content.tx_fed_fcefile', ''],
             ],
             'showIconTable' => false,
             'selicon_cols'  => 0,
          ],
       ],
    ]);

    $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',tx_fed_fcefile';
    $GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = '
                --palette--;' . $frontendLanguageFilePrefix . 'palette.general;general,
                --palette--;' . $frontendLanguageFilePrefix . 'palette.headers;headers,
                pi_flexform,
        --div--;' . $frontendLanguageFilePrefix . 'tabs.appearance,
                --palette--;' . $frontendLanguageFilePrefix . 'palette.frames;frames,
        --div--;' . $frontendLanguageFilePrefix . 'tabs.access,
				    hidden;' . $frontendLanguageFilePrefix . 'field.default.hidden,
                --palette--' . $frontendLanguageFilePrefix . 'palette.access;access,
        --div--;' . $frontendLanguageFilePrefix . 'tabs.extended
';

    $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['fluidcontent_content'] = 'apps-pagetree-root';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('tt_content', 'general', 'tx_fed_fcefile', 'after:CType');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'pi_flexform', 'fluidcontent_content', 'after:header');
});
