<?php

namespace AnyContent\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

class RepositoryController
{
    public static function index(Application $app, Request $request,$repositoryName)
    {


        $repository = $app['repos']->get($repositoryName);
        if ($repository)
        {
            var_dump ($repository);
        }

        return new Response();
    }
}