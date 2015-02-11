<?php

use Silex\WebTestCase;

class ContentManagerWebTest extends WebTestCase
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


    public function testProtectedPropertiese()
    {

        $client = $this->createClient();

        $client->request('GET', '/1/example/content/example04/record/1');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('record', $result);

        $record = $result['record'];

        $this->assertEquals('test', $record['properties']['protected']);

        $record['properties']['protected'] = 'update';

        $json = json_encode($record);
        $client->request('POST', '/1/example/content/example04/records', array( 'record' => $json ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $id = json_decode($response->getContent());
        $this->assertEquals('1', $id);

        $client->request('GET', '/1/example/content/example04/record/1');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('record', $result);

        $record = $result['record'];
        $this->assertEquals('test', $record['properties']['protected']);

        unset($record['properties']['protected']);

        $json = json_encode($record);
        $client->request('POST', '/1/example/content/example04/records', array( 'record' => $json ));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $id = json_decode($response->getContent());
        $this->assertEquals('1', $id);

        $client->request('GET', '/1/example/content/example04/record/1');
        $response = $client->getResponse();

        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('record', $result);

        $record = $result['record'];

        $this->assertEquals('test', $record['properties']['protected']);

        return;
    }
}

