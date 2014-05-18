<?php

namespace AnyContent\Repository;

use AnyContent\Repository\Application;

use CMDL\ContentTypeDefinition;
use CMDL\Util;

use AnyContent\Repository\Repository;





class FilesManager
{

    /**
     * @var Repository
     */
    protected $repository = null;

    protected $adapter = null;


    public function __construct(Application $app, Repository $repository, $config)
    {

        $this->repository = $repository;

        $this->adapter = $app->getStorageAdapter($config, $repository->getName());

    }


    public function getFolders($path)
    {
        return $this->adapter->getFolders($path);
    }


    public function getFiles($path, $info = true)
    {
        return $this->adapter->getFiles($path, $info);

    }


    public function getFile($id)
    {
        return $this->adapter->getFile($id);
    }


    public function saveFile($id, $binary)
    {
        return $this->adapter->saveFile($id, $binary);
    }


    public function deleteFile($id)
    {
        return $this->adapter->deleteFile($id);
    }


    public function createFolder($path)
    {
        return $this->adapter->createFolder($path);
    }


    public function deleteFolder($path)
    {
        return $this->adapter->deleteFolder($path);

    }

}