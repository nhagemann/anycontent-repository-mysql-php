<?php
namespace AnyContent\Repository\Modules\Core\Repositories;

class RepositoryException extends \Exception
{

    const REPOSITORY_RECORD_NOT_FOUND             = 1;
    const REPOSITORY_INVALID_PROPERTIES           = 2;
    const REPOSITORY_MISSING_MANDATORY_PROPERTIES = 3;
    const REPOSITORY_BAD_PARAMS                   = 4;
    const REPOSITORY_INVALID_LANGUAGE             = 5;
    const REPOSITORY_INVALID_WORKSPACE            = 6;
    const REPOSITORY_INVALID_NAMES                = 7;

}