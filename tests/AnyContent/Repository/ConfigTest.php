<?php

namespace AnyContent\Repository;

use Silex\Application;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;
use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\Service\ConfigManager;
use AnyContent\Repository\Repository;

//use AnyContent\Client\Record;

class ConfigTest extends \PHPUnit_Framework_TestCase
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


    public function testConfigTypes()
    {
        /**
         * @var ConfigManager
         */
        $manager = $this->repository->getConfigManager('example01');

        $this->assertTrue($manager->hasConfigType('config1'));
        $this->assertTrue($manager->hasConfigType('config2'));
        $this->assertFalse($manager->hasConfigType('config3'));

    }


    public function testSaveConfigRecords()
    {
        /**
         * @var ConfigManager
         */
        $manager = $this->repository->getConfigManager('example01');

        $configTypeDefinition = $manager->getConfigTypeDefinition('config1');

        $properties = array( 'city' => 'Frankfurt', 'country' => 'Germany' );
        $manager->saveConfig($configTypeDefinition, $properties);
    }

}
