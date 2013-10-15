<?php

namespace AnyContent;

use Silex\Application;
use AnyContent\Service\Config;
use AnyContent\Service\Database;
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
        $app['db']     = new Database($app);

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

        $contentTypes = $repo->getContentTypesList();

        $this->assertInstanceOf('AnyContent\Entity\ContentTypeInfo', $contentTypes['example01']);
        $this->assertCount(3, $contentTypes);
    }


    public function testGetCMDL()
    {
        /** @var $repo Repository */
        $repo = $this->app['repos']->get('example');

        $cmdl = $repo->getCMDL('example01');
        $this->assertStringEqualsFile('cmdl/example/example01.cmdl', $cmdl);

        $cmdl = $repo->getCMDL('example99');
        $this->assertFalse($cmdl);
    }


    public function testDatabaseOperations()
    {
        /** @var $repo Repository */
        $repo = $this->app['repos']->get('example');

        $contentType = $repo->getContentType('example01');
        $this->app['db']->refreshContentTypeTableStructure('example', $contentType);
    }
}