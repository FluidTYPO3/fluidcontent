<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'FluidTYPO3.Fluidcontent',
        'Content',
        [
            'Content' => 'render',
        ],
        [],
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    \FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidcontent\Provider\ContentProvider');
    \FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidcontent\Provider\BackendUserGroupProvider');

    if ('BE' === TYPO3_MODE) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook']['fluidcontent'] = 'FluidTYPO3\Fluidcontent\Hooks\WizardItemsHookSubscriber';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['fetchGroups_postProcessing']['fluidcontent'] = 'FluidTYPO3\Fluidcontent\Hooks\GroupAccessListPostProcessor->addAccessLists';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing']['fluidcontent'] = 'FluidTYPO3\Fluidcontent\Backend\TableConfigurationPostProcessor';
    }
}

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluidcontent'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluidcontent'] = [
        'groups' => ['system'],
    ];
}
