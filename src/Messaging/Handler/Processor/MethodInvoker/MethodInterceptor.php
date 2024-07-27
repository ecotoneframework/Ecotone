<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

/**
 * Class Interceptor
 * @package Ecotone\Messaging\Config
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class MethodInterceptor implements InterceptorWithPointCut
{
    private string $interceptorName;
    private MessageHandlerBuilderWithOutputChannel $messageHandler;
    private int $precedence;
    private Pointcut $pointcut;
    private InterfaceToCall $interceptorInterfaceToCall;


    private function __construct(string $interceptorName, InterfaceToCall $interceptorInterfaceToCall, MessageHandlerBuilderWithOutputChannel $messageHandler, int $precedence, Pointcut $pointcut)
    {
        $this->messageHandler             = $messageHandler;
        $this->precedence                 = $precedence;
        $this->pointcut                   = $this->initializePointcut($interceptorInterfaceToCall, $pointcut, $messageHandler instanceof MessageHandlerBuilderWithParameterConverters ? $messageHandler->getParameterConverters() : []);
        $this->interceptorName            = $interceptorName;
        $this->interceptorInterfaceToCall = $interceptorInterfaceToCall;
    }

    /**
     * @param string                                 $interceptorName
     * @param InterfaceToCall                        $interceptorInterfaceToCall
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @param int                                    $precedence
     * @param string                                 $pointcut
     */
    public static function create(string $interceptorName, InterfaceToCall $interceptorInterfaceToCall, MessageHandlerBuilderWithOutputChannel $messageHandler, int $precedence, string $pointcut): MethodInterceptor
    {
        return new self($interceptorName, $interceptorInterfaceToCall, $messageHandler, $precedence, Pointcut::createWith($pointcut));
    }

    /**
     * @param object[]        $endpointAnnotations
     */
    public function doesItCutWith(InterfaceToCall $interfaceToCall, iterable $endpointAnnotations): bool
    {
        return $this->pointcut->doesItCut($interfaceToCall, $endpointAnnotations);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptingObject(): object
    {
        return $this->messageHandler;
    }

    /**
     * @inheritDoc
     */
    public function addInterceptedInterfaceToCall(InterfaceToCall $interceptedInterface, array $endpointAnnotations): self
    {
        $clone                     = clone $this;
        $interceptedMessageHandler = clone $clone->messageHandler;

        if ($interceptedMessageHandler instanceof MessageHandlerBuilderWithParameterConverters) {
            $interceptedMessageHandler->withMethodParameterConverters(
                MethodArgumentsFactory::createInterceptedInterfaceAnnotationMethodParameters(
                    $this->interceptorInterfaceToCall,
                    $interceptedMessageHandler->getParameterConverters(),
                    $endpointAnnotations,
                    $interceptedInterface,
                )
            );
        }
        $clone->messageHandler = $interceptedMessageHandler;

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function hasName(string $name): bool
    {
        return $this->interceptorName === $name;
    }

    /**
     * @return string
     */
    public function getInterceptorName(): string
    {
        return $this->interceptorName;
    }

    /**
     * @return int
     */
    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->interceptorName}.{$this->messageHandler}";
    }

    /**
     * @return MessageHandlerBuilderWithOutputChannel
     */
    public function getMessageHandler(): MessageHandlerBuilderWithOutputChannel
    {
        return $this->messageHandler;
    }

    /**
     * @var ParameterConverterBuilder[] $parameterConverters
     */
    private function initializePointcut(InterfaceToCall $interfaceToCall, Pointcut $pointcut, array $parameterConverters): Pointcut
    {
        if (! $pointcut->isEmpty()) {
            return $pointcut;
        }

        return Pointcut::initializeFrom($interfaceToCall, $parameterConverters);
    }
}
