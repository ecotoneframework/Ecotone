<?php


namespace Tests\Ecotone\Messaging\Fixture\Handler\Property;


class DifferentTypeAndDocblockProperty
{
    /**
     * @var \stdClass
     */
    private int $integer;

    /**
     * @var \stdClass
     */
    private $unknown;
}