<?php
namespace FluidTYPO3\Fluidcontent\Tests\Unit\Controller;

/*
 * This file is part of the FluidTYPO3/Fluidcontent project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Fluidcontent\Controller\ContentController;
use FluidTYPO3\Flux\Configuration\ConfigurationManager;
use FluidTYPO3\Flux\View\ExposedTemplateView;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Reflection\PropertyReflection;

/**
 * Class ContentControllerTest
 */
class ContentControllerTest extends UnitTestCase
{

    public function testInitializeView()
    {

        /** @var ContentController|\PHPUnit_Framework_MockObject_MockObject $instance */
        $instance = $this->getMockBuilder(ContentController::class)
            ->setMethods(
                [
                    'getRecord', 'initializeProvider', 'initializeSettings', 'initializeOverriddenSettings',
                    'initializeViewObject', 'initializeViewVariables'
                ]
            )->getMock();
        $viewProperty = new PropertyReflection(ContentController::class, 'request');
        $viewProperty->setAccessible(true);
        $viewProperty->setValue($instance, $this->getMockBuilder(Request::class)->getMock());
        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $configurationManager */
        $configurationManager = $this->getMockBuilder(ConfigurationManager::class)
            ->setMethods(['getContentObject', 'getConfiguration'])
            ->getMock();
        $contentObject = new \stdClass();
        $configurationManager->expects($this->once())->method('getContentObject')->willReturn($contentObject);
        $configurationManager->expects($this->once())->method('getConfiguration')->willReturn(['foo' => 'bar']);
        $instance->expects($this->atLeastOnce())->method('getRecord')->willReturn(['uid' => 0]);
        $GLOBALS['TSFE'] = (object) ['page' => 'page', 'fe_user' => (object) ['user' => 'user']];
        /** @var ExposedTemplateView|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->getMockBuilder(ExposedTemplateView::class)->setMethods(['assign'])->getMock();
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
