<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'be_groups',
    [
        'tx_fluidcontent_allowedfluidcontent' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xlf:be_groups.tx_fluidcontent_allowedfluidcontent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'size' => 10,
                'maxitems' => 99999999,
                'multiple' => true,
                'items' => [],
            ],
        ],
        'tx_fluidcontent_deniedfluidcontent' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xlf:be_groups.tx_fluidcontent_deniedfluidcontent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'size' => 10,
                'maxitems' => 99999999,
                'multiple' => true,
                'items' => [],
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'be_groups',
    'tx_fluidcontent_allowedfluidcontent,tx_fluidcontent_deniedfluidcontent',
    '0',
    'after:pagetypes_select'
);
