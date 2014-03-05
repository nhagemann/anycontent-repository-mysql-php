<?php

use Silex\WebTestCase;

class ConfigWebTest extends WebTestCase
{

    /**
     * @link http://silex.sensiolabs.org/doc/testing.html
     * @link http://whateverthing.com/blog/2013/09/01/quick-web-apps-part-five/
     *
     * @return mixed|\Symfony\Component\HttpKernel\HttpKernel
     */
    public function createApplication()
    {
        $app_env      = 'test';
        $app          = require __DIR__ . '/../../../web/index.php';
        $app['debug'] = true;
        $app['exception_handler']->disable();

        return $app;
    }


    public function testGetAndSave()
    {

        $client = $this->createClient();

        $client->request('GET', '/1/example/config');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('config1', $result);

        $client->request('GET', '/1/example/config/config1/record');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('record', $result);
        $record = $result['record'];
        $this->assertArrayHasKey('properties', $record);
        $properties = $record['properties'];
        $this->assertArrayHasKey('city', $properties);

        $record               = array();
        $record['properties'] = array( 'city' => 'Madrid', 'country' => 'Spain' );

        $json = json_encode($record);
        $client->request('POST', '/1/example/config/config1/record', array( 'record' => $json ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
    }
}

