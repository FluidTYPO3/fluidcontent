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
  'state' => 'beta',
  'internal' => '',
  'uploadfolder' => 0,
  'createDirs' => '',
  'modify_tables' => '',
  'clearCacheOnLoad' => 1,
  'lockType' => '',
  'version' => '5.0.0',
  'constraints' =>
  array (
    'depends' =>
    array (
      'php' => '7.0.0-7.1.99',
      'typo3' => '7.6.0-8.6.99',
      'flux' => '7.3.0-8.99.99',
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
  array(
      'psr-4' =>
      array(
          'FluidTYPO3\\Fluidcontent\\' => 'Classes/',
      ),
  ),
);
