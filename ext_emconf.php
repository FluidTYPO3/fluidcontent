<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "fluidcontent".
 *
 * Auto generated 30-06-2013 21:39
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Fluid Content Engine',
	'description' => 'Fluid Content Element engine - integrates extremely compact and highly dynamic content element templates written in Fluid. See: https://github.com/FluidTYPO3/fluidcontent',
	'category' => 'misc',
	'author' => 'Claus Due',
	'author_email' => 'claus@namelesscoder.net',
	'author_company' => 'Wildside A/S',
	'shy' => '',
	'dependencies' => 'cms,flux',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'version' => '4.0-dev',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.2.99',
			'cms' => '',
			'flux' => '6.0.0-6.0.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:24:{s:20:"class.ext_update.php";s:4:"8bec";s:16:"ext_autoload.php";s:4:"7e9d";s:21:"ext_conf_template.txt";s:4:"e7db";s:12:"ext_icon.gif";s:4:"68b4";s:17:"ext_localconf.php";s:4:"2230";s:14:"ext_tables.php";s:4:"3a9e";s:14:"ext_tables.sql";s:4:"3b19";s:9:"README.md";s:4:"b009";s:35:"Classes/Backend/ContentSelector.php";s:4:"d462";s:48:"Classes/Controller/AbstractContentController.php";s:4:"9d3a";s:40:"Classes/Controller/ContentController.php";s:4:"84dc";s:49:"Classes/Controller/ContentControllerInterface.php";s:4:"be93";s:49:"Classes/Provider/ContentConfigurationProvider.php";s:4:"e394";s:40:"Classes/Service/ConfigurationService.php";s:4:"7856";s:47:"Classes/UserFunction/ContentFieldSuppressor.php";s:4:"68f3";s:34:"Configuration/TypoScript/setup.txt";s:4:"53ca";s:33:"Documentation/ComplexityChart.png";s:4:"638f";s:30:"Documentation/PyramidChart.png";s:4:"d565";s:40:"Resources/Private/Language/locallang.xml";s:4:"fb03";s:38:"Resources/Private/Layouts/Content.html";s:4:"da94";s:34:"Resources/Private/Layouts/FCE.html";s:4:"5a0f";s:46:"Resources/Private/Templates/Content/Error.html";s:4:"d41d";s:47:"Resources/Private/Templates/Content/Render.html";s:4:"d41d";s:33:"Resources/Public/Icons/Plugin.png";s:4:"50ed";}',
);
