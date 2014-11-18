<?php
/**
 * Tests Magento\Core\App\Router\Base
 *
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Core\App\Router;

class BaseTest extends \Magento\Test\BaseTestCase
{
    /**
     * @var \Magento\Core\App\Router\Base
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\RequestInterface
     */
    private $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Route\ConfigInterface
     */
    private $routeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\State
     */
    private $appStateMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Router\ActionList
     */
    private $actionListMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\ActionFactory
     */
    private $actionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Code\NameBuilder
     */
    private $nameBuilderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\DefaultPathInterface
     */
    private $defaultPathMock;

    public function setUp()
    {
        parent::setUp();
        // Create mocks
        $requestMethods = [
            'getActionName',
            'getModuleName',
            'getParam',
            'setActionName',
            'setModuleName',
            'setRouteName',
            'getPathInfo',
            'getControllerName',
            'setControllerName',
            'setControllerModule',
            'setAlias',
            'getCookie',
            'getOriginalPathInfo',
            'getPost',
            'isSecure',
            'setParams',
        ];

        $this->requestMock = $this->getMock('Magento\Framework\App\RequestInterface', $requestMethods);
        $this->routeConfigMock = $this->basicMock('Magento\Framework\App\Route\ConfigInterface');
        $this->appStateMock = $this->basicMock('Magento\Framework\App\State');
        $this->actionListMock = $this->basicMock('Magento\Framework\App\Router\ActionList');
        $this->actionFactoryMock = $this->basicMock('Magento\Framework\App\ActionFactory');
        $this->nameBuilderMock = $this->basicMock('Magento\Framework\Code\NameBuilder');
        $this->defaultPathMock = $this->basicMock('Magento\Framework\App\DefaultPathInterface');

        // Prepare SUT
        $mocks = [
            'actionList' => $this->actionListMock,
            'actionFactory' => $this->actionFactoryMock,
            'routeConfig' => $this->routeConfigMock,
            'appState' => $this->appStateMock,
            'nameBuilder' => $this->nameBuilderMock,
            'defaultPath' => $this->defaultPathMock,
        ];
        $this->model = $this->objectManager->getObject('Magento\Core\App\Router\Base', $mocks);
    }

    public function testMatch()
    {
        // Test Data
        $actionInstance = 'action instance';
        $moduleFrontName = 'module front name';
        $actionPath = 'action path';
        $actionName = 'action name';
        $actionClassName = 'Magento\Cms\Controller\Index\Index';
        $moduleName = 'module name';
        $moduleList = [$moduleName];

        // Stubs
        $this->requestMock->expects($this->any())->method('getModuleName')->willReturn($moduleFrontName);
        $this->requestMock->expects($this->any())->method('getControllerName')->willReturn($actionPath);
        $this->requestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->routeConfigMock->expects($this->any())->method('getModulesByFrontName')->willReturn($moduleList);
        $this->appStateMock->expects($this->any())->method('isInstalled')->willReturn(true);
        $this->actionListMock->expects($this->any())->method('get')->willReturn($actionClassName);
        $this->actionFactoryMock->expects($this->any())->method('create')->willReturn($actionInstance);

        // Expectations and Test
        $this->requestExpects('setModuleName', $moduleFrontName)
            ->requestExpects('setControllerName', $actionPath)
            ->requestExpects('setActionName', $actionName)
            ->requestExpects('setControllerModule', $moduleName);

        $this->assertSame($actionInstance, $this->model->match($this->requestMock));
    }

    public function testMatchUseParams()
    {
        // Test Data
        $actionInstance = 'action instance';
        $moduleFrontName = 'module front name';
        $actionPath = 'action path';
        $actionName = 'action name';
        $actionClassName = 'Magento\Cms\Controller\Index\Index';
        $moduleName = 'module name';
        $moduleList = [$moduleName];
        $paramList = $moduleFrontName . '/' . $actionPath . '/' . $actionName . '/key/val/key2/val2/';

        // Stubs
        $this->requestMock->expects($this->any())->method('getPathInfo')->willReturn($paramList);
        $this->routeConfigMock->expects($this->any())->method('getModulesByFrontName')->willReturn($moduleList);
        $this->appStateMock->expects($this->any())->method('isInstalled')->willReturn(false);
        $this->actionListMock->expects($this->any())->method('get')->willReturn($actionClassName);
        $this->actionFactoryMock->expects($this->any())->method('create')->willReturn($actionInstance);

        // Expectations and Test
        $this->requestExpects('setModuleName', $moduleFrontName)
            ->requestExpects('setControllerName', $actionPath)
            ->requestExpects('setActionName', $actionName)
            ->requestExpects('setControllerModule', $moduleName);

        $this->assertSame($actionInstance, $this->model->match($this->requestMock));
    }

    public function testMatchUseDefaultPath()
    {
        // Test Data
        $actionInstance = 'action instance';
        $moduleFrontName = 'module front name';
        $actionPath = 'action path';
        $actionName = 'action name';
        $actionClassName = 'Magento\Cms\Controller\Index\Index';
        $moduleName = 'module name';
        $moduleList = [$moduleName];

        // Stubs
        $defaultReturnMap = [
            ['module', $moduleFrontName],
            ['controller', $actionPath],
            ['action', $actionName]
        ];
        $this->defaultPathMock->expects($this->any())->method('getPart')->willReturnMap($defaultReturnMap);
        $this->routeConfigMock->expects($this->any())->method('getModulesByFrontName')->willReturn($moduleList);
        $this->appStateMock->expects($this->any())->method('isInstalled')->willReturn(false);
        $this->actionListMock->expects($this->any())->method('get')->willReturn($actionClassName);
        $this->actionFactoryMock->expects($this->any())->method('create')->willReturn($actionInstance);

        // Expectations and Test
        $this->requestExpects('setModuleName', $moduleFrontName)
            ->requestExpects('setControllerName', $actionPath)
            ->requestExpects('setActionName', $actionName)
            ->requestExpects('setControllerModule', $moduleName);

        $this->assertSame($actionInstance, $this->model->match($this->requestMock));
    }

    public function testMatchEmptyModuleList()
    {
        // Test Data
        $actionInstance = 'action instance';
        $moduleFrontName = 'module front name';
        $actionPath = 'action path';
        $actionName = 'action name';
        $actionClassName = 'Magento\Cms\Controller\Index\Index';
        $emptyModuleList = [];

        // Stubs
        $this->requestMock->expects($this->any())->method('getModuleName')->willReturn($moduleFrontName);
        $this->routeConfigMock->expects($this->any())->method('getModulesByFrontName')->willReturn($emptyModuleList);
        $this->requestMock->expects($this->any())->method('getControllerName')->willReturn($actionPath);
        $this->requestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->appStateMock->expects($this->any())->method('isInstalled')->willReturn(false);
        $this->actionListMock->expects($this->any())->method('get')->willReturn($actionClassName);
        $this->actionFactoryMock->expects($this->any())->method('create')->willReturn($actionInstance);

        // Test
        $this->assertNull($this->model->match($this->requestMock));
    }

    public function testMatchEmptyActionInstance()
    {
        // Test Data
        $nullActionInstance = null;
        $moduleFrontName = 'module front name';
        $actionPath = 'action path';
        $actionName = 'action name';
        $actionClassName = 'Magento\Cms\Controller\Index\Index';
        $moduleName = 'module name';
        $moduleList = [$moduleName];

        // Stubs
        $this->requestMock->expects($this->any())->method('getModuleName')->willReturn($moduleFrontName);
        $this->routeConfigMock->expects($this->any())->method('getModulesByFrontName')->willReturn($moduleList);
        $this->requestMock->expects($this->any())->method('getControllerName')->willReturn($actionPath);
        $this->requestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->appStateMock->expects($this->any())->method('isInstalled')->willReturn(false);
        $this->actionListMock->expects($this->any())->method('get')->willReturn($actionClassName);
        $this->actionFactoryMock->expects($this->any())->method('create')->willReturn($nullActionInstance);

        // Expectations and Test
        $this->assertNull($this->model->match($this->requestMock));
    }

    public function testGetActionClassName()
    {
        $className = 'name of class';
        $module = 'module';
        $prefix = 'Controller';
        $actionPath = 'action path';
        $this->nameBuilderMock->expects($this->once())
            ->method('buildClassName')
            ->with([$module, $prefix, $actionPath])
            ->willReturn($className);
        $this->assertEquals($className, $this->model->getActionClassName($module, $actionPath));

    }

    /**
     * Generate a stub with an expected usage for the request mock object
     *
     * @param string $method
     * @param string $with
     * @return $this
     */
    private function requestExpects($method, $with)
    {
        $this->requestMock->expects($this->once())
            ->method($method)
            ->with($with);
        return $this;
    }
} 