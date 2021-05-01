<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;

class MessagingBaseCommand
{
    public function executeConsoleCommand(string $commandName, array $parameters, ConfiguredMessagingSystem $configuredMessagingSystem) : mixed
    {
        return $configuredMessagingSystem->runConsoleCommand($commandName, $parameters);
    }

    public function runAsynchronousEndpointCommand(string $consumerName, ConfiguredMessagingSystem $configuredMessagingSystem, ?string $handledMessageLimit = null, ?int $executionTimeLimit = null, ?int $memoryLimit = null, ?string $cron = null, bool $stopOnFailure = false) : void
    {
        $pollingMetadata = ExecutionPollingMetadata::createWithDefaults();
        if ($stopOnFailure) {
            $pollingMetadata = $pollingMetadata->withStopOnError(true);
        }
        if ($handledMessageLimit) {
            $pollingMetadata = $pollingMetadata->withHandledMessageLimit($handledMessageLimit);
        }
        if ($executionTimeLimit) {
            $pollingMetadata = $pollingMetadata->withExecutionTimeLimitInMilliseconds($executionTimeLimit);
        }
        if ($memoryLimit) {
            $pollingMetadata = $pollingMetadata->withMemoryLimitInMegabytes($memoryLimit);
        }
        if ($cron) {
            $pollingMetadata = $pollingMetadata->withCron($cron);
        }


        $configuredMessagingSystem->run($consumerName, $pollingMetadata);
    }

    public function listAsynchronousEndpointsCommand(ConfiguredMessagingSystem $configuredMessagingSystem) : ConsoleCommandResultSet
    {
        $consumers = [];
        foreach ($configuredMessagingSystem->list() as $consumerName) {
            $consumers[] = [$consumerName];
        }

        return ConsoleCommandResultSet::create(["Name"], $consumers);
    }
}