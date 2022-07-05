<?php
declare(strict_types=1);

namespace Tests\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\InMemoryStandardRepository;

#[Repository]
class OrderRepository extends InMemoryStandardRepository
{

}