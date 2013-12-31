<?php

namespace AnyContent\Repository\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use AnyContent\Repository\Controller\BaseController;

use AnyContent\Repository\Repository;
use AnyContent\Repository\Entity\ContentTypeInfo;

class RepositoryController extends BaseController
{

    public static function index(Application $app, Request $request, $repositoryName, $workspace = 'default', $language = 'none', $timeshift = 0)
    {

        if ($request->query->has('timeshift'))
        {
            $timeshift = (int)$request->get('timeshift');
        }

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            $contentTypesList = $repository->getContentTypesList();

            /** @var ContentTypeInfo $contentTypeInfo */
            foreach ($contentTypesList as $contentTypeName => $contentTypeInfo)
            {
                $manager = $repository->getContentManager($contentTypeName);
                if ($manager)
                {
                    $info = $manager->countRecords($workspace,null,$language,$timeshift);
                    $contentTypeInfo->setCount($info['count']);
                    $contentTypeInfo->setLastchangeContent($info['lastchange']);
                }
            }


            $result = array( 'content' => $contentTypesList, 'config' => array() );

            return $app->json($result);
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
                return $app->json(array( 'cmdl' => $cmdl ));
            }

            return self::notFoundError($app, self::UNKNOWN_CONTENTTYPE, $repositoryName, $contentTypeName);
        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }

}