<?php


namespace Ecotone\Tests\Messaging\Fixture\Service;

use Ecotone\Messaging\Support\Assert;
use Ramsey\Uuid\UuidInterface;

class ServiceWithUuidArgument
{
    public function execute(UuidInterface $uuid) : void
    {

    }
}