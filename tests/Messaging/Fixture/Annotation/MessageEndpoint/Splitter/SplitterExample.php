<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Splitter;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Annotation\Splitter;

class SplitterExample
{
    /**
     * @param string $payload
     * @return array
     * @Splitter(endpointId="testId", inputChannelName="inputChannel", outputChannelName="outputChannel", parameterConverters={
     *      @Payload(parameterName="payload")
     * }, requiredInterceptorNames={"someReference"})
     */
    public function split(string $payload) : array
    {
        return [];
    }
}