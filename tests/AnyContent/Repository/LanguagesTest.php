<?php

namespace AnyContent\Repository;

use AnyContent\Repository\Modules\Core\Application\Application;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;
use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\Service\ContentManager;
use AnyContent\Repository\Repository;

//use AnyContent\Client\Record;

class LanguagesTest extends \PHPUnit_Framework_TestCase
{

    protected $app;

    /** @var $repository Repository */
    protected $repository;


    public function setUp()
    {

        $app_env      = 'test';
        $app          = require __DIR__ . '/../../../web/index.php';
        $app['debug'] = true;
        $app['exception_handler']->disable();
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
            $id                   = $manager->saveRecord($record, 'default', 'default', 'default');
            $this->assertEquals($i, $id);
        }

        for ($i = 1; $i <= 5; $i++)
        {
            $record               = array();
            $record['properties'] = array( 'name' => 'New Record ' . $i );
            $id                   = $manager->saveRecord($record, 'default', 'default', 'en');
            $this->assertEquals(5 + $i, $id);
        }

        for ($i = 1; $i <= 5; $i++)
        {
            $record               = array();
            $record['properties'] = array( 'name' => 'New Record ' . $i );
            $id                   = $manager->saveRecord($record, 'default', 'live', 'en');
            $this->assertEquals(10 + $i, $id);
        }

        $records = $manager->getRecords('default', 'default', 'id ASC', null, 1, null, null, 'default');
        $this->assertCount(5, $records['records']);
        $records = $manager->getRecords('default', 'default', 'id ASC', null, 1, null, null, 'en');
        $this->assertCount(5, $records['records']);
        $records = $manager->getRecords('default', 'live', 'id ASC', null, 1, null, null, 'en');
        $this->assertCount(5, $records['records']);

    }


    public function testNextFreeId()
    {

        /**
         * @var ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        $record               = array();
        $record['properties'] = array( 'name' => 'New Record' );
        $firstId              = $manager->saveRecord($record, 'default', 'default', 'default');

        $record['id'] = $firstId;

        $id           = $manager->saveRecord($record, 'default', 'default', 'default');
        $this->assertEquals($firstId,$id);

        $id           = $manager->saveRecord($record, 'default', 'default', 'en');
        $this->assertEquals($firstId,$id);

        $id           = $manager->saveRecord($record, 'default', 'live', 'en');
        $this->assertEquals($firstId,$id);
    }
}
