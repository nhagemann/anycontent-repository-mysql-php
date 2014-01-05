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

    public static function scan(Application $app, Request $request, $repositoryName, $workspace, $path = '')
    {

        $result = array();

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            /** @var FilesManager $filesManager */
            $filesManager = $repository->getFilesManager();

            $result['folders'] = $filesManager->getFolders($path);

            $result['files'] = $filesManager->getFiles($path);

        }

        return $app->json($result);

    }
}