<?php

namespace AnyContent\Repository;

use AnyContent\Repository\Modules\Core\Application\Application;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;
use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\ContentManager;
use AnyContent\Repository\Repository;

//use AnyContent\Client\Record;

class SynchronizedPropertiesTest extends \PHPUnit_Framework_TestCase
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


    public function testPropertiesNotSet()
    {
        $manager = $this->repository->getContentManager('example03');

        $record               = array();
        $record['properties'] = array( 'name' => 'New Record' );
        $id                   = $manager->saveRecord($record);

        $record['id'] = $id;

        $nextId = $manager->saveRecord($record, 'default', 'default', 'es');

        $this->assertEquals($id, $nextId);

    }

}