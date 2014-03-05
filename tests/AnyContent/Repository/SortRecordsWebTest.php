<?php

use Silex\WebTestCase;

class SortRecordsWebTest extends WebTestCase
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


    public function testSorting()
    {
        $url    = '/1/example';
        $client = $this->createClient();
        $client->request('GET', $url);
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());

        $result = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('example01',$result['content']);


        // This test must run after SortRecordsTest.php, check if the repository has all relevant records
        $client->request('GET', $url.'/content/example01/records');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(10,$result['records']);

        $client->request('GET', $url.'/content/example01/records?subset=0');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(9,$result['records']);

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



        $client->request('POST', $url.'/content/example01/sort-records',array('list'=>json_encode($list)));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertTrue($result);


        // Now do some of the sorting queries of the original SortRecordsTest via client

        $client->request('GET', $url.'/content/example01/records?subset=1');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(3,$result['records']);

        $client->request('GET', $url.'/content/example01/records?subset=4');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(6,$result['records']);

        $client->request('GET', $url.'/content/example01/records?subset=4,0');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(5,$result['records']);

        $client->request('GET', $url.'/content/example01/records?subset=4,0,1');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(2,$result['records']);

        $client->request('GET', $url.'/content/example01/records?subset=5,0');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(2,$result['records']);

        // some sorting within live/es

        $list   = array();
        $list[] = array( 'id' => 11, 'parent_id' => 0 );
        $list[] = array( 'id' => 12, 'parent_id' => 0 );
        $list[] = array( 'id' => 13, 'parent_id' => 0 );

        $client->request('POST', $url.'/content/example01/sort-records/live',array('list'=>json_encode($list),'language'=>'es'));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertTrue($result);

        $client->request('GET', $url.'/content/example01/records/live?subset=0&language=es');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(3,$result['records']);

     }
}

