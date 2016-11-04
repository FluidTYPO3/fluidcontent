<?php
namespace FluidTYPO3\Fluidcontent\Tests\Unit\Service;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Service\ConfigurationService;
use FluidTYPO3\Flux\Configuration\ConfigurationManager;
use FluidTYPO3\Flux\Core;
use FluidTYPO3\Flux\Form;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ConfigurationServiceTest
 */
class ConfigurationServiceTest extends UnitTestCase {

	public function testGetContentConfiguration() {
		Core::registerProviderExtensionKey('FluidTYPO3.Fluidcontent', 'Content');
		/** @var ConfigurationService $service */
		$service = $this->getMock('FluidTYPO3\\Fluidcontent\\Service\\ConfigurationService', array('dummy'), array(), '', FALSE);
		$service->injectConfigurationManager(GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
			->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface'));
		$result = $service->getContentConfiguration();
		$this->assertEquals(array(
			'FluidTYPO3.Fluidcontent' => array(
				'templateRootPaths' => array('EXT:fluidcontent/Resources/Private/Templates/'),
				'partialRootPaths' => array('EXT:fluidcontent/Resources/Private/Partials/'),
				'layoutRootPaths' => array('EXT:fluidcontent/Resources/Private/Layouts/'),
			)
		), $result);
	}

	public function testWriteCachedConfigurationIfMissing() {
		/** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $service */
		$service = $this->getMock(
			'FluidTYPO3\\Fluidcontent\\Service\\ConfigurationService',
            array('getPageTsConfig'),
            array(), '', FALSE
		);
        $service->expects($this->any())->method('getPageTsConfig')->willReturn('test');
        $service->writeCachedConfigurationIfMissing();
	}

	public function testBuildAllWizardTabsPageTsConfig() {
		$tabs = array(
			'tab1' => array(
				'title' => 'Tab 1',
				'key' => 'tab1',
				'elements' => array(
					'a,b,c'
				)
			),
			'tab2' => array(
				'title' => 'Tab 2',
				'key' => 'tab2',
				'elements' => array(
					'a,b,c'
				)
			)
		);
		$service = new ConfigurationService();
		$result = $this->callInaccessibleMethod($service, 'buildAllWizardTabsPageTsConfig', $tabs);
		foreach ($tabs as $tabId => $tab) {
			$this->assertContains($tabId, $result);
			$this->assertContains($tab['title'], $result);
			$this->assertContains($tab['key'], $result);
		}
	}

	public function testRenderWizardTabItem() {
		$form = Form::create();
		$form->setLabel('bazlabel');
		$form->setDescription('foobar');
		$service = $this->getMock('FluidTYPO3\\Fluidcontent\\Service\\ConfigurationService', array(), array(), '', FALSE);
		$result = $this->callInaccessibleMethod($service, 'buildWizardTabItem', 'tabid', 'id', $form, '');
		$this->assertContains('tabid.elements.id', $result);
		$this->assertContains('title = bazlabel', $result);
		$this->assertContains('description = foobar', $result);
	}

	/**
	 * @test
	 * @dataProvider getSanitizeStringTestValues
	 * @param string $input
	 * @param string $expected
	 */
	public function testSanitizeString($input, $expected) {
		$service = $this->getMock('FluidTYPO3\\Fluidcontent\\Service\\ConfigurationService', array(), array(), '', FALSE);
		$result = $this->callInaccessibleMethod($service, 'sanitizeString', $input);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getSanitizeStringTestValues() {
		return array(
			array('foo bar', 'foo-bar')
		);
	}

	/**
	 * @return void
	 */
	public function testGetContentElementFormInstances() {
		$class = substr(str_replace('Tests\\Unit\\', '', get_class($this)), 0, -4);
		/** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $mock */
		$mock = $this->getMock($class, array('getContentConfiguration', 'message'));
		/** @var ObjectManager $objectManager */
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$mock->injectObjectManager($objectManager);
		$mock->expects($this->once())->method('getContentConfiguration')->willReturn(array(
			'fluidcontent' => array(
				'templateRootPath' => 'EXT:fluidcontent/Tests/Fixtures/Templates/'
			)
		));
		$mock->expects($this->exactly(2))->method('message');
		$result = $mock->getContentElementFormInstances();
		$this->assertInstanceOf('FluidTYPO3\\Flux\\Form', $result['fluidcontent']['fluidcontent_DummyContent_html']);
	}

	/**
	 * @return void
	 */
	public function testBuildAllWizardTabGroups() {
		$class = substr(str_replace('Tests\\Unit\\', '', get_class($this)), 0, -4);
		/** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $mock */
		$mock = $this->getMock($class, array('getContentConfiguration', 'message'));
		/** @var ObjectManager $objectManager */
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$mock->injectObjectManager($objectManager);
		$paths = array(
			'fluidcontent' => array(
				'templateRootPath' => 'EXT:fluidcontent/Tests/Fixtures/Templates/'
			)
		);
		$mock->expects($this->once())->method('getContentConfiguration')->willReturn($paths);
		$mock->expects($this->exactly(2))->method('message');
		$result = $this->callInaccessibleMethod($mock, 'buildAllWizardTabGroups', $paths);
		$this->assertArrayHasKey('Content', $result);
		$this->assertEquals('Content', $result['Content']['title']);
		$this->assertArrayHasKey('fluidcontent_DummyContent_html', $result['Content']['elements']);
	}

	/**
	 * @dataProvider getTestRenderPageTypoScriptTestValues
	 * @param $pageUid
	 */
	public function testRenderPageTypoScriptForPageUidCreatesExpectedTypoScript($pageUid) {
		$class = substr(str_replace('Tests\\Unit\\', '', get_class($this)), 0, -4);
		$instance = $this->getMock(
			$class,
			array(
				'overrideCurrentPageUidForConfigurationManager',
				'getContentConfiguration',
				'buildAllWizardTabGroups',
				'buildAllWizardTabsPageTsConfig',
				'message'
			)
		);
		$instance->expects($this->at(0))->method('overrideCurrentPageUidForConfigurationManager')->with($pageUid);
		$instance->expects($this->at(1))->method('getContentConfiguration')->willReturn(array('foo' => 'bar'));
		$instance->expects($this->at(2))->method('buildAllWizardTabGroups')->with(array('foo' => 'bar'))->willReturn(array());
		$instance->expects($this->at(3))->method('buildAllWizardTabsPageTsConfig')->with(array())->willReturn('targetmarker');
		$instance->expects($this->at(4))->method('message');
		$result = $this->callInaccessibleMethod($instance, 'renderPageTypoScriptForPageUid', $pageUid);
		$this->assertContains('targetmarker', $result);
	}

	/**
	 * @return array
	 */
	public function getTestRenderPageTypoScriptTestValues() {
		return array(
			array(1),
			array(2)
		);
	}

	/**
	 * @return void
	 */
	public function testRenderPageTypoScriptForPageUidDelegatesExceptionsToDebug() {
		$class = substr(str_replace('Tests\\Unit\\', '', get_class($this)), 0, -4);
		$instance = $this->getMock($class, array('getContentConfiguration', 'debug', 'message'));
		$instance->expects($this->once())->method('getContentConfiguration')
			->willThrowException(new \RuntimeException('test'));
		$instance->expects($this->never())->method('message');
		$instance->expects($this->once())->method('debug');
		$this->callInaccessibleMethod($instance, 'renderPageTypoScriptForPageUid', 0, array());
	}

	/**
	 * @return void
	 */
	public function testConfigurationManagerOverrides() {
		$instance = new ConfigurationService();
		/** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $mock */
		$mock = $this->getMock(
			'FluidTYPO3\\Flux\\Configuration\\ConfigurationManager',
			array('setCurrentPageUid', 'getCurrentPageId')
		);
		$mock->expects($this->at(0))->method('setCurrentPageUid')->with(1);
		$mock->expects($this->at(1))->method('getCurrentPageId')->willReturn(2);
		$mock->expects($this->at(2))->method('setCurrentPageUid')->with(2);
		$instance->injectConfigurationManager($mock);
		$this->callInaccessibleMethod($instance, 'overrideCurrentPageUidForConfigurationManager', 1);
		$this->callInaccessibleMethod($instance, 'backupPageUidForConfigurationManager');
		$this->callInaccessibleMethod($instance, 'restorePageUidForConfigurationManager');
	}

}
