<?php

namespace Tests\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\VerifyAccessToSavingLogs;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Tests\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\Logger;

class HasEnoughPermissions
{
    #[Around(pointcut: ValidateExecutor::class)]
    public function validate(MethodInvocation $methodInvocation, ?Logger $logger)
    {
        if (is_null($logger)) {
            return $methodInvocation->proceed();
        }

        $data = $methodInvocation->getArguments()[0];

        if (!$logger->hasAccess($data["executorId"])) {
            throw new \InvalidArgumentException("Not enough permissions");
        }

        return $methodInvocation->proceed();
    }
}