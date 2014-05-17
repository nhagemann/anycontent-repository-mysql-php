<?php

namespace AnyContent\Repository;

use Silex\Application;
use AnyContent\Repository\Service\Config;
use AnyContent\Repository\Service\Database;
use AnyContent\Repository\Service\RepositoryManager;
use AnyContent\Repository\Service\ContentManager;
use AnyContent\Repository\Repository;
use AnyContent\Repository\Entity\Filter;

class FilesTest extends \PHPUnit_Framework_TestCase
{

    protected $app;

    /** @var $repository Repository */
    protected $repository;


    public function setUp()
    {

        $app           = new Application();
        $app['config'] = new Config($app);

        $cacheDriver = new  \Doctrine\Common\Cache\ApcCache();
        $app['cache'] = $cacheDriver;

        $app['repos']  = new RepositoryManager($app);
        $app['db']     = new Database($app);

        $this->app = $app;

        $this->repository = $this->app['repos']->get('example');

        /** @var FilesManager $filesManager */
        $filesManager = $this->repository->getFilesManager();


        $this->assertTrue($filesManager->deleteFolder('Test'));

    }


    public function testFoldersExtraction()
    {

        /** @var FilesManager $filesManager */
        $filesManager = $this->repository->getFilesManager();

        $result = $filesManager->getFolders('');
        $this->assertCount(2, $result);

        $result = $filesManager->getFolders('Music/');
        $this->assertCount(3, $result);

        $result = $filesManager->getFolders('Music/Alternative');
        $this->assertCount(0, $result);

        $result = $filesManager->getFolders('Music/Pop');
        $this->assertCount(0, $result);

        $result = $filesManager->getFolders('Music/Jazz');
        $this->assertFalse($result);
    }


    public function testFileUpload()
    {
        /** @var FilesManager $filesManager */
        $filesManager = $this->repository->getFilesManager();

        $file = $filesManager->getFile('Test/test.txt');
        $this->assertFalse($file);

        $filesManager->saveFile('Test/test.txt', 'test');

        $file = $filesManager->getFile('Test/test.txt');
        $this->assertEquals('test', $file);

        $binary = $filesManager->getFile('len_std.jpg');

        $filesManager->saveFile('Test/test.jpg', $binary);

        $file = $filesManager->getFile('Test/test.jpg');
        $this->assertEquals($binary, $file);

        $filesManager->deleteFolder('Test');


    }


    /**
     * This test has no "real" assertions, but was necessary during development to check the right handling of
     * system files like .folder (which are not accessible through the FilesManager)
     */
    public function testFolderDelete()
    {
        /** @var FilesManager $filesManager */
        $filesManager = $this->repository->getFilesManager();

        $filesManager->saveFile('Test/test.txt', 'test');

        $filesManager->saveFile('Test/A/test.txt', 'test');

        $filesManager->saveFile('Test/A/B/C/test.txt', 'test');

        $file = $filesManager->getFile('Test/.folder');
        $this->assertFalse($file);

        $filesManager->deleteFolder('Test');

        $file = $filesManager->getFile('Test/A/B/C/test.txt');
        $this->assertFalse($file);
    }


    public function testCreationOfEmptyFolder()
    {
        /** @var FilesManager $filesManager */
        $filesManager = $this->repository->getFilesManager();

        $filesManager->createFolder('Test/A/B');

        $result = $filesManager->getFolders('Test/A');
        $this->assertContains('B', $result);

        $filesManager->deleteFolder('Test/A/B');

        $result = $filesManager->getFolders('Test/A');
        $this->assertNotContains('B', $result);

        $filesManager->deleteFolder('Test');

        $result = $filesManager->getFolders('Test/A');
        $this->assertFalse($result);
    }
}
