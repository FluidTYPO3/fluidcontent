<?php
// Register composer autoloader
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
	throw new \RuntimeException(
		'Could not find vendor/autoload.php, make sure you ran composer.'
	);
}

/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = require __DIR__ . '/../vendor/autoload.php';

\FluidTYPO3\Development\Bootstrap::initialize(
	$autoloader,
	array(
		'fluid_template' => \FluidTYPO3\Development\Bootstrap::CACHE_PHP_NULL,
		'cache_core' => \FluidTYPO3\Development\Bootstrap::CACHE_PHP_NULL,
		'cache_rootline' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'cache_runtime' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'extbase_object' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'extbase_datamapfactory_datamap' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'extbase_typo3dbbackend_tablecolumns' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'extbase_typo3dbbackend_queries' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'fluidcontent' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
		'l10n' => \FluidTYPO3\Development\Bootstrap::CACHE_NULL,
	)
);

/** @var $extbaseObjectContainer \TYPO3\CMS\Extbase\Object\Container\Container */
$extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\Container\Container');
$extbaseObjectContainer->registerImplementation('TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface', 'FluidTYPO3\Flux\Configuration\ConfigurationManager');
