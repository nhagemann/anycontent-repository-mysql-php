<?php

namespace AnyContent\Repository;

use AnyContent\Repository\Modules\Core\Application\Application;
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

        $cacheDriver = new  \Doctrine\Common\Cache\ApcCache();
        $app['cache'] = $cacheDriver;

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

        $result = $manager->getRecord(1);
        $record = $result['record'];
        $this->assertEquals(1, $record['id']);
        $this->assertEquals(5, $record['info']['revision']);
        $this->assertEquals('New Record 1 - Revision 5', $record['properties']['name']);
        $this->assertCount(6, $record['properties']);

        $result = $manager->getRecord(2);
        $record = $result['record'];
        $this->assertEquals(2, $record['id']);
        $this->assertEquals(1, $record['info']['revision']);
        $this->assertEquals('New Record 2', $record['properties']['name']);
        $this->assertCount(6, $record['properties']);

        $manager              = $this->repository->getContentManager('example02');
        $record               = array();
        $record['properties'] = array( 'name' => 'New Record ' );
        $id                   = $manager->saveRecord($record);
        $this->assertEquals(1, $id);

        $result = $manager->getRecord(1);
        $record = $result['record'];
        $this->assertCount(8, $record['properties']);

    }


    public function testTimesShift()
    {
        // skip this test, since timeshifting unwanted delays test execution, remove if necessary
        return;

        /**
         * @var ContentManager
         */
        $manager              = $this->repository->getContentManager('example01');
        $record               = array();
        $record['properties'] = array( 'name' => 'Timeshift Record' );
        $id                   = $manager->saveRecord($record);
        $result               = $manager->getRecord($id);
        $record               = $result['record'];
        $this->assertEquals(1, $record['info']['revision']);
        sleep(2);
        $this->assertEquals($id, $manager->saveRecord($record));
        $record = $manager->getRecord($id, 'default', 'default', 'none', 1);
        $this->assertEquals(1, $record['info']['revision']);
        $record = $manager->getRecord($id);
        $this->assertEquals(2, $record['info']['revision']);
    }


    public function testDeleteRecord()
    {
        /**
         * @var ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        $record = $manager->getRecord(1);
        $this->assertEquals(1, $record['record']['id']);

        $manager->deleteRecord(1);

        $this->setExpectedException('AnyContent\Repository\RepositoryException');
        $record = $manager->getRecord(1);
    }


    public function testCountAfterDeletion()
    {
        /**
         * @var ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        $records = $manager->getRecords();
        $this->assertCount(4, $records['records']);
    }
}