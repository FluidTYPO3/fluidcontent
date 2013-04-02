<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Claus Due <claus@wildside.dk>, Wildside A/S
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

/**
 * Abstract Content Controller
 *
 * @package Fluidcontent
 * @subpackage Controller
 * @route off
 */
abstract class Tx_Fluidcontent_Controller_AbstractContentController extends Tx_Flux_Controller_AbstractFluxController implements Tx_Fluidcontent_Controller_ContentControllerInterface {

	/**
	 * @var string
	 */
	protected $fallbackExtensionKey = 'fluidcontent';

	/**
	 * @var Tx_Fluidcontent_Service_ConfigurationService
	 */
	protected $configurationService;

	/**
	 * @param Tx_Fluidcontent_Service_ConfigurationService $configurationService
	 * @return void
	 */
	public function injectConfigurationService(Tx_Fluidcontent_Service_ConfigurationService $configurationService) {
		$this->configurationService = $configurationService;
	}

	/**
	 * @param Tx_Extbase_MVC_View_ViewInterface $view
	 * @return void
	 */
	public function initializeView(Tx_Extbase_MVC_View_ViewInterface $view) {
		parent::initializeView($view);
		$view->assign('page', $GLOBALS['TSFE']->page);
		$view->assign('user', $GLOBALS['TSFE']->fe_user->user);
		$view->assign('record', $this->getRecord());
		$view->assign('contentObject', $this->configurationManager->getContentObject());
		$view->assign('cookies', $_COOKIE);
		$view->assign('session', $_SESSION);
	}

}