<?php

namespace AnyContent\Repository;

use Silex\Application;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;
use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\ContentManager;
use AnyContent\Repository\Repository;

//use AnyContent\Client\Record;

class SortRecordsTest extends \PHPUnit_Framework_TestCase
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


    public function testSortRecords()
    {

        $this->app['db']->deleteRepository('example', 'example01');
        $this->app['db']->deleteRepository('example', 'example02');
        $this->app['db']->deleteRepository('example', 'example03');

        /**
         * @var $manager ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        for ($i = 1; $i <= 10; $i++)
        {
            $record               = array();
            $record['properties'] = array( 'name' => 'New Record ' . $i );
            $id                   = $manager->saveRecord($record);
            $this->assertEquals($i, $id);
        }

        // add additional records with differing languages and workspaces, to make sure selection
        // is effective within it's boundaries only

        for ($i = 11; $i <= 13; $i++)
        {
            $record               = array();
            $record['properties'] = array( 'name' => 'New Record ' . $i );
            $id                   = $manager->saveRecord($record,'default','live','es');
            $this->assertEquals($i, $id);
        }

        for ($i = 14; $i <= 17; $i++)
        {
            $record               = array();
            $record['properties'] = array( 'name' => 'New Record ' . $i );
            $id                   = $manager->saveRecord($record,'default','default','es');
            $this->assertEquals($i, $id);
        }


        //
        // sort records within following tree list
        //
        // - 1
        //   - 2
        //   - 3
        // - 4
        //   - 5
        //     - 7
        //     - 8
        //   - 6
        //     - 9


        $list   = array();
        $list[] = array( 'id' => 1, 'parent_id' => 0 );
        $list[] = array( 'id' => 2, 'parent_id' => 1 );
        $list[] = array( 'id' => 3, 'parent_id' => 1 );
        $list[] = array( 'id' => 4, 'parent_id' => 0 );
        $list[] = array( 'id' => 5, 'parent_id' => 4 );
        $list[] = array( 'id' => 6, 'parent_id' => 4 );
        $list[] = array( 'id' => 7, 'parent_id' => 5 );
        $list[] = array( 'id' => 8, 'parent_id' => 5 );
        $list[] = array( 'id' => 9, 'parent_id' => 6 );

        $manager->sortRecords($list);

        // subset = parent_id,include_parent_id(default=1),depth(default=null)

        $subset  = '0';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(9,$records['records']);

        $subset  = '1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records['records']);

        $subset  = '4';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(6,$records['records']);

        $subset  = '4,0';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(5,$records['records']);

        $subset  = '4,0,1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(2,$records['records']);

        $subset  = '5,0';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(2,$records['records']);

        $subset  = '5,1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records['records']);

        $subset  = '6,0';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(1,$records['records']);

        $subset  = '6,1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(2,$records['records']);

        $subset  = '7,1,-9';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records['records']);

        $subset  = '8,1,-9';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records['records']);

        $subset  = '9,1,-9';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records['records']);

        $subset  = '3,1,-9';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(2,$records);

        $subset  = '4,1,-9';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(1,$records['records']);

        $subset  = '5,0,-9';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(1,$records['records']);

        $subset  = '4,0,-9';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(0,$records['records']);

        $subset  = '8,0,-1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(1,$records['records']);

        $subset  = '8,0,-2';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(2,$records['records']);

        $subset  = '8,1,-1';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(2,$records['records']);

        $subset  = '8,1,-2';
        $records = $manager->getRecords('default', 'default','id ASC', null, 1, $subset);
        $this->assertCount(3,$records['records']);


        // some sorting within live/es

        $list   = array();
        $list[] = array( 'id' => 12, 'parent_id' => 0 );
        $list[] = array( 'id' => 11, 'parent_id' => 0 );


        /*$manager->sortRecords($list,'live','es');

        $subset  = '0';
        $records = $manager->getRecords('default', 'live','id ASC', null, 1, $subset,null,'es');
        $this->assertCount(2,$records['records']);
        */

    }

}