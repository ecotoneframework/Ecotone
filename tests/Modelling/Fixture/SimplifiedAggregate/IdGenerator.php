<?php

namespace Ecotone\Tests\Modelling\Fixture\SimplifiedAggregate;

use Ramsey\Uuid\Uuid;

class IdGenerator
{
    private int $counter = 0;

    public function generate(): string
    {
        $this->counter++;

        return $this->counter;
    }
}