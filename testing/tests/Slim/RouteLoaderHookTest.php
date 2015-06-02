<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Panthor\Slim;

use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\Route;
use Slim\Slim;
use Symfony\Component\DependencyInjection\Container;

class RouteLoaderHookTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->di = Mockery::mock(Container::CLASS);
        $this->slim = Mockery::mock(Slim::CLASS);
    }

    public function testAddingRoutesOnInstantiation()
    {
        $routes = [
            'herp' => [
                'method' => 'POST',
                'route' => '/users/:id',
                'stack' => ['middleware.test', 'test.page'],
                'conditions' => ['id' => '[\d]{6}']
            ],
            'derp' => [
                'method' => ['GET', 'POST'],
                'route' => '/resource/add',
                'stack' => ['resource.add.page']
            ]
        ];

        $route1 = Mockery::mock(Route::CLASS);
        $route2 = Mockery::mock(Route::CLASS);

        $this->slim
            ->shouldReceive('map')
            ->with('/users/:id', Mockery::type('Closure'), Mockery::type('Closure'))
            ->andReturn($route1);
        $this->slim
            ->shouldReceive('map')
            ->with('/resource/add', Mockery::type('Closure'))
            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('via')
            ->with('POST')
            ->once();
        $route1
            ->shouldReceive('name')
            ->with('herp')
            ->once();
        $route1
            ->shouldReceive('conditions')
            ->with(['id' => '[\d]{6}'])
            ->once();

        // route 2
        $route2
            ->shouldReceive('via')
            ->with('GET', 'POST')
            ->once();
        $route2
            ->shouldReceive('name')
            ->with('derp')
            ->once();

        $hook = new RouteLoaderHook($this->di, $routes);
        $hook($this->slim);
    }

    public function testAddingIncrementalRoutes()
    {
        $routes = [
            'herp' => [
                'method' => 'POST',
                'route' => '/users/:id',
                'stack' => ['middleware.test', 'test.page'],
                'conditions' => ['id' => '[\d]{6}']
            ],
            'derp' => [
                'method' => ['GET', 'POST'],
                'route' => '/resource/add',
                'stack' => ['resource.add.page']
            ]
        ];

        $route1 = Mockery::mock(Route::CLASS);
        $route2 = Mockery::mock(Route::CLASS);

        $this->slim
            ->shouldReceive('map')
            ->with('/users/:id', Mockery::type('Closure'), Mockery::type('Closure'))
            ->andReturn($route1);
        $this->slim
            ->shouldReceive('map')
            ->with('/resource/add', Mockery::type('Closure'))
            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('via')
            ->with('POST')
            ->once();
        $route1
            ->shouldReceive('name')
            ->with('herp')
            ->once();
        $route1
            ->shouldReceive('conditions')
            ->with(['id' => '[\d]{6}'])
            ->once();

        // route 2
        $route2
            ->shouldReceive('via')
            ->with('GET', 'POST')
            ->once();
        $route2
            ->shouldReceive('name')
            ->with('derp')
            ->once();

        $hook = new RouteLoaderHook($this->di);
        $hook->addRoutes($routes);
        $hook($this->slim);
    }

    public function testMergingRoutes()
    {
        $routes = [
            'herp' => [
                'method' => 'POST',
                'route' => '/users/:id',
                'stack' => ['middleware.test', 'test.page'],
                'conditions' => ['id' => '[\d]{6}']
            ],
            'derp' => [
                'method' => ['GET', 'POST'],
                'route' => '/resource/add',
                'stack' => ['resource.add.page']
            ]
        ];

        $route1 = Mockery::mock(Route::CLASS);
        $route2 = Mockery::mock(Route::CLASS);

        $this->slim
            ->shouldReceive('map')
            ->with('/users/:id', Mockery::type('Closure'), Mockery::type('Closure'))
            ->andReturn($route1);
        $this->slim
            ->shouldReceive('map')
            ->with('/new-resource/add', Mockery::type('Closure'))
            ->andReturn($route2);

        // route 1
        $route1
            ->shouldReceive('via')
            ->with('POST')
            ->once();
        $route1
            ->shouldReceive('name')
            ->with('herp')
            ->once();
        $route1
            ->shouldReceive('conditions')
            ->with(['id' => '[\d]{6}'])
            ->once();

        // route 2
        $route2
            ->shouldReceive('via')
            ->with('DELETE')
            ->once();
        $route2
            ->shouldReceive('name')
            ->with('derp')
            ->once();

        $hook = new RouteLoaderHook($this->di, $routes);

        // This overwrites the previously set route
        $hook->addRoutes([
            'derp' => [
                'method' => ['DELETE'],
                'route' => '/new-resource/add',
                'stack' => ['resource2.add.page']
            ]
        ]);
        $hook($this->slim);
    }
}
