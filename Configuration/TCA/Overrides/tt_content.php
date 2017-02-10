<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(
    function () {

        $languageFilePrefix = 'LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xlf:';
        $frontendLanguageFilePrefix = 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:';
        $tabsLanguageFilePrefix = 'LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf';

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
            'tt_content',
            [
                'tx_fed_fcefile' => [
                    'exclude' => 1,
                    'label' => $languageFilePrefix.'tt_content.tx_fed_fcefile',
                    'displayCond' => 'FIELD:CType:=:fluidcontent_content',
                    'config' => [
                        'type' => 'select',
                        'renderType' => 'selectSingle',
                        'items' => [
                            [$languageFilePrefix.'tt_content.tx_fed_fcefile', ''],
                        ],
                        'showIconTable' => false,
                        'selicon_cols'  => 0,
                    ],
                ],
            ]
        );

        $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',tx_fed_fcefile';
        $GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = '
            --div--;' . $tabsLanguageFilePrefix . ':general,
            --palette--;' . $frontendLanguageFilePrefix . ':palette.general;general,
            --palette--;' . $frontendLanguageFilePrefix . ':palette.header;header,
            --div--;' . $frontendLanguageFilePrefix . ':tabs.appearance, layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
            --palette--; '. $frontendLanguageFilePrefix . ':palette.frames;frames,
            --palette--;' . $frontendLanguageFilePrefix . ':palette.appearanceLinks;appearanceLinks,
            --div--;' . $tabsLanguageFilePrefix . ':language, --palette--;;language,
            --div--;' . $tabsLanguageFilePrefix . ':access,
            --palette--;;hidden,
            --palette--;' . $frontendLanguageFilePrefix . ':palette.access;access,
            --div--;' . $tabsLanguageFilePrefix . ':categories, categories,
            --div--;' . $tabsLanguageFilePrefix . ':notes, rowDescription,
            --div--;' . $tabsLanguageFilePrefix . ':extended,
            --div--;LLL:EXT:flux/Resources/Private/Language/locallang.xlf:tt_content.tabs.relation, tx_flux_parent, tx_flux_column
        ';

        $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['fluidcontent_content'] = 'apps-pagetree-root';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
            'tt_content',
            'general',
            'tx_fed_fcefile',
            'after:CType'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            'pi_flexform',
            'fluidcontent_content',
            'after:header'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            ',--div--;LLL:EXT:fluidcontent_elements/Resources/Private/Language/locallang_tabs.xlf:notes,rowDescription,',
            'fluidcontent_content',
            'before:--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended'
        );
    }
);
