<?php

namespace AnyContent\Repository\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use AnyContent\Repository\Controller\BaseController;

use AnyContent\Repository\FilesManager;

use AnyContent\Repository\Repository;
use CMDL\Util;

class FilesController extends BaseController
{

    public static function scan(Application $app, Request $request, $repositoryName, $path = '')
    {

        $result = false;

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            /** @var FilesManager $filesManager */
            $filesManager = $repository->getFilesManager();

            $folders = $filesManager->getFolders($path);

            if ($folders !== false)
            {
                $result            = array();
                $result['folders'] = $folders;

                $result['files'] = $filesManager->getFiles($path);
            }

        }

        return $app->json($result);

    }


    public static function binary(Application $app, Request $request, $repositoryName, $path)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {
            /** @var FilesManager $filesManager */
            $filesManager = $repository->getFilesManager();

            $binary = $filesManager->getFile($path);
            if ($binary !== false)
            {
                return $binary;
            }

            return self::notFoundError($app, self::FILE_NOT_FOUND);
        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);

    }


    public static function postFile(Application $app, Request $request, $repositoryName, $path)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            $binary = false;

            if ($request->request->has('binary'))
            {
                $binary = $request->get('binary');

            }

            if ($binary)
            {

                /** @var FilesManager $filesManager */
                $filesManager = $repository->getFilesManager();

                if ($filesManager->saveFile($path, $binary))
                {
                    return $app->json(true);
                }
            }

            return $app->json(false);

        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }


    public static function createFolder(Application $app, Request $request, $repositoryName, $path)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            /** @var FilesManager $filesManager */
            $filesManager = $repository->getFilesManager();

            if ($filesManager->createFolder($path))
            {
                return $app->json(true);
            }

            return $app->json(false);

        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }


    public static function deleteFile(Application $app, Request $request, $repositoryName, $path)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {
            /** @var FilesManager $filesManager */
            $filesManager = $repository->getFilesManager();

            if ($filesManager->deleteFile($path))
            {
                return $app->json(true);
            }

            return $app->json(false);

        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }


    public static function deleteFiles(Application $app, Request $request, $repositoryName, $path)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {
            /** @var FilesManager $filesManager */
            $filesManager = $repository->getFilesManager();

            if ($filesManager->deleteFolder($path))
            {
                return $app->json(true);
            }

            return $app->json(false);

        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }
}