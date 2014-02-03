<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

define('FLUIDCONTENT_TEMPFILE', \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('typo3temp/.FED_CONTENT'));

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

$GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = $GLOBALS['TCA']['tt_content']['types']['text']['showitem'];
$GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = str_replace('bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext_formlabel;;richtext:rte_transform[flag=rte_enabled|mode=ts_css],', '', $GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem']);
$GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = str_replace('rte_enabled;LLL:EXT:cms/locallang_ttc.xlf:rte_enabled_formlabel,', '', $GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem']);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['fluidcontent_content'] = 'apps-pagetree-root';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_fed_fcefile,pi_flexform', 'fluidcontent_content', 'after:header');

if (file_exists(FLUIDCONTENT_TEMPFILE)) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(file_get_contents(FLUIDCONTENT_TEMPFILE));
}
