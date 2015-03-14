<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

\FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidcontent\Provider\ContentProvider');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array(
	'Fluid Content',
	'fluidcontent_content',
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('fluidcontent') . 'ext_icon.gif'
), \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT, 'FluidTYPO3.Fluidcontent');

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
// Remove bodytext RTE with TCA pointing to locallang file in xlf format
$GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = str_replace('bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext_formlabel;;richtext:rte_transform[flag=rte_enabled|mode=ts_css],', '', $GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem']);
$GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = str_replace('rte_enabled;LLL:EXT:cms/locallang_ttc.xlf:rte_enabled_formlabel,', '', $GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem']);
// Remove bodytext RTE with TCA pointing to locallang file in xml format
$GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = str_replace('bodytext;LLL:EXT:cms/locallang_ttc.xml:bodytext_formlabel;;richtext:rte_transform[flag=rte_enabled|mode=ts_css],', '', $GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem']);
$GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem'] = str_replace('rte_enabled;LLL:EXT:cms/locallang_ttc.xml:rte_enabled_formlabel,', '', $GLOBALS['TCA']['tt_content']['types']['fluidcontent_content']['showitem']);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['fluidcontent_content'] = 'apps-pagetree-root';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('tt_content', 'general', 'tx_fed_fcefile', 'after:CType');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'pi_flexform', 'fluidcontent_content', 'after:header');

if ('BE' === TYPO3_MODE) {
	/** @var \TYPO3\CMS\Core\Cache\CacheManager $cacheManager */
	$cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager')->get('TYPO3\CMS\Core\Cache\CacheManager');

	if (TRUE === $cacheManager->hasCache('fluidcontent') && TRUE === $cacheManager->getCache('fluidcontent')->has('pageTsConfig')) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig($cacheManager->getCache('fluidcontent')->get('pageTsConfig'));
	}
	unset($cacheManager);
}
