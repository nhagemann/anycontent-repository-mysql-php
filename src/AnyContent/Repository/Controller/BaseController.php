<?php

namespace AnyContent\Repository\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

class BaseController
{

    const UNKNOWN_REPOSITORY  = 1;
    const UNKNOWN_CONTENTTYPE = 2;
    const RECORD_NOT_FOUND    = 3;


    protected function notFoundError($app, $code, $s1 = null, $s2 = null, $s3 = null, $s4 = null, $s5 = null)
    {

        switch ($code)
        {
            case self::UNKNOWN_REPOSITORY:
                $message = sprintf('Unknown repository %s.', $s1);
                break;
            case self::UNKNOWN_CONTENTTYPE:
                $message = sprintf('Unknown content type %s within repository %s.', $s2, $s1);
                break;
            case self::RECORD_NOT_FOUND:
                $message = sprintf('Record with id %s not found for content type %s within repository %s.', $s3, $s2, $s1);
                break;
            default:
                $message = 'Unknown error';
                break;
        }
        $error = array( 'error' => array( 'code' => $code, 'message' => $message ) );

        return $app->json($error, 404);
    }


    protected function badRequest($app, $message = null)
    {
        if (!$message)
        {
            $message = 'Bad request';
        }

        $error = array( 'error' => array( 'code' => 400, 'message' => $message ) );

        return $app->json($error, 400);
    }
}