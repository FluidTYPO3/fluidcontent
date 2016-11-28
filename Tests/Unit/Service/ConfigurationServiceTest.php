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
use FluidTYPO3\Flux\View\ExposedTemplateView;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class ConfigurationServiceTest
 */
class ConfigurationServiceTest extends UnitTestCase
{
    const CACHE_KEY_PAGETSCONFIG = 'pageTsConfig';

    public function testGetContentConfiguration()
    {
        $configuration = array(
            'templateRootPaths' => array('EXT:fluidcontent/Tests/Fixtures/Templates/'),
            'partialRootPaths' => array('EXT:fluidcontent/Resources/Private/Partials/'),
            'layoutRootPaths' => array('EXT:fluidcontent/Resources/Private/Layouts/'),
        );
        Core::registerProviderExtensionKey('FluidTYPO3.Fluidcontent', 'Content');
        /** @var ConfigurationService $service */
        $service = $this->getMockBuilder(ConfigurationService::class)
            ->setMethods(array('getViewConfigurationForExtensionName'))
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())->method('getViewConfigurationForExtensionName')->willReturn($configuration);
        $service->injectConfigurationManager(GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationManagerInterface::class));
        $result = $service->getContentConfiguration();
        $this->assertEquals(array(
            'FluidTYPO3.Fluidcontent' => $configuration
        ), $result);
    }

    public function testWriteCachedConfigurationIfMissing()
    {
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $service */
        $service = $this->getMockBuilder(ConfigurationService::class)
            ->setMethods(array('getPageTsConfig'))
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->any())->method('getPageTsConfig')->willReturn('test');
        $service->writeCachedConfigurationIfMissing();
    }

    public function testBuildAllWizardTabsPageTsConfig()
    {
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

    public function testRenderWizardTabItem()
    {
        $form = Form::create();
        $form->setLabel('bazlabel');
        $form->setDescription('foobar');
        $service = $this->getMockBuilder(ConfigurationService::class)->disableOriginalConstructor()->getMock();
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
    public function testSanitizeString($input, $expected)
    {
        $service = $this->getMockBuilder(ConfigurationService::class)->disableOriginalConstructor()->getMock();
        $result = $this->callInaccessibleMethod($service, 'sanitizeString', $input);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getSanitizeStringTestValues()
    {
        return array(
            array('foo bar', 'foo_bar')
        );
    }

    /**
     * @return void
     */
    public function testGetContentElementFormInstances()
    {
        $class = substr(str_replace('Tests\\Unit\\', '', get_class($this)), 0, -4);
        $view = $this->getMockBuilder(ExposedTemplateView::class)->setMethods(['setControllerContext', 'getStoredVariable'])->getMock();

        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->getMockBuilder($class)->setMethods(array('getContentConfiguration', 'message', 'getPreparedExposedTemplateView'))->getMock();
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $mock->injectObjectManager($objectManager);
        $view->expects($this->at(0))->method('getStoredVariable')->willReturn(Form::create(['enabled' => false]));
        $view->expects($this->at(1))->method('getStoredVariable')->willReturn(Form::create(['enabled' => true]));
        $view->expects($this->at(2))->method('getStoredVariable')->willReturn(null);
        $mock->expects($this->any())->method('getPreparedExposedTemplateView')->willReturn($view);
        $mock->expects($this->once())->method('getContentConfiguration')->willReturn(array(
            'fluidcontent' => array(
                'templateRootPaths' => [ExtensionManagementUtility::extPath('fluidcontent', 'Tests/Fixtures/Templates/')]
            )
        ));
        $mock->expects($this->exactly(2))->method('message');
        $result = $mock->getContentElementFormInstances();
        $this->assertInstanceOf(Form::class, $result['fluidcontent']['fluidcontent_DummyContent_html']);
    }

    /**
     * @return void
     */
    public function testBuildAllWizardTabGroups()
    {
        $class = substr(str_replace('Tests\\Unit\\', '', get_class($this)), 0, -4);
        $view = $this->getMockBuilder(ExposedTemplateView::class)->setMethods(['setControllerContext', 'getStoredVariable'])->getMock();
        $view->expects($this->at(0))->method('getStoredVariable')->willReturn(Form::create(['enabled' => false]));
        $view->expects($this->at(1))->method('getStoredVariable')->willReturn(Form::create(['enabled' => true]));
        $view->expects($this->at(2))->method('getStoredVariable')->willReturn(null);
        /** @var ConfigurationService|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->getMockBuilder($class)->setMethods(array('getContentConfiguration', 'message', 'translateLabel', 'getPreparedExposedTemplateView'))->getMock();
        $mock->expects($this->any())->method('getPreparedExposedTemplateView')->willReturn($view);
        $mock->expects($this->atLeastOnce())->method('translateLabel')->willReturn('translated');
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $mock->injectObjectManager($objectManager);
        $paths = array(
            'fluidcontent' => array(
                'templateRootPaths' => array('EXT:fluidcontent/Tests/Fixtures/Templates/')
            )
        );
        $mock->expects($this->once())->method('getContentConfiguration')->willReturn($paths);
        $mock->expects($this->exactly(2))->method('message');
        $result = $this->callInaccessibleMethod($mock, 'buildAllWizardTabGroups', $paths);
        #var_dump($result);
        $this->assertArrayHasKey('Content', $result);
        $this->assertEquals('translated', $result['Content']['title']);
        $this->assertArrayHasKey('fluidcontent_DummyContent_html', $result['Content']['elements']);
    }

    /**
     * @dataProvider getTestRenderPageTypoScriptTestValues
     * @param $pageUid
     */
    public function testRenderPageTypoScriptForPageUidCreatesExpectedTypoScript($pageUid)
    {
        $class = substr(str_replace('Tests\\Unit\\', '', get_class($this)), 0, -4);
        $instance = $this->getMockBuilder($class)
            ->setMethods(
                array(
                    'overrideCurrentPageUidForConfigurationManager',
                    'getContentConfiguration',
                    'buildAllWizardTabGroups',
                    'buildAllWizardTabsPageTsConfig'
                )
            )->getMock();
        $instance->expects($this->at(0))->method('overrideCurrentPageUidForConfigurationManager')->with($pageUid);
        $instance->expects($this->at(1))->method('getContentConfiguration')->willReturn(array('foo' => 'bar'));
        $instance->expects($this->at(2))->method('buildAllWizardTabGroups')->with(array('foo' => 'bar'))->willReturn(array());
        $instance->expects($this->at(3))->method('buildAllWizardTabsPageTsConfig')->with(array())->willReturn('targetmarker');
        $result = $this->callInaccessibleMethod($instance, 'renderPageTypoScriptForPageUid', $pageUid);
        $this->assertContains('targetmarker', $result);
    }

    /**
     * @return array
     */
    public function getTestRenderPageTypoScriptTestValues()
    {
        return array(
            array(1),
            array(2)
        );
    }

    /**
     * @return void
     */
    public function testRenderPageTypoScriptForPageUidDelegatesExceptionsToDebug()
    {
        $class = substr(str_replace('Tests\\Unit\\', '', get_class($this)), 0, -4);
        $instance = $this->getMockBuilder($class)->setMethods(array('getContentConfiguration', 'debug', 'message'))->getMock();
        $instance->expects($this->once())->method('getContentConfiguration')
            ->willThrowException(new \RuntimeException('test'));
        $instance->expects($this->never())->method('message');
        $instance->expects($this->once())->method('debug');
        $this->callInaccessibleMethod($instance, 'renderPageTypoScriptForPageUid', 0, array());
    }

    /**
     * @return void
     */
    public function testConfigurationManagerOverrides()
    {
        $instance = new ConfigurationService();
        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->getMockBuilder(ConfigurationManager::class)->setMethods(array('setCurrentPageUid', 'getCurrentPageId'))->getMock();
        $mock->expects($this->at(0))->method('setCurrentPageUid')->with(1);
        $mock->expects($this->at(1))->method('getCurrentPageId')->willReturn(2);
        $mock->expects($this->at(2))->method('setCurrentPageUid')->with(2);
        $instance->injectConfigurationManager($mock);
        $this->callInaccessibleMethod($instance, 'overrideCurrentPageUidForConfigurationManager', 1);
        $this->callInaccessibleMethod($instance, 'backupPageUidForConfigurationManager');
        $this->callInaccessibleMethod($instance, 'restorePageUidForConfigurationManager');
    }

    /**
     * @test
     */
    public function testGetPageTsConfigFetchesAndCachesRootTypoScriptIfNotCached()
    {
        $expectedValue = 'This will be fetched and cached.';
        $cache = $this->getMockBuilder(VariableFrontend::class)->setMethods(array('has', 'set', 'get'))->disableOriginalConstructor()->getMock();
        $cache->expects($this->once())->method('has')->with($this->equalTo(self::CACHE_KEY_PAGETSCONFIG))->willReturn(false);
        $cache->expects($this->once())->method('set')->with($this->equalTo(self::CACHE_KEY_PAGETSCONFIG), $this->equalTo($expectedValue));
        $cache->expects($this->never())->method('get');
        $manager = $this->getMockBuilder(CacheManager::class)->setMethods(array('hasCache', 'getCache'))->getMock();
        $manager->expects($this->once())->method('hasCache')->willReturn(true);
        $manager->expects($this->once())->method('getCache')->willReturn($cache);
        $service = $this->getMockBuilder(ConfigurationService::class)
            ->setMethods(array('getAllRootTypoScriptTemplates', 'renderPageTypoScriptForPageUid', 'getTypoScriptTemplatesInRootline'))
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->never())->method('getTypoScriptTemplatesInRootline');
        $service->expects($this->once())->method('renderPageTypoScriptForPageUid')->willReturn($expectedValue);
        $service->expects($this->once())->method('getAllRootTypoScriptTemplates')->willReturn(array(1));

        $service->injectConfigurationManager($this->getMockBuilder(ConfigurationManager::class)->getMock());
        $service->injectCacheManager($manager);
        $returnedValue = $service->getPageTsConfig();

        $this->assertEquals($expectedValue, $returnedValue);
    }

    /**
     * @test
     */
    public function testGetPageTsConfigFetchesRootTypoScriptIfCacheUnavailable()
    {
        $expectedValue = 'This will be fetched.';
        $manager = $this->getMockBuilder(CacheManager::class)->setMethods(array('hasCache', 'getCache'))->getMock();
        $manager->expects($this->once())->method('hasCache')->willReturn(false);
        $manager->expects($this->never())->method('getCache')->willThrowException(new NoSuchCacheException());
        $service = $this->getMockBuilder(ConfigurationService::class)
            ->setMethods(array('getAllRootTypoScriptTemplates', 'renderPageTypoScriptForPageUid', 'getTypoScriptTemplatesInRootline'))
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->never())->method('getTypoScriptTemplatesInRootline');
        $service->expects($this->once())->method('renderPageTypoScriptForPageUid')->willReturn($expectedValue);
        $service->expects($this->once())->method('getAllRootTypoScriptTemplates')->willReturn(array(1));

        $service->injectConfigurationManager($this->getMockBuilder(ConfigurationManager::class)->getMock());
        $service->injectCacheManager($manager);
        $returnedValue = $service->getPageTsConfig();

        $this->assertEquals($expectedValue, $returnedValue);
    }

    /**
     * @test
     */
    public function testGetPageTsConfigUsesCachedRootTypoScriptIfAvailable()
    {
        $cachedValue = 'this has been cached';
        $cache = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(array('has', 'set', 'get', 'getByTag'))
            ->disableOriginalConstructor()
            ->getMock();
        $cache->expects($this->once())->method('has')->with(self::CACHE_KEY_PAGETSCONFIG)->willReturn(true);
        $cache->expects($this->never())->method('set');
        $cache->expects($this->once())->method('get')->with(self::CACHE_KEY_PAGETSCONFIG)->willReturn($cachedValue);
        $cache->expects($this->once())->method('getByTag')->with(ConfigurationService::ICON_CACHE_TAG)->willReturn(array());
        $manager = $this->getMockBuilder(CacheManager::class)->setMethods(array('hasCache', 'getCache'))->getMock();
        $manager->expects($this->once())->method('hasCache')->willReturn(true);
        $manager->expects($this->once())->method('getCache')->willReturn($cache);
        $service = $this->getMockBuilder(ConfigurationService::class)
            ->setMethods(array('getAllRootTypoScriptTemplates', 'renderPageTypoScriptForPageUid', 'getTypoScriptTemplatesInRootline'))
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->never())->method('getTypoScriptTemplatesInRootline');
        $service->expects($this->never())->method('getAllRootTypoScriptTemplates');

        $service->injectConfigurationManager($this->getMockBuilder(ConfigurationManager::class)->getMock());
        $service->injectCacheManager($manager);
        $returnedValue = $service->getPageTsConfig();

        $this->assertEquals($cachedValue, $returnedValue);
    }
}
