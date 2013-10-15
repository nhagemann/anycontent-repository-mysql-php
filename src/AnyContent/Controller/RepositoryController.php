<?php

namespace AnyContent\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use AnyContent\Controller\BaseController;

use AnyContent\Repository\Repository;

class RepositoryController extends BaseController
{

    public static function index(Application $app, Request $request, $repositoryName)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            return $app->json($repository->getContentTypesList());
        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }


    public static function cmdl(Application $app, Request $request, $repositoryName, $contentTypeName)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            $cmdl = $repository->getCMDL($contentTypeName);
            if ($cmdl)
            {
                return $app->json(array('cmdl'=>$cmdl));
            }
            return self::notFoundError($app, self::UNKNOWN_CONTENTTYPE, $repositoryName,$contentTypeName);
        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }

}