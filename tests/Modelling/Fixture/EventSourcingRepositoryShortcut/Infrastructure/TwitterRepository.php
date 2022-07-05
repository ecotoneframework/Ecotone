<?php

namespace Tests\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut\Infrastructure;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\InMemoryStandardRepository;

#[Repository]
class TwitterRepository extends InMemoryEventSourcedRepository
{

}