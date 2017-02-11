<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(
    function () {

        $languageFilePrefix = 'LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xlf:';
        $frontendLanguageFilePrefix = 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:';
        if (version_compare(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('core'), '8.0', '<')) {
            $tabsLanguageFilePrefix = 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.';
            $categoryTabLabel = 'LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category:category';
        } else {
            $tabsLanguageFilePrefix = 'LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:';
            $categoryTabLabel = $tabsLanguageFilePrefix . 'categories';
        }

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

        $GLOBALS['TCA']['tt_content']['ctrl']['useColumnsForDefaultValues'] .= ',tx_fed_fcefile';
        $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',tx_fed_fcefile';
        $GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = '
            --palette--;' . $frontendLanguageFilePrefix . 'palette.general;general,
            --palette--;' . $frontendLanguageFilePrefix . 'palette.header;header,
            --div--;' . $frontendLanguageFilePrefix . 'tabs.appearance, layout;' . $frontendLanguageFilePrefix . 'layout_formlabel,
            --palette--; '. $frontendLanguageFilePrefix . 'palette.frames;frames,
            --palette--;' . $frontendLanguageFilePrefix . 'palette.appearanceLinks;appearanceLinks,
            --div--;' . $tabsLanguageFilePrefix . 'access,
            --palette--;' . $frontendLanguageFilePrefix . ';visibility,
            --palette--;' . $frontendLanguageFilePrefix . ':palette.access;access,
            --div--;' . $categoryTabLabel . ', categories,
            --div--;' . $tabsLanguageFilePrefix . 'extended,
            --div--;LLL:EXT:flux/Resources/Private/Language/locallang.xlf:tt_content.tabs.relation, tx_flux_parent, tx_flux_column
        ';

        if (version_compare(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('core'), '8.0', '>=')) {
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', ',--div--;' . $tabsLanguageFilePrefix . 'language, --palette--;;language,', 'fluidcontent_content', 'before:access');
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', ',--div--;' . $tabsLanguageFilePrefix . 'notes, rowDescription,', 'fluidcontent_content', 'after:language');
        }

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
    }
);
