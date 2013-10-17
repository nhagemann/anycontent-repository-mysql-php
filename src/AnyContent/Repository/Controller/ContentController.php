<?php

namespace AnyContent\Repository\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use AnyContent\Repository\Controller\BaseController;

use AnyContent\Repository\Repository;
use AnyContent\Repository\RepositoryException;

class ContentController extends BaseController
{

    public static function post(Application $app, Request $request, $repositoryName, $contentTypeName, $workspace = 'default', $clippingName = 'default', $language = 'none')
    {

        $record = false;

        if ($request->request->has('record'))
        {
            $record = $request->get('record');
            $record = json_decode($record, true);
        }

        if ($record)
        {

            /** @var $repository Repository */
            $repository = $app['repos']->get($repositoryName);
            if ($repository)
            {
                $manager = $repository->getContentManager($contentTypeName);

                try
                {
                    $id = $manager->saveRecord($record, $workspace, $clippingName, $language);
                }
                catch (RepositoryException $e)
                {
                    return self::badRequest($app, 'Bad Request - ' . $e->getMessage());
                }

                return $app->json($id);
            }

            return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
        }

        return self::badRequest($app);
        //return $app->json('true');
    }
}