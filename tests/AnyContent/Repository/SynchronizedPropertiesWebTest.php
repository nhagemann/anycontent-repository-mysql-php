<?php

use Silex\WebTestCase;

class SynchronizedPropertiesWebTest extends WebTestCase
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


    public function testSync()
    {
        $url    = '/1/example';
        $client = $this->createClient();
        $client->request('GET', $url);
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $result = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('example01', $result['content']);

        $record    = new Record();
        $languages = array( 'default', 'en', 'es' );

        foreach ($languages as $language)
        {

            $record->properties['source'] = $language;

            $json = json_encode($record);

            $client->request('POST', '/1/example/content/example01/records/default/default', array( 'record' => $json, 'language' => $language ));
            $response = $client->getResponse();

            $id = json_decode($response->getContent(), true);

            $record->id = $id;
        }

        foreach ($languages as $language)
        {

            $client->request('GET', '/1/example/content/example01/record/' . $id, array( 'language' => $language ));
            $response = $client->getResponse();
            $this->assertTrue($response->isOk());
            $result = json_decode($response->getContent(), true);

            $value = $result['record']['properties']['source'];
            $this->assertEquals('es', $value);

        }
    }
}


class Record
{

    public $id = null;

    public $properties = array();

    public $revision = 1;
    public $revisionTimestamp = null;

    public $hash = null;

    public $position = null;
    public $parentRecordId = null;
    public $levelWithinSortedTree = null;

    public $creationUserInfo;
    public $lastChangeUserInfo;
}