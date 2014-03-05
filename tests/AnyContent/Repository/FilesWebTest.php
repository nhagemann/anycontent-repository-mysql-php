<?php

namespace AnyContent\Repository;

use Silex\WebTestCase;

class FilesWebTest extends WebTestCase
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



    public function testBasicOperations()
    {
        $client = $this->createClient();

        $client->request('DELETE','/1/example/files/Test');
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertTrue($result);

        $client->request('GET', '/1/example/file/Test/test.txt');
        $response = $client->getResponse();
        $this->assertEquals(404,$response->getStatusCode());

        $client->request('POST','/1/example/file/Test/test.txt');
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertFalse($result);

        $client->request('POST','/1/example/file/Test/test.txt',array(),array(),array(),'test');
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertTrue($result);

        $client->request('GET', '/1/example/file/Test/test.txt');
        $response = $client->getResponse();
        $this->assertEquals(200,$response->getStatusCode());
        $this->assertEquals('test',$response->getContent());

        $client->request('DELETE','/1/example/file/Test/test.txt');
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertTrue($result);

        $client->request('GET', '/1/example/file/Test/test.txt');
        $response = $client->getResponse();
        $this->assertEquals(404,$response->getStatusCode());


        $client->request('DELETE','/1/example/file/Test/test-not-existing.txt');
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertFalse($result);

        $client->request('DELETE','/1/example/files/Test');
    }


    public function testBinary()
    {
        $client = $this->createClient();

        $client->request('GET', '/1/example/file/len_std.jpg');
        $response = $client->getResponse();
        $this->assertEquals(200,$response->getStatusCode());
        $binary = $response->getContent();

        $client->request('POST','/1/example/file/Test/test.jpg',array(),array(),array(),$binary);
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertTrue($result);

        $client->request('DELETE','/1/example/files/Test');

    }

    public function testFolderOperations()
    {
        $client = $this->createClient();

        $client->request('POST','/1/example/file/Test/test.txt',array(),array(),array(),'test');
        $client->request('POST','/1/example/file/Test/A/test.txt',array(),array(),array(),'test');
        $client->request('POST','/1/example/file/Test/A/B/C/test.txt',array(),array(),array(),'test');

        $client->request('GET','/1/example/files/Test');
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertContains('A',$result['folders']);
        $this->assertArrayHasKey('test.txt',$result['files']);

        $client->request('GET','/1/example/files/Test/A/B');
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertContains('C',$result['folders']);
        $this->assertArrayNotHasKey('test.txt',$result['files']);

        $client->request('DELETE','/1/example/files/Test/A/B');

        $client->request('GET','/1/example/files/Test/A');
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertNotContains('B',$result['folders']);
        $this->assertArrayHasKey('test.txt',$result['files']);

        $client->request('DELETE','/1/example/files/Test');

        $client->request('POST','/1/example/files/Test/A/B');

        $client->request('GET','/1/example/files/Test/A');
        $response = $client->getResponse();
        $result = json_decode($response->getContent(), true);
        $this->assertContains('B',$result['folders']);
        $this->assertArrayNotHasKey('test.txt',$result['files']);

        $client->request('DELETE','/1/example/files/Test');
    }

}
