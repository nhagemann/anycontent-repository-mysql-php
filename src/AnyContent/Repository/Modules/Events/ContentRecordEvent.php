<?php

namespace AnyContent\Repository\Modules\Events;

use Symfony\Component\EventDispatcher\Event;
use CMDL\ContentTypeDefinition;

class ContentRecordEvent extends Event
{

    protected $currentRecordTableValues = null;

    protected $newRecordTableValues = null;

    /* @var ContentTypeDefinition */
    protected $definition = null;


    function __construct(ContentTypeDefinition $definition, $newRecordTableValues, $currentRecordTableValues = null)
    {
        $this->definition               = $definition;
        $this->currentRecordTableValues = $currentRecordTableValues;
        $this->newRecordTableValues     = $newRecordTableValues;

    }


    public function getValues()
    {
        return $this->newRecordTableValues;
    }


    /**
     * @return null
     */
    public function getCurrentRecordTableValues()
    {
        return $this->currentRecordTableValues;
    }


    /**
     * @return null
     */
    public function getNewRecordTableValues()
    {
        $this->newRecordTableValues['property_cxioid'] = microtime();

        return $this->newRecordTableValues;
    }


    /**
     * @return \CMDL\ContentTypeDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }


    public function getCurrentRecordTableValue($key, $property = true)
    {
        if ($property)
        {
            $key = 'property_' . $key;
        }

        if (array_key_exists($key, $this->currentRecordTableValues))
        {
            return $this->currentRecordTableValues[$key];
        }

        return false;
    }


    public function getNewRecordTableValue($key, $property = true)
    {

        if ($property)
        {
            $key = 'property_' . $key;
        }

        if (array_key_exists($key, $this->newRecordTableValues))
        {
            return $this->newRecordTableValues[$key];
        }

        return false;
    }


    public function setRecordTableValue($key, $value, $property = true)
    {
        if ($property)
        {
            $key = 'property_' . $key;
        }

        $this->newRecordTableValues[$key] = $value;

    }
}