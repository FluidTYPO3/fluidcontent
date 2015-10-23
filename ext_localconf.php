<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'FluidTYPO3.Fluidcontent',
	'Content',
	array(
		'Content' => 'render',
	),
	array(
	),
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

\FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidcontent\Provider\ContentProvider');

\FluidTYPO3\Flux\Utility\CompatibilityRegistry::register(
	'FluidTYPO3\\Fluidcontent\\Service\\WizardTabService',
	array(
		'6.2.0' => 'FluidTYPO3\\Fluidcontent\\Service\\LegacyWizardTabService',
		'7.5.0' => 'FluidTYPO3\\Fluidcontent\\Service\\WizardTabService'
	)
);

if ('BE' === TYPO3_MODE) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook']['fluidcontent'] = 'FluidTYPO3\Fluidcontent\Hooks\WizardItemsHookSubscriber';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing']['fluidcontent'] = 'FluidTYPO3\Fluidcontent\Backend\TableConfigurationPostProcessor';
}

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluidcontent'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluidcontent'] = array(
		'groups' => array('system')
	);
}
