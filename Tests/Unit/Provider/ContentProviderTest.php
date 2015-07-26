<?php
namespace FluidTYPO3\Fluidcontent\Tests\Unit\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Provider\ContentProvider;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ContentProviderTest
 */
class ContentProviderTest extends UnitTestCase {

	/**
	 * @return ContentProvider
	 */
	protected function createProviderInstance() {
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
			->get('FluidTYPO3\\Fluidcontent\\Provider\\ContentProvider');
		return $instance;
	}

	public function testPerformsInjections() {
		$instance = $this->createProviderInstance();
		$this->assertAttributeInstanceOf('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface', 'configurationManager', $instance);
		$this->assertAttributeInstanceOf('FluidTYPO3\\Fluidcontent\\Service\\ConfigurationService', 'configurationService', $instance);
	}

	/**
	 * @dataProvider getTemplatePathAndFilenameTestValues
	 * @param array $record
	 * @param string $expected
	 */
	public function testGetTemplatePathAndFilename(array $record, $expected) {
		$instance = $this->createProviderInstance();
		$result = $instance->getTemplatePathAndFilename($record);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getTemplatePathAndFilenameTestValues() {
		$path = ExtensionManagementUtility::extPath('fluidcontent');
		$file = $path . 'Resources/Private/Templates/Content/Index.html';
		return [
			[['uid' => 0], $file],
			[['tx_fed_fcefile' => 'test:Test.html'], NULL],
		];
	}

	/**
	 * @dataProvider getTemplatePathAndFilenameOverrideTestValues
	 * @param string $template
	 * @param string $expected
	 */
	public function testGetTemplatePathAndFilenameWithOverride($template, $expected) {
		$instance = $this->createProviderInstance();
		$instance->setTemplatePathAndFilename($template);
		$result = $instance->getTemplatePathAndFilename([]);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getTemplatePathAndFilenameOverrideTestValues() {
		$path = ExtensionManagementUtility::extPath('fluidcontent');
		return [
			[
				'EXT:fluidcontent/Resources/Private/Templates/Content/Index.html',
				$path . 'Resources/Private/Templates/Content/Index.html',
			],
			[
				$path . 'Resources/Private/Templates/Content/Index.html',
				$path . 'Resources/Private/Templates/Content/Index.html',
			],
			[
				'EXT:fluidcontent/Resources/Private/Templates/Content/Error.html',
				$path . 'Resources/Private/Templates/Content/Error.html',
			],
			[
				$path . 'Resources/Private/Templates/Content/Error.html',
				$path . 'Resources/Private/Templates/Content/Error.html',
			],
			[
				$path . '/Does/Not/Exist.html',
				NULL,
			]
		];
	}

	/**
	 * @dataProvider getControllerExtensionKeyFromRecordTestValues
	 * @param array $record
	 * @param $expected
	 */
	public function testGetControllerExtensionKeyFromRecord(array $record, $expected) {
		$instance = $this->createProviderInstance();
		$result = $instance->getControllerExtensionKeyFromRecord($record);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getControllerExtensionKeyFromRecordTestValues() {
		return [
			[['uid' => 0], 'Fluidcontent'],
			[['tx_fed_fcefile' => 'test:test'], 'test'],
		];
	}

	/**
	 * @dataProvider getControllerActionFromRecordTestValues
	 * @param array $record
	 * @param $expected
	 */
	public function testGetControllerActionFromRecord(array $record, $expected) {
		$instance = $this->createProviderInstance();
		$result = $instance->getControllerActionFromRecord($record);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getControllerActionFromRecordTestValues() {
		return [
			[['uid' => 0], 'index'],
			[['tx_fed_fcefile' => 'test:test'], 'test'],
		];
	}

	/**
	 * @dataProvider getPriorityTestValues
	 * @param array $record
	 * @param $expected
	 */
	public function testGetPriority(array $record, $expected) {
		$instance = $this->createProviderInstance();
		$result = $instance->getPriority($record);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getPriorityTestValues() {
		return [
			[['uid' => 0], 0],
			[['tx_fed_fcefile' => 'test:test'], 0],
			[['tx_fed_fcefile' => 'test:test', 'CType' => 'fluidcontent_content'], 100],
		];
	}

}
