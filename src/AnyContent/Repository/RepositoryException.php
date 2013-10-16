<?php
namespace AnyContent\Repository;

class RepositoryException extends \Exception
{
    const REPOSITORY_RECORD_NOT_FOUND = 1;
    const REPOSITORY_INVALID_PROPERTIES = 2;
    const REPOSITORY_MISSING_MANDATORY_PROPERTIES = 3;

}