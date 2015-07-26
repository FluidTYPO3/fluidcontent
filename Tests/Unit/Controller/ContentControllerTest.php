<?php
namespace FluidTYPO3\Fluidcontent\Tests\Unit\Controller;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ContentControllerTest
 */
class ContentControllerTest extends UnitTestCase {

	public function testCreatesInstance() {
		$GLOBALS['TYPO3_DB'] = $this->getMock(
			'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
			['prepare_SELECTquery'],
			[], '', FALSE
		);
		$preparedStatementMock = $this->getMock(
			'TYPO3\\CMS\\Core\\Database\\PreparedStatement',
			['execute', 'fetch', 'free'],
			[], '', FALSE
		);
		$preparedStatementMock->expects($this->any())->method('execute')->willReturn(FALSE);
		$preparedStatementMock->expects($this->any())->method('free');
		$preparedStatementMock->expects($this->any())->method('fetch')->willReturn(FALSE);;
		$GLOBALS['TYPO3_DB']->expects($this->any())->method('prepare_SELECTquery')->willReturn($preparedStatementMock);
		$instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
			->get('FluidTYPO3\\Fluidcontent\\Controller\\ContentController');
		$this->assertInstanceOf('FluidTYPO3\\Fluidcontent\\Controller\\ContentController', $instance);
	}

	public function testInitializeView() {
		$instance = $this->getMock(
			'FluidTYPO3\\Fluidcontent\\Controller\\ContentController',
			[
				'getRecord', 'initializeProvider', 'initializeSettings', 'initializeOverriddenSettings',
				'initializeViewObject', 'initializeViewVariables'
			]
		);
		$configurationManager = $this->getMock(
			'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager',
			['getContentObject', 'getConfiguration']
		);
		$contentObject = new \stdClass();
		$configurationManager->expects($this->once())->method('getContentObject')->willReturn($contentObject);
		$configurationManager->expects($this->once())->method('getConfiguration')->willReturn(['foo' => 'bar']);
		$instance->expects($this->once())->method('getRecord')->willReturn(['uid' => 0]);
		$GLOBALS['TSFE'] = (object) ['page' => 'page', 'fe_user' => (object) ['user' => 'user']];
		$view = $this->getMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView', ['assign']);
		$instance->injectConfigurationManager($configurationManager);
		$view->expects($this->at(0))->method('assign')->with('page', 'page');
		$view->expects($this->at(1))->method('assign')->with('user', 'user');
		$view->expects($this->at(2))->method('assign')->with('record', ['uid' => 0]);
		$view->expects($this->at(3))->method('assign')->with('contentObject', $contentObject);
		$view->expects($this->at(4))->method('assign')->with('cookies', $_COOKIE);
		$view->expects($this->at(5))->method('assign')->with('session', $_SESSION);
		$instance->initializeView($view);
	}

}
