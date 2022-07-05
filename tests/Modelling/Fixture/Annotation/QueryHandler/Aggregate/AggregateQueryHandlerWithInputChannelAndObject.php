<?php


namespace Ecotone\Tests\Modelling\Fixture\Annotation\QueryHandler\Aggregate;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class AggregateQueryHandlerWithInputChannelAndObject
{
    #[QueryHandler("execute", "queryHandler")]
    public function execute(\stdClass $class) : int
    {

    }
}