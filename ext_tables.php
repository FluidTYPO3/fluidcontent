<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
    \FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidcontent\Provider\ContentProvider');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'Fluid Content',
            'fluidcontent_content',
            'EXT:fluidcontent/Resources/Public/Icons/Plugin.svg',
        ],
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
        'FluidTYPO3.Fluidcontent'
    );
}
