<?php
$EM_CONF[$_EXTKEY] = array (
  'title' => 'Fluid Content Engine',
  'description' => 'Fluid Content Element engine - integrates extremely compact and highly dynamic content element templates written in Fluid. See: https://github.com/FluidTYPO3/fluidcontent',
  'category' => 'misc',
  'author' => 'FluidTYPO3 Team',
  'author_email' => 'claus@namelesscoder.net',
  'author_company' => '',
  'shy' => '',
  'dependencies' => 'flux',
  'conflicts' => '',
  'priority' => '',
  'module' => '',
  'state' => 'obsolete',
  'internal' => '',
  'uploadfolder' => 0,
  'createDirs' => '',
  'modify_tables' => '',
  'clearCacheOnLoad' => 1,
  'lockType' => '',
  'version' => '5.2.0',
  'constraints' => 
  array (
    'depends' => 
    array (
      'php' => '7.0.0-7.1.99',
      'typo3' => '8.7.0-9.2.99',
      'flux' => '8.2.0-9.99.99',
    ),
    'conflicts' => 
    array (
    ),
    'suggests' => 
    array (
    ),
  ),
  'suggests' => 
  array (
  ),
  '_md5_values_when_last_written' => '',
  'autoload' => 
  array (
    'psr-4' => 
    array (
      'FluidTYPO3\\Fluidcontent\\' => 'Classes/',
    ),
  ),
  'autoload-dev' => 
  array (
    'psr-4' => 
    array (
      'FluidTYPO3\\Fluidcontent\\Tests\\' => 'Tests/',
    ),
  ),
);
