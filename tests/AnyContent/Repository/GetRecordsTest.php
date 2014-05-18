<?php

namespace AnyContent\Repository;

use AnyContent\Repository\Application;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;
use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\ContentManager;
use AnyContent\Repository\Repository;

//use AnyContent\Client\Record;

class GetRecordsTest extends \PHPUnit_Framework_TestCase
{

    protected $app;

    /** @var $repository Repository */
    protected $repository;


    public function setUp()
    {

        $app           = new Application();
        $app['config'] = new Config($app);

        $cacheDriver = new  \Doctrine\Common\Cache\ApcCache();
        $app['cache'] = $cacheDriver;

        $app['repos']  = new RepositoryManager($app);
        $app['db']     = new Database($app);


        $this->app = $app;

        $this->repository = $this->app['repos']->get('example');

    }


    public function testGetRecords()
    {

        $this->app['db']->deleteRepository('example', 'example01');
        $this->app['db']->deleteRepository('example', 'example02');
        $this->app['db']->deleteRepository('example', 'example03');

        /**
         * @var $manager ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        for ($i = 1; $i <= 5; $i++)
        {
            $record               = array();
            $record['properties'] = array( 'name' => 'New Record ' . $i );
            $id                   = $manager->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        for ($i = 1; $i <= 3; $i++)
        {
            $record               = array();
            $record['properties'] = array( 'name' => 'New Record ' . $i );
            $manager->saveRecord($record, 'default', 'live');
        }

        $records = $manager->getRecords();
        $this->assertCount(5, $records['records']);
        $records = $manager->getRecords('default', 'live');
        $this->assertCount(3, $records['records']);
        $records = $manager->getRecords('default', 'default');
        $this->assertCount(5, $records['records']);

        $records = $manager->getRecords('default', 'default', 'property_name DESC, id ASC');
        $record = array_shift($records['records']);
        $this->assertEquals(5, $record['id']);

        $records = $manager->getRecords('default', 'default','id ASC',2,1);
        $this->assertCount(2, $records['records']);
        $records = $manager->getRecords('default', 'default','id ASC',2,3);
        $this->assertCount(1, $records['records']);
    }

    public function testDeleteRecords()
    {
        /**
         * @var $manager ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        $manager->deleteRecord(1);

        $records = $manager->getRecords();
        $this->assertCount(4, $records['records']);

        $manager->deleteRecord(99);
    }

}