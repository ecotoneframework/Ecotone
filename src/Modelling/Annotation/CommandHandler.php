<?php

namespace Ecotone\Modelling\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ramsey\Uuid\Uuid;

/**
 * Class CommandHandler
 * @package Ecotone\Modelling\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class CommandHandler extends InputOutputEndpointAnnotation
{
    /**
     * @var array
     */
    public $parameterConverters = [];
    /**
     * if endpoint is not interested in message's payload, set to true.
     * inputChannelName must be defined to connect with external channels
     *
     * @var boolean
     */
    public $ignorePayload = false;
    /**
     * If @Aggregate was not found, message can be dropped instead of throwing exception
     *
     * @var bool
     */
    public $dropMessageOnNotFound = false;
    /**
     * @var array
     */
    public $identifierMetadataMapping = [];

    public function __construct(array $values = [])
    {
        if (!isset($values["inputChannelName"])) {
            $this->inputChannelName = Uuid::uuid4()->toString();
        }

        parent::__construct($values);
    }
}