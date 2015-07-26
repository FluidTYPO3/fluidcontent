<?php
namespace FluidTYPO3\Fluidcontent\Tests\Unit\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Form\Container\Column;
use FluidTYPO3\Flux\Form\Container\Grid;
use FluidTYPO3\Flux\Form\Container\Row;
use FluidTYPO3\Flux\Provider\Provider;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class WizardItemsHookSubscriberTest
 */
class WizardItemsHookSubscriberTest extends UnitTestCase {

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
			->get('FluidTYPO3\\Fluidcontent\\Hooks\\WizardItemsHookSubscriber');
		$this->assertInstanceOf('FluidTYPO3\\Fluidcontent\\Hooks\\WizardItemsHookSubscriber', $instance);
	}

	/**
	 * @dataProvider getTestElementsWhiteAndBlackListsAndExpectedList
	 * @test
	 * @param array $items
	 * @param string $whitelist
	 * @param string $blacklist
	 * @param array $expectedList
	 */
	public function processesWizardItems($items, $whitelist, $blacklist, $expectedList) {
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$instance = $objectManager->get('FluidTYPO3\\Fluidcontent\\Hooks\\WizardItemsHookSubscriber');
		$emulatedPageAndContentRecord = ['uid' => 1, 'tx_flux_column' => 'name'];
		$controller = new NewContentElementController();
		$controller->colPos = 0;
		$controller->uid_pid = -1;
		$grid = new Grid();
		$row = new Row();
		$column = new Column();
		$column->setColumnPosition(0);
		$column->setName('name');
		$column->setVariable('Fluidcontent', [
			'allowedContentTypes' => $whitelist,
			'deniedContentTypes' => $blacklist
		]);
		$row->add($column);
		$grid->add($row);
		$provider1 = $objectManager->get('FluidTYPO3\\Flux\\Provider\\Provider');
		$provider1->setTemplatePaths([]);
		$provider1->setTemplateVariables([]);
		$provider1->setGrid($grid);
		$provider2 = $this->getMock('FluidTYPO3\\Flux\\Provider\\Provider', ['getGrid']);
		$provider2->expects($this->exactly(1))->method('getGrid')->will($this->returnValue(NULL));
		$configurationService = $this->getMock(
			'FluidTYPO3\\Fluidcontent\\Service\\ConfigurationService',
			['resolveConfigurationProviders', 'writeCachedConfigurationIfMissing']
		);
		$configurationService->expects($this->exactly(1))->method('resolveConfigurationProviders')
			->will($this->returnValue([$provider1, $provider2]));
		$recordService = $this->getMock('FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService', ['getSingle']);
		$recordService->expects($this->exactly(2))->method('getSingle')->will($this->returnValue($emulatedPageAndContentRecord));
		$instance->injectConfigurationService($configurationService);
		$instance->injectRecordService($recordService);
		$instance->manipulateWizardItems($items, $controller);
		$this->assertEquals($expectedList, $items);
	}

	/**
	 * @return array
	 */
	public function getTestElementsWhiteAndBlackListsAndExpectedList() {
		$items = [
			'plugins' => ['title' => 'Nice header'],
			'plugins_test1' => [
				'tt_content_defValues' => ['CType' => 'fluidcontent_content', 'tx_fed_fcefile' => 'test1:test1']
			],
			'plugins_test2' => [
				'tt_content_defValues' => ['CType' => 'fluidcontent_content', 'tx_fed_fcefile' => 'test2:test2']
			]
		];
		return [
			[
				$items,
				NULL,
				NULL,
				$items,
			],
			[
				$items,
				'test1:test1',
				NULL,
				[
					'plugins' => ['title' => 'Nice header'],
					'plugins_test1' => $items['plugins_test1']
				],
			],
			[
				$items,
				NULL,
				'test1:test1',
				[
					'plugins' => ['title' => 'Nice header'],
					'plugins_test2' => $items['plugins_test2']
				],
			],
			[
				$items,
				'test1:test1',
				'test1:test1',
				[],
			],
		];
	}

	public function testManipulateWizardItemsCallsExpectedMethodSequenceWithoutProviders() {
		$instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
			->get('FluidTYPO3\\Fluidcontent\\Hooks\\WizardItemsHookSubscriber');
		$configurationService = $this->getMock(
			'FluidTYPO3\\Fluidcontent\\Service\\ConfigurationService',
			['writeCachedConfigurationIfMissing', 'resolveConfigurationProviders']
		);
		$recordService = $this->getMock(
			'FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService',
			['getSingle']
		);
		$configurationService->expects($this->once())->method('writeCachedConfigurationIfMissing');
		$configurationService->expects($this->once())->method('resolveConfigurationProviders')->willReturn([]);
		$recordService->expects($this->once())->method('getSingle')->willReturn(NULL);
		$instance->injectConfigurationService($configurationService);
		$instance->injectRecordService($recordService);
		$parent = new NewContentElementController();
		$items = [];
		$instance->manipulateWizardItems($items, $parent);
	}

	public function testManipulateWizardItemsCallsExpectedMethodSequenceWithProvidersWithColPosWithoutRelativeElement() {
		$instance = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
			->get('FluidTYPO3\\Fluidcontent\\Hooks\\WizardItemsHookSubscriber');
		$configurationService = $this->getMock(
			'FluidTYPO3\\Fluidcontent\\Service\\ConfigurationService',
			['writeCachedConfigurationIfMissing', 'resolveConfigurationProviders']
		);
		$recordService = $this->getMock(
			'FluidTYPO3\\Flux\\Service\\WorkspacesAwareRecordService',
			['getSingle']
		);
		$record = ['uid' => 0];
		$provider1 = $this->getMockProvider($record);
		$provider2 = $this->getMockProvider($record);
		$provider3 = $this->getMockProvider($record, FALSE);
		$configurationService->expects($this->once())->method('writeCachedConfigurationIfMissing');
		$configurationService->expects($this->once())->method('resolveConfigurationProviders')->willReturn([
			$provider1, $provider2, $provider3
		]);
		$recordService->expects($this->once())->method('getSingle')->willReturn($record);
		$instance->injectConfigurationService($configurationService);
		$instance->injectRecordService($recordService);
		$parent = new NewContentElementController();
		$parent->colPos = 1;
		$items = [];
		$instance->manipulateWizardItems($items, $parent);
	}

	/**
	 * @param array $record
	 * @param boolean $withGrid
	 * @return Provider
	 */
	protected function getMockProvider(array $record, $withGrid = TRUE) {
		$instance = $this->getMock('FluidTYPO3\\Flux\\Provider\\Provider', ['getViewVariables', 'getGrid']);
		if (FALSE === $withGrid) {
			$instance->expects($this->any())->method('getGrid')->willReturn($grid);
		} else {
			$grid = Grid::create();
			$grid->createContainer('Row', 'row')->createContainer('Column', 'column')->setColumnPosition(1)
				->setVariable('Fluidcontent', ['deniedContentTypes' => 'html', 'allowedContentTypes' => 'text']);
			$instance->expects($this->any())->method('getGrid')->willReturn($grid);
		}
		return $instance;
	}

}
