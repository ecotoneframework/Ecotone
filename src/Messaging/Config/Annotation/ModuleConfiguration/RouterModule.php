<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Annotation\Router;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Router\RouterBuilder;

/**
 * Class AnnotationRouterConfiguration
 * @package Ecotone\Messaging\Config\Annotation\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class RouterModule extends MessageHandlerRegisterConfiguration
{
    public const MODULE_NAME = "routerModule";

    /**
     * @inheritDoc
     */
    public static function createMessageHandlerFrom(AnnotatedFinding $annotationRegistration): MessageHandlerBuilderWithParameterConverters
    {
        /** @var Router $annotation */
        $annotation = $annotationRegistration->getAnnotationForMethod();

        return RouterBuilder::create(AnnotatedDefinitionReference::getReferenceFor($annotationRegistration), $annotationRegistration->getMethodName())
            ->withEndpointId($annotation->endpointId)
            ->withInputChannelName($annotation->inputChannelName)
            ->setResolutionRequired($annotation->isResolutionRequired);
    }

    /**
     * @inheritDoc
     */
    public static function getMessageHandlerAnnotation(): string
    {
        return Router::class;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }
}