<?php

namespace Ecotone\Tests\Messaging\Fixture\Behat\Shopping;

/**
 * Class OrderRequest
 * @package Ecotone\Tests\Messaging\Fixture\Behat\Shopping
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReserveRequest
{
    /**
     * @var string
     */
    private $name;

    /**
     * OrderRequest constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name() : string
    {
        return $this->name;
    }
}