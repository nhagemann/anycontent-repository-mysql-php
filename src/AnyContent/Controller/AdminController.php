<?php

namespace AnyContent\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use AnyContent\Controller\BaseController;

use AnyContent\Repository\Repository;

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
}