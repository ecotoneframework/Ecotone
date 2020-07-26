<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\AddNotificationTimestamp;

use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

class AddNotificationTimestamp
{
    private $currentTime = null;

    /**
     * @CommandHandler("changeCurrentTime")
     */
    public function setTime(string $currentTime) : void
    {
        $this->currentTime = $currentTime;
    }

    /**
     * @After(pointcut="Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\Logger", changeHeaders=true)
     */
    public function add(array $events, array $metadata) : array
    {
        return array_merge(
            $metadata,
            ["notificationTimestamp" => $this->currentTime]
        );
    }
}