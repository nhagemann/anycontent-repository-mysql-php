<?php

namespace AnyContent\Repository;

use AnyContent\Repository\Modules\Core\Application\Application;
use AnyContent\Repository\Modules\Core\Application\ConfigService;
use AnyContent\Repository\Modules\Core\Application\Database;
use AnyContent\Repository\Modules\Core\Repositories\RepositoryManager;
use AnyContent\Repository\Modules\Core\Repositories\Repository;

//use AnyContent\Client\Record;

class RepositoryManagerTest extends \PHPUnit_Framework_TestCase
{

    protected $app;


    public function setUp()
    {
        $app_env      = 'test';
        $app          = require __DIR__ . '/../../../web/index.php';
        $app['debug'] = true;
        $app['exception_handler']->disable();
        $this->app = $app;

        $this->repository = $this->app['repos']->get('example');
    }


    public function testGetRepository()
    {

        $this->assertInstanceOf('AnyContent\Repository\Modules\Core\Repositories\Repository', $this->app['repos']->get('example'));
        $this->assertFalse($this->app['repos']->get('mostunlikelyrepositoryname457230495789'));

    }


    public function testGetContentTypes()
    {
        /** @var $repo Repository */
        $repo = $this->app['repos']->get('example');

        $contentTypes = $repo->getContentTypesList();

        $this->assertInstanceOf('AnyContent\Repository\Modules\Core\Repositories\ContentTypeInfo', $contentTypes['example01']);

        $this->assertCount(5, $contentTypes);
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

        $contentTypeDefinition = $repo->getContentTypeDefinition('example01');

        $this->app['db']->refreshContentTypeTableStructure('example', $contentTypeDefinition);

    }


    public function testCreateAndDeleteContentType()
    {
        /** @var $manager RepositoryManager */
        $manager = $this->app['repos'];

        $manager->discardContentType('example', 'example99');

        /** @var $repo Repository */
        $repo = $this->app['repos']->get('example');

        $contentTypeDefinition = $repo->getContentTypeDefinition('example99');
        $this->assertFalse($contentTypeDefinition);

        $manager->saveContentTypeCMDL('example', 'example99', 'test');

        $contentTypeDefinition = $repo->getContentTypeDefinition('example99');
        $this->assertTrue((boolean)$contentTypeDefinition);

        $manager->discardContentType('example', 'example99');

    }


    public function testCreateAndDeleteConfigType()
    {
        /** @var $manager RepositoryManager */
        $manager = $this->app['repos'];

        $manager->discardConfigType('example', 'config99');

        /** @var $repo Repository */
        $repo = $this->app['repos']->get('example');

        $configTypeDefinition = $repo->getConfigTypeDefinition('config99');
        $this->assertFalse($configTypeDefinition);

        $manager->saveConfigTypeCMDL('example', 'config99', 'test');

        $configTypeDefinition = $repo->getConfigTypeDefinition('config99');
        $this->assertTrue((boolean)$configTypeDefinition);

        $manager->discardConfigType('example', 'config99');

    }


    public function testCreateAndDeleteRepository()
    {
        /** @var $manager RepositoryManager */
        $manager = $this->app['repos'];

        $this->assertFalse($manager->hasRepository('example99'));

        $manager->createRepository('example99');

        $this->assertTrue($manager->hasRepository('example99'));

        $manager->discardRepository('example99');

        $this->assertFalse($manager->hasRepository('example99'));

    }

}