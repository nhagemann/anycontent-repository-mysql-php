<?php

namespace AnyContent\Repository;

use AnyContent\Repository\Modules\Core\Application\Application;

use AnyContent\Repository\Modules\Core\Repositories\ContentManager;
use AnyContent\Repository\Repository;

//use AnyContent\Client\Record;

class NameAnnotationTest extends \PHPUnit_Framework_TestCase
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


    public function testGetRecords()
    {

        $this->app['db']->truncateContentType('example', 'example05');

        /** @var ContentManager $manager */
        $manager = $this->repository->getContentManager('example05');

        $record               = array();
        $record['properties'] = array( 'firstname' => 'Nils', 'lastname' => 'Hagemann' );
        $id                   = $manager->saveRecord($record);
        $this->assertEquals(1, $id);

        $records = $manager->getRecords();
        $this->assertCount(1, $records['records']);

        $record = $manager->getRecord(1);

        $this->assertEquals('Hagemann', $record['record']['properties']['lastname']);
        $this->assertEquals('Nils', $record['record']['properties']['firstname']);
        $this->assertEquals('Hagemann, Nils', $record['record']['properties']['name']);

    }

}