<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

\FluidTYPO3\Flux\Core::registerConfigurationProvider('FluidTYPO3\Fluidcontent\Provider\ContentProvider');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin([
	'Fluid Content',
	'fluidcontent_content',
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('fluidcontent') . 'ext_icon.gif'
], \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT, 'FluidTYPO3.Fluidcontent');

if ('BE' === TYPO3_MODE) {
	/** @var \TYPO3\CMS\Core\Cache\CacheManager $cacheManager */
	$cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager')->get('TYPO3\CMS\Core\Cache\CacheManager');

	if (TRUE === $cacheManager->hasCache('fluidcontent') && TRUE === $cacheManager->getCache('fluidcontent')->has('pageTsConfig')) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig($cacheManager->getCache('fluidcontent')->get('pageTsConfig'));
	}
	unset($cacheManager);
}

