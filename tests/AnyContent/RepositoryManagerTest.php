<?php

namespace AnyContent;

use Silex\Application;
use AnyContent\Service\Config;

use AnyContent\Service\RepositoryManager;
use AnyContent\Repository\Repository;

class RepositoryManagerTest extends \PHPUnit_Framework_TestCase
{

    protected $app;


    public function setUp()
    {
        $app           = new Application();
        $app['config'] = new Config($app);
        $app['repos']  = new RepositoryManager($app);

        $this->app = $app;
    }


    public function testGetRepository()
    {

        $this->assertInstanceOf('AnyContent\Repository\Repository', $this->app['repos']->get('example'));
        $this->assertFalse($this->app['repos']->get('mostunlikelyrepositoryname457230495789'));

    }

    public function testGetContentTypes()
    {
        /** @var $repo Repository */
        $repo = $this->app['repos']->get('example');

        $repo->getContentTypes();

    }
}