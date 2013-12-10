<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

define('FLUIDCONTENT_TEMPFILE', \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('typo3temp/.FED_CONTENT'));
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidcontent']['setup'] = unserialize($_EXTCONF);

\FluidTYPO3\Flux\Core::unregisterConfigurationProvider('Tx_Fed_Provider_Configuration_ContentObjectConfigurationProvider');
\FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidcontent\Provider\ContentProvider');

\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA('tt_content');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('Fluid Content', 'fluidcontent_content', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('fluidcontent') . 'ext_icon.gif'), 'CType');
//\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Fluid Content'); // Disabled temporarily: fluidcontent currently does not use TS configuration.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', array(
	'tx_fed_fcefile' => array (
		'exclude' => 1,
		'label' => 'LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xml:tt_content.tx_fed_fcefile',
		'config' => array (
			'type' => 'user',
			'userFunc' => 'FluidTYPO3\Fluidcontent\Backend\ContentSelector->renderField',
		)
	),
), 1);

if (FALSE === isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidcontent']['setup']['removeTab']) || TRUE === (boolean) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fluidcontent']['setup']['removeTab']) {
	$tab = NULL;
} else {
	$tab = '--div--;LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xml:pages.tab.content_settings,';
}

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['fluidcontent_content'] = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = '
	--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
	--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
	' . $tab . '
	tx_fed_fcefile;LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xml:pages.tab.element_type,
	pi_flexform;LLL:EXT:fluidcontent/Resources/Private/Language/locallang.xml:pages.tab.configuration,
	--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
	--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
	--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
	--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
	--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
	--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended,
	--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.extended;extended
	 ';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['fluidcontent_content'] = 'apps-pagetree-root';

if (file_exists(FLUIDCONTENT_TEMPFILE)) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(file_get_contents(FLUIDCONTENT_TEMPFILE));
}

unset($tab);
