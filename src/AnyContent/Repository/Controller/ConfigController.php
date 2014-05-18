<?php

namespace AnyContent\Repository\Controller;

use AnyContent\Repository\Entity\Filter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AnyContent\Repository\Application;

use AnyContent\Repository\Controller\BaseController;

use AnyContent\Repository\Repository;
use AnyContent\Repository\RepositoryException;

use CMDL\Util;

class ConfigController extends BaseController
{

    public static function index(Application $app, Request $request, $repositoryName)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {
            return $app->json($repository->getConfigTypesList());
        }

        return $app->json(false);
    }


    public static function cmdl(Application $app, Request $request, $repositoryName, $configTypeName)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            $cmdl = $repository->getConfigCMDL($configTypeName);
            if ($cmdl)
            {
                return $app->json(array( 'cmdl' => $cmdl ));
            }

            return self::notFoundError($app, self::UNKNOWN_CONFIGTYPE, $repositoryName, $configTypeName);
        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);

    }




    public static function getConfig(Application $app, Request $request, $repositoryName, $configTypeName, $workspace = 'default', $language = 'default', $timeshift = 0)
    {

        /** @var $repository Repository */
        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {

            $manager = $repository->getConfigManager();

            if ($manager)
            {

                if ($manager->hasConfigType($configTypeName))
                {
                    try
                    {
                        if ($request->query->has('language'))
                        {
                            $language = $request->get('language');
                        }
                        if ($request->query->has('timeshift'))
                        {
                            $timeshift = (int)$request->get('timeshift');
                        }

                        $record = $manager->getConfig($configTypeName, $workspace, $language, $timeshift);

                        if ($record)
                        {
                            return $app->json($record);
                        }


                    }
                    catch (RepositoryException $e)
                    {

                    }
                    return self::notFoundError($app, self::CONFIG_NOT_FOUND, $repositoryName, $configTypeName);
                }
                else
                {
                    return self::notFoundError($app, self::UNKNOWN_CONFIGTYPE, $repositoryName, $configTypeName);
                }
            }

        }

        return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
    }


    public static function post(Application $app, Request $request, $repositoryName, $configTypeName, $workspace = 'default', $language = 'default')
    {

        if ($request->request->has('record'))
        {
            $record     = $request->get('record');
            $record     = json_decode($record, true);
            $properties = array();

            if (array_key_exists('properties', $record))
            {
                $properties = $record['properties'];
            }

            /** @var $repository Repository */
            $repository = $app['repos']->get($repositoryName);
            if ($repository)
            {
                $manager = $repository->getConfigManager();

                if ($manager->hasConfigType($configTypeName))
                {

                    $configTypeDefinition = $manager->getConfigTypeDefinition($configTypeName);
                    if ($configTypeDefinition)
                    {
                        if ($request->request->has('language'))
                        {
                            $language = $request->request->get('language');
                        }
                        try
                        {
                            $manager->saveConfig($configTypeDefinition, $properties, $workspace, $language);
                        }
                        catch (RepositoryException $e)
                        {
                            return self::badRequest($app, 'Bad Request - ' . $e->getMessage());
                        }

                        return $app->json(true);
                    }
                }
                else
                {
                    return self::notFoundError($app, self::UNKNOWN_CONFIGTYPE, $repositoryName, $configTypeName);
                }
            }
            else
            {
                return self::notFoundError($app, self::UNKNOWN_REPOSITORY, $repositoryName);
            }
        }

        return self::badRequest($app);
    }


    public static function getConfigShortCut(Application $app, Request $request, $repositoryName, $configTypeName)
    {
        $url = '/1/' . $repositoryName . '/config/' . $configTypeName . '/record';

        return new RedirectResponse($url, 303);
    }

}
