<?php

namespace AnyContent\Repository\Modules\Core\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AnyContent\Repository\Modules\Core\Application\Application;

class BaseController
{

    const BAD_REQUEST                 = 1;
    const UNKNOWN_REPOSITORY          = 2;
    const UNKNOWN_CONTENTTYPE         = 3;
    const RECORD_NOT_FOUND            = 4;
    const UNKNOWN_CONFIGTYPE          = 5;
    const CONFIG_NOT_FOUND            = 6;
    const FILE_NOT_FOUND              = 7;
    const UNKNOWN_PROPERTY            = 8;
    const UNKNOWN_ERROR               = 9;
    const MISSING_MANDATORY_PARAMETER = 10;
    const SERVER_ERROR                = 11;


    protected function notFoundError($app, $code = self::UNKNOWN_ERROR, $s1 = null, $s2 = null, $s3 = null, $s4 = null, $s5 = null)
    {

        switch ($code)
        {
            case self::UNKNOWN_REPOSITORY:
                $message = sprintf('Unknown repository %s.', $s1);
                break;
            case self::UNKNOWN_CONTENTTYPE:
                $message = sprintf('Unknown content type %s within repository %s.', $s2, $s1);
                break;
            case self::UNKNOWN_CONFIGTYPE:
                $message = sprintf('Unknown config type %s within repository %s.', $s2, $s1);
                break;
            case self::RECORD_NOT_FOUND:
                $message = sprintf('Record with id %s not found for content type %s within repository %s.', $s3, $s2, $s1);
                break;
            case self::CONFIG_NOT_FOUND:
                $message = sprintf('No existing config with id %s within repository %s.', $s2, $s1);
                break;
            case self::FILE_NOT_FOUND:
                $message = sprintf('File not found');
                break;
            default:
                $message = 'Unknown error';
                break;
        }
        $error = array( 'error' => array( 'code' => $code, 'message' => $message ) );

        return $app->json($error, 404);
    }


    protected function badRequest($app, $code = self::BAD_REQUEST, $s1 = null, $s2 = null, $s3 = null, $s4 = null, $s5 = null)
    {

        switch ($code)
        {
            case self::UNKNOWN_PROPERTY:

                $message = sprintf('Unknown property %s for view %s of content type %s within repository %s.', $s4, $s3, $s2, $s1);
                break;
            default:
                $message = 'Unknown error';
                break;
        }
        $error = array( 'error' => array( 'code' => $code, 'message' => $message ) );

        return $app->json($error, 400);
    }


    protected function serverError($app, $code = self::SERVER_ERROR, $s1 = null, $s2 = null, $s3 = null, $s4 = null, $s5 = null)
    {

        switch ($code)
        {
            default:
                $message = 'Internal Server Error';
                break;
        }
        $error = array( 'error' => array( 'code' => $code, 'message' => $message ) );

        return $app->json($error, 500);
    }
}