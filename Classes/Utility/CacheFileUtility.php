<?php
namespace FluidTYPO3\Fluidcontent\Utility;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Fabien Udriot <fabien.udriot@ecodev.ch>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Caching file utility
 *
 * Provides Utility method for managing the caching file.
 *
 * @author Fabien Udriot
 * @package Fluidcontent
 * @subpackage Utility
 */
class CacheFileUtility implements SingletonInterface {

	/**
	 * @var string
	 */
	protected $fileNameAndPath;

	/**
	 * Returns a class instance.
	 *
	 * @return CacheFileUtility
	 */
	static public function getInstance() {
		return GeneralUtility::makeInstance('FluidTYPO3\Fluidcontent\Utility\CacheFileUtility');
	}

	/**
	 * Tell whether the caching file exist or not.
	 *
	 * @return boolean
	 */
	public function exists(){
		return file_exists($this->getFileNameAndPath());
	}

	/**
	 * Return the file name and path of the caching file.
	 *
	 * @return string
	 */
	public function getFileNameAndPath() {
		if (TRUE === is_null($this->fileNameAndPath)) {
			$this->fileNameAndPath = GeneralUtility::getFileAbsFileName('typo3temp/.FED_CONTENT');
		}
		return $this->fileNameAndPath;
	}

	/**
	 * Return the content of the caching file.
	 *
	 * @return string
	 */
	public function getContent() {
		$result = '';
		if ($this->exists()) {
			$result = file_get_contents($this->getFileNameAndPath());
		}
		return $result;
	}

}
