<?php

namespace Test\Ecotone\Messaging\Fixture\MessageConverter;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Payload;

interface FakeMessageConverterGatewayExample
{
    /**
     * @param array $some
     * @param int $amount
     * @return \stdClass
     * @MessageGateway(
     *     requestChannel="requestChannel",
     *     parameterConverters={
     *          @Header(parameterName="some", headerName="some"),
     *          @Payload(parameterName="amount")
     *     }
     * )
     */
    public function execute(array $some, int $amount) : \stdClass;
}