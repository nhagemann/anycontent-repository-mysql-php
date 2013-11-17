<?php

namespace AnyContent\Repository\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use AnyContent\Repository\Helper;

class PrettyPrint
{

    public static function execute(Request $request, Response $response)
    {

        if ($response->headers->get('Content-Type') == 'application/json')
        {
            $response->setContent(Helper::prettyPrintJSON($response->getContent()));
        }

    }
}