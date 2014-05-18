<?php

namespace AnyContent\Repository\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AnyContent\Repository\Application;

use AnyContent\Repository\Controller\BaseController;

use AnyContent\Repository\Repository;
use CMDL\Util;

class AdminController extends BaseController
{

    public static function refresh(Application $app, Request $request, $repositoryName, $contentTypeName)
    {
        $repo = $app['repos']->get($repositoryName);

        if ($repo)
        {
            $contentType = $repo->getContentType($contentTypeName);
            if ($contentType)
            {
                if ($app['db']->refreshContentTypeTableStructure('example', $contentType))
                {
                    return $app->json(true);
                }
            }
        }

        return $app->json(false);
    }


    public static function delete(Application $app, Request $request, $repositoryName, $contentTypeName)
    {

        return $app->json($app['db']->deleteRepository($repositoryName, $contentTypeName));

    }
}