<?php

namespace AnyContent\Repository;

use AnyContent\Repository\Modules\Core\Application\Application;
use AnyContent\Repository\Modules\Core\ContentRecords\FilterFactory;
use AnyContent\Repository\Repository;
use AnyContent\Repository\Modules\Core\ContentRecords\Filter;


class FilterFactoryTest extends \PHPUnit_Framework_TestCase
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


    public function testFactory()
    {
        $query = 'name = test';

        $filter = FilterFactory::createFromQuery($query);

        $this->assertInstanceOf('AnyContent\Repository\Modules\Core\ContentRecords\Filter',$filter);

        $array = $filter->getConditionsArray();

        $this->assertCount(1,$array[1]);
        $this->assertEquals('name',$array[1][0][0]);
        $this->assertEquals('=',$array[1][0][1]);
        $this->assertEquals('test',$array[1][0][2]);


        $query = 'name = test, name=test2';

        $filter = FilterFactory::createFromQuery($query);

        $this->assertInstanceOf('AnyContent\Repository\Modules\Core\ContentRecords\Filter',$filter);

        $array = $filter->getConditionsArray();

        $this->assertCount(2,$array[1]);

        $query = 'name = "Hans\,Dieter"';

        $filter = FilterFactory::createFromQuery($query);

        $this->assertInstanceOf('AnyContent\Repository\Modules\Core\ContentRecords\Filter',$filter);

        $array = $filter->getConditionsArray();


        $this->assertCount(1,$array[1]);
        $this->assertEquals('name',$array[1][0][0]);
        $this->assertEquals('=',$array[1][0][1]);
        $this->assertEquals('Hans,Dieter',$array[1][0][2]);

        $query = 'name = test + status = 1';

        $filter = FilterFactory::createFromQuery($query);

        $this->assertInstanceOf('AnyContent\Repository\Modules\Core\ContentRecords\Filter',$filter);

        $array = $filter->getConditionsArray();
        $this->assertCount(2,$array);
    }
}
