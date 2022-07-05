<?php
declare(strict_types=1);

namespace Tests\Ecotone\Messaging\Fixture\Behat\InterceptedGateway;

use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Interceptor\After;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class InterceptorExample
{
    #[Before(pointcut: CalculateGatewayExample::class)]
    public function multiplyBefore(int $amount): int
    {
        return $amount * 2;
    }

    #[Around(pointcut: CalculateGatewayExample::class)]
    public function sum(MethodInvocation $methodInvocation): int
    {
        $proceed = $methodInvocation->proceed();
        return $proceed + 1;
    }

    #[After(pointcut: CalculateGatewayExample::class)]
    public function multiplyAfter(int $amount): int
    {
        return $amount * 2;
    }
}