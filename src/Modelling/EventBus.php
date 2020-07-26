<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;

interface EventBus
{
    const CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.event_by_object";
    const CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.event_by_name";

    /**
     * Entrypoint for events, when you access to instance of the command
     *
     * @param object $event instance of command
     *
     * @return mixed
     *
     * @MessageGateway(requestChannel=EventBus::CHANNEL_NAME_BY_OBJECT)
     */
    public function send(object $event);

    /**
     * Entrypoint for events, when you access to instance of the command
     *
     * @param object $event instance of command
     * @param array  $metadata
     *
     * @return mixed
     *
     * @MessageGateway(
     *     requestChannel=EventBus::CHANNEL_NAME_BY_OBJECT,
     *     parameterConverters={
     *         @Payload(parameterName="event"),
     *         @Headers(parameterName="metadata")
     *     }
     * )
     */
    public function sendWithMetadata(object $event, array $metadata);


    /**
     * @param string $name
     * @param string $sourceMediaType
     * @param mixed  $data
     *
     * @return mixed
     *
     * @MessageGateway(
     *     requestChannel=EventBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Header(parameterName="name", headerName=EventBus::CHANNEL_NAME_BY_NAME),
     *          @Header(parameterName="sourceMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @Payload(parameterName="data")
     *     }
     * )
     */
    public function convertAndSend(string $name, string $sourceMediaType, $data);

    /**
     * @param string $name
     * @param string $sourceMediaType
     * @param mixed  $data
     * @param array  $metadata
     *
     * @return mixed
     *
     * @MessageGateway(
     *     requestChannel=EventBus::CHANNEL_NAME_BY_NAME,
     *     parameterConverters={
     *          @Headers(parameterName="metadata"),
     *          @Header(parameterName="name", headerName=EventBus::CHANNEL_NAME_BY_NAME),
     *          @Header(parameterName="sourceMediaType", headerName=MessageHeaders::CONTENT_TYPE),
     *          @Payload(parameterName="data")
     *     }
     * )
     */
    public function convertAndSendWithMetadata(string $name, string $sourceMediaType, $data, array $metadata);
}