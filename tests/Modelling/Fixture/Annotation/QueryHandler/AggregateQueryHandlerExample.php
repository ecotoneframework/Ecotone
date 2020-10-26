<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 30.03.18
 * Time: 09:28
 */

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\QueryHandler;

#[Aggregate]
class AggregateQueryHandlerExample
{
    #[AggregateIdentifier]
    private string $id;

    #[QueryHandler(endpointId: "some-id")]
    public function doStuff(SomeQuery $query) : SomeResult
    {
        return new SomeResult();
    }

    public function doAnotherAction(SomeQuery $query) : SomeResult
    {

    }
}