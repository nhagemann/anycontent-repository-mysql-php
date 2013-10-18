<?php

namespace AnyContent\Repository;

use Silex\Application;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;
use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\Service\ContentManager;
use AnyContent\Repository\Repository;

//use AnyContent\Client\Record;

class ContentManagerTest extends \PHPUnit_Framework_TestCase
{

    protected $app;

    /** @var $repository Repository */
    protected $repository;


    public function setUp()
    {

        $app           = new Application();
        $app['config'] = new Config($app);
        $app['repos']  = new RepositoryManager($app);
        $app['db']     = new Database($app);

        $this->app = $app;

        $this->repository = $this->app['repos']->get('example');

    }


    public function testSaveRecords()
    {
        $this->app['db']->deleteRepository('example', 'example01');
        $this->app['db']->deleteRepository('example', 'example02');
        $this->app['db']->deleteRepository('example', 'example03');

        /**
         * @var ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        for ($i = 1; $i <= 5; $i++)
        {
            $record               = array();
            $record['properties'] = array( 'name' => 'New Record ' . $i );
            $id                   = $manager->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        for ($i = 2; $i <= 5; $i++)
        {
            $record               = array();
            $record['id']         = 1;
            $record['properties'] = array( 'name' => 'New Record 1 - Revision ' . $i );
            $id                   = $manager->saveRecord($record);
            $this->assertEquals(1, $id);
        }
    }


    public function testGetRecord()
    {
        /**
         * @var ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        $record = $manager->getRecord(1);
        $this->assertEquals(1, $record['id']);
        $this->assertEquals(5, $record['info']['revision']);
        $this->assertEquals('New Record 1 - Revision 5', $record['properties']['name']);
        $this->assertCount(6, $record['properties']);

        $record = $manager->getRecord(2);
        $this->assertEquals(2, $record['id']);
        $this->assertEquals(1, $record['info']['revision']);
        $this->assertEquals('New Record 2', $record['properties']['name']);
        $this->assertCount(6, $record['properties']);

        $manager              = $this->repository->getContentManager('example02');
        $record               = array();
        $record['properties'] = array( 'name' => 'New Record ' );
        $id                   = $manager->saveRecord($record);
        $this->assertEquals(1, $id);

        $record = $manager->getRecord(1);
        $this->assertCount(8, $record['properties']);

    }

}