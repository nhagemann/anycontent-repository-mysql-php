<?php

namespace AnyContent\Repository;

use AnyContent\Repository\Modules\Core\Application\Application;
use AnyContent\Repository\Config;
use AnyContent\Repository\Database;
use AnyContent\Repository\Modules\Core\Repositories\ContentManager;
use AnyContent\Repository\RepositoryManager;
use AnyContent\Repository\Repository;
use AnyContent\Repository\Entity\Filter;
use Silex\WebTestCase;

// This test must run after FilterTest.php, check if the repository has all relevant records

class FilterWebTest extends WebTestCase
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


    public function testDeprecatedFilters()
    {
        $client = $this->createClient();

        // test valid number of records

        $client->request('GET', '/1/example/content/example01/records');
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('records', $result);
        $this->assertCount(3, $result['records']);

        // test 1 deprecated filter

        $filter = array( 0 => array( array( 'name', '=', 'Differing Name' ) ) );
        $filter = array( 'filter' => $filter );

        $client->request('GET', '/1/example/content/example01/records?' . http_build_query($filter));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('records', $result);
        $this->assertCount(1, $result['records']);

        // test 2 deprecated filter

        $filter = array( 0 => array( array( 'name', '=', 'New Record 1' ) ), 1 => array( array( 'source', '=', 'a' ) ) );
        $filter = array( 'filter' => $filter );

        $client->request('GET', '/1/example/content/example01/records?' . http_build_query($filter));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('records', $result);
        $this->assertCount(1, $result['records']);

        $filter = array( 0 => array( array( 'name', '=', 'New Record 1' ) ), 1 => array( array( 'source', '=', 'b' ) ) );
        $filter = array( 'filter' => $filter );

        $client->request('GET', '/1/example/content/example01/records?' . http_build_query($filter));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('records', $result);
        $this->assertCount(0, $result['records']);

    }


    public function notestSimpleFilters()
    {
        $client = $this->createClient();

        $query = 'name >< Record , name = Differing Name';

        $client->request('GET', '/1/example/content/example01/records?' . http_build_query(array( 'filter' => $query )));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('records', $result);
        $this->assertCount(3, $result['records']);

        $query = 'name = Differing Name';

        $client->request('GET', '/1/example/content/example01/records?' . http_build_query(array( 'filter' => $query )));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('records', $result);
        $this->assertCount(1, $result['records']);

        $query = 'name = "New Record 1" + source = a';

        $client->request('GET', '/1/example/content/example01/records?' . http_build_query(array( 'filter' => $query )));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('records', $result);
        $this->assertCount(1, $result['records']);

        $query = 'name = "New Record 1" + source = b';

        $client->request('GET', '/1/example/content/example01/records?' . http_build_query(array( 'filter' => $query )));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('records', $result);
        $this->assertCount(0, $result['records']);

       /* $query = 'name = "New Record 1" + source = a, source=c';

        $client->request('GET', '/1/example/content/example01/records?' . http_build_query(array( 'filter' => $query )));
        $response = $client->getResponse();
        $this->assertTrue($response->isOk());
        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('records', $result);
        $this->assertCount(2, $result['records']);*/
    }

}
