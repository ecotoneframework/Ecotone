<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRevision;

/**
 * licence Apache-2.0
 */
final class RegisterPerson
{
    public function __construct(
        private string $personId,
        private string $type
    ) {
    }

    public function getPersonId(): string
    {
        return $this->personId;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
