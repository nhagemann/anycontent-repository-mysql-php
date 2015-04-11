<?php

namespace AnyContent\Repository;

use AnyContent\Repository\Modules\Core\Application\Application;
use AnyContent\Repository\Config;
use AnyContent\Repository\Database;
use AnyContent\Repository\Modules\Core\Repositories\ContentManager;
use AnyContent\Repository\RepositoryManager;
use AnyContent\Repository\Repository;
use AnyContent\Repository\Modules\Core\ContentRecords\Filter;

class FilterTest extends \PHPUnit_Framework_TestCase
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


    public function testFilters()
    {
        $this->app['db']->truncateContentType('example', 'example01');
        $this->app['db']->truncateContentType('example', 'example02');
        $this->app['db']->truncateContentType('example', 'example03');

        /**
         * @var $manager ContentManager
         */
        $manager = $this->repository->getContentManager('example01');

        $record               = array();
        $record['properties'] = array( 'name' => 'New Record 1','source'=>'a');
        $id                   = $manager->saveRecord($record);
        $this->assertEquals(1, $id);

        $record               = array();
        $record['properties'] = array( 'name' => 'New Record 2','source'=>'c','article'=>'c');
        $id                   = $manager->saveRecord($record);
        $this->assertEquals(2, $id);

        $record               = array();
        $record['properties'] = array( 'name' => 'Differing Name');
        $id                   = $manager->saveRecord($record);
        $this->assertEquals(3, $id);

        $filter = new Filter();
        $filter->addCondition('name', '><', 'Record');
        $filter->addCondition('name', '=', 'Differing Name');
        $records = $manager->getRecords('default', 'default', 'property_name DESC, id ASC',null,1,null,$filter);
        $this->assertCount(3, $records['records']);


        $filter = new Filter();
        $filter->addCondition('name', '=', 'Differing Name');
        $records = $manager->getRecords('default', 'default', 'property_name DESC, id ASC',null,1,null,$filter);
        $this->assertCount(1, $records['records']);

        $filter = new Filter();
        $filter->addCondition('name', '=', 'New Record 1');
        $filter->nextConditionsBlock();
        $filter->addCondition('source', '=', 'a');
        $records = $manager->getRecords('default', 'default', 'property_name DESC, id ASC',null,1,null,$filter);
        $this->assertCount(1, $records['records']);

        $filter = new Filter();
        $filter->addCondition('name', '=', 'New Record 1');
        $filter->nextConditionsBlock();
        $filter->addCondition('source', '=', 'b');
        $records = $manager->getRecords('default', 'default', 'property_name DESC, id ASC',null,1,null,$filter);
        $this->assertCount(0, $records['records']);

        $filter = new Filter();
        $filter->addCondition('source', '={}', 'article');
        $records = $manager->getRecords('default', 'default', 'property_name DESC, id ASC',null,1,null,$filter);
        $this->assertCount(1, $records['records']);

        $filter = new Filter();
        $filter->addCondition('name', '=', 'New Record 2');
        $filter->addCondition('source', '=', 'c');
        $records = $manager->getRecords('default', 'default', 'property_name DESC, id ASC',null,1,null,$filter);
        $this->assertCount(1, $records['records']);

        $filter = new Filter();
        $filter->addCondition('name', '=', 'New Record 2');
        $filter->nextConditionsBlock();
        $filter->addCondition('source', '=', 'a');
        $records = $manager->getRecords('default', 'default', 'property_name DESC, id ASC',null,1,null,$filter);
        $this->assertCount(0, $records['records']);
    }
}
