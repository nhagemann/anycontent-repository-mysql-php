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

    public function testProtectedProperties()
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
        $client->request('POST', '/1/example/content/example04/records', array('record' => $json));
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
        $client->request('POST', '/1/example/content/example04/records', array('record' => $json));
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

    public function testSaveRecords1()
    {
        $client = $this->createClient();

        $this->app['db']->truncateContentType('example', 'example01');

        for ($i = 1; $i <= 5; $i++) {
            $record                       = array();
            $record['properties']['name'] = 'Record ' . $i;
            $json                         = json_encode($record);
            $client->request('POST', '/1/example/content/example01/records', array('record' => $json));
            $response = $client->getResponse();
            $this->assertTrue($response->isOk());
        }

        $client->request('GET', '/1/example/content/example01/records');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $result  = json_decode($response->getContent(), true);
        $records = $result['records'];
        $this->assertCount(5, $records);

        for ($i = 1; $i <= 5; $i++) {
            $record = $records[$i];
            $this->assertEquals($i, $record['id']);
            $this->assertEquals('Record ' . $i, $record['properties']['name']);
        }
    }

    // Now the same with one request

    public function testSaveRecords2()
    {
        $client = $this->createClient();

        $this->app['db']->truncateContentType('example', 'example01');

        $records = array();

        for ($i = 1; $i <= 5; $i++) {
            $record                       = array();
            $record['properties']['name'] = 'Record ' . $i;

            $records[] = $record;
        }

        $json = json_encode($records);

        $client->request('POST', '/1/example/content/example01/records', array('records' => $json));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $client->request('GET', '/1/example/content/example01/records');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $result  = json_decode($response->getContent(), true);
        $records = $result['records'];
        $this->assertCount(5, $records);

        for ($i = 1; $i <= 5; $i++) {
            $record = $records[$i];
            $this->assertEquals($i, $record['id']);
            $this->assertEquals('Record ' . $i, $record['properties']['name']);
        }
    }

    public function testDeleteRecords()
    {
        $client = $this->createClient();

        $record                       = array();
        $record['properties']['name'] = 'Record Other Workspace';
        $json = json_encode($record);

        $client->request('POST', '/1/example/content/example01/records/live', array('record' => $json));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());


        $record                       = array();
        $record['properties']['name'] = 'Record Other Workspace';
        $json = json_encode($record);

        $client->request('POST', '/1/example/content/example01/records', array('record' => $json,'language'=>'en'));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());


        $client->request('DELETE', '/1/example/content/example01/record/1');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $client->request('GET', '/1/example/content/example01/records');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $result  = json_decode($response->getContent(), true);
        $records = $result['records'];
        $this->assertCount(4, $records);

        $client->request('DELETE', '/1/example/content/example01/records');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $client->request('GET', '/1/example/content/example01/records');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $result  = json_decode($response->getContent(), true);
        $records = $result['records'];
        $this->assertCount(0, $records);
    }

}

