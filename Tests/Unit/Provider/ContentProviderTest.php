<?php
namespace FluidTYPO3\Fluidcontent\Tests\Unit\Provider;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Provider\ContentProvider;
use FluidTYPO3\Fluidcontent\Service\ConfigurationService;
use FluidTYPO3\Flux\Configuration\BackendConfigurationManager;
use FluidTYPO3\Flux\Configuration\ConfigurationManager;
use FluidTYPO3\Flux\Service\FluxService;
use FluidTYPO3\Flux\Service\WorkspacesAwareRecordService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\PreparedStatement;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ContentProviderTest
 */
class ContentProviderTest extends UnitTestCase
{

    /**
     * @return ContentProvider
     */
    protected function createProviderInstance()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMockBuilder(DatabaseConnection::class)
            ->setMethods(['prepare_SELECTquery', 'exec_SELECTgetSingleRow', 'exec_SELECTgetRows', 'exec_SELECTquery'])
            ->disableOriginalConstructor()
            ->getMock();
        $preparedStatementMock = $this->getMockBuilder(PreparedStatement::class)
            ->setMethods(['execute', 'fetch', 'free'])
            ->disableOriginalConstructor()
            ->getMock();
        $preparedStatementMock->expects($this->any())->method('execute')->willReturn(false);
        $preparedStatementMock->expects($this->any())->method('free');
        $preparedStatementMock->expects($this->any())->method('fetch')->willReturn(false);
        $GLOBALS['TYPO3_DB']->expects($this->any())->method('prepare_SELECTquery')->willReturn($preparedStatementMock);
        $instance = $this->getMockBuilder(ContentProvider::class)->setMethods(['getPreview', 'getTemplatePaths'])->getMock();
        $instance->expects($this->any())->method('getTemplatePaths')->willReturn(['templateRootPaths' => ['EXT:fluidcontent/Resources/Private/Templates/']]);
        $instance->expects($this->any())->method('getPreview')->willReturn(['preview', true]);
        $configurationServiceMock = $this->getMockBuilder(ConfigurationService::class)->setMethods(['translateLabel', 'getContentConfiguration'])->getMock();
        $configurationServiceMock->expects($this->any())->method('getContentConfiguration')->willReturn([
            'fluidcontent' => [
                'templateRootPaths' => [
                    'EXT:fluidcontent/Tests/Fixtures/Templates/'
                ]
            ]
        ]);
        $instance->injectConfigurationService($configurationServiceMock);
        return $instance;
    }

    /**
     * @test
     */
    public function testPerformsInjections()
    {
        $instance = GeneralUtility::makeInstance(ObjectManager::class)->get(ContentProvider::class);
        $this->assertAttributeInstanceOf(
            ConfigurationManagerInterface::class,
            'configurationManager',
            $instance
        );
        $this->assertAttributeInstanceOf(
            ConfigurationService::class,
            'contentConfigurationService',
            $instance
        );
    }

    /**
     * @dataProvider getTemplatePathAndFilenameTestValues
     * @param array $record
     * @param string $expected
     */
    public function testGetTemplatePathAndFilename(array $record, $expected)
    {
        $GLOBALS['TYPO3_LOADED_EXT'] = [];
        $instance = $this->createProviderInstance();
        $result = $instance->getTemplatePathAndFilename($record);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getTemplatePathAndFilenameTestValues()
    {
        $path = ExtensionManagementUtility::extPath('fluidcontent');
        $file = $path . 'Resources/Private/Templates/Content/Error.html';
        return [
            [['uid' => 0], $file],
            [['tx_fed_fcefile' => 'test:Error.html'], $file],
            [['tx_fed_fcefile' => 'fluidcontent:Error.html'], $file],
        ];
    }

    /**
     * @dataProvider getTemplatePathAndFilenameOverrideTestValues
     * @param string $template
     * @param string $expected
     */
    public function testGetTemplatePathAndFilenameWithOverride($template, $expected)
    {
        $instance = $this->createProviderInstance();
        $instance->setTemplatePathAndFilename($template);
        $result = $instance->getTemplatePathAndFilename([]);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getTemplatePathAndFilenameOverrideTestValues()
    {
        $path = ExtensionManagementUtility::extPath('fluidcontent');
        return [
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
                null,
            ]
        ];
    }

    /**
     * @dataProvider getControllerExtensionKeyFromRecordTestValues
     * @param array $record
     * @param $expected
     */
    public function testGetControllerExtensionKeyFromRecord(array $record, $expected)
    {
        $instance = $this->createProviderInstance();
        $result = $instance->getControllerExtensionKeyFromRecord($record);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getControllerExtensionKeyFromRecordTestValues()
    {
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
    public function testGetControllerActionFromRecord(array $record, $expected)
    {
        $instance = $this->createProviderInstance();
        $result = $instance->getControllerActionFromRecord($record);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getControllerActionFromRecordTestValues()
    {
        return [
            [['uid' => 0], 'error'],
            [['tx_fed_fcefile' => 'test:test'], 'test'],
        ];
    }

    /**
     * @dataProvider getPriorityTestValues
     * @param array $record
     * @param $expected
     */
    public function testGetPriority(array $record, $expected)
    {
        $instance = $this->createProviderInstance();
        $result = $instance->getPriority($record);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getPriorityTestValues()
    {
        return [
            [['uid' => 0], 0],
            [['tx_fed_fcefile' => 'test:test'], 0],
            [['tx_fed_fcefile' => 'test:test', 'CType' => 'fluidcontent_content'], 100],
        ];
    }

    /**
     * @test
     * @dataProvider getPreviewTestValues
     * @param array $record
     *
     * tests if defaut previews for content elements of different types
     * each with a tx_fed_tcefile defined
     */
    public function testGetPreviewForTextElement(array $record)
    {
        $instance = $this->createProviderInstance();
        $recordService = $this->getMockBuilder(WorkspacesAwareRecordService::class)->setMethods(['get'])->getMock();
        $instance->injectRecordService($recordService);
        $result = $instance->getPreview($record);
        $this->assertEquals(['preview', true], $result);
    }

    public function getPreviewTestValues()
    {
        return [
            [
                [
                    'uid' => 1,
                    'CType' => 'text',
                    'header' => 'this is a simple text element',
                    'tx_fed_tcefile' => 'dummy-fed-file.txt'
                ]
            ],
            [
                [
                    'uid' => 1,
                    'CType' => 'fluidcontent_content',
                    'header' => 'this is a simple text element',
                    'tx_fed_tcefile' => 'dummy-fed-file.txt'
                ]
            ]
        ];
    }
}
