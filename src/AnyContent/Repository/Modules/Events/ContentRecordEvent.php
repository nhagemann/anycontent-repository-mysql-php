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


    function __construct(ContentTypeDefinition $definition, &$newRecordTableValues, &$currentRecordTableValues = null)
    {
        $this->definition               = $definition;
        $this->currentRecordTableValues = $currentRecordTableValues;
        $this->newRecordTableValues     = $newRecordTableValues;
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
        return $this->newRecordTableValues;
    }


    /**
     * @return \CMDL\ContentTypeDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

}