<?php

namespace Behat\Bootstrap;

use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Fixture\Behat\Booking\BookingService;
use Fixture\Behat\Ordering\Order;
use Fixture\Behat\Ordering\OrderConfirmation;
use Fixture\Behat\Ordering\OrderingService;
use Fixture\Behat\Shopping\BookWasReserved;
use Fixture\Behat\Shopping\ShoppingService;
use Messaging\Channel\DirectChannel;
use Messaging\Channel\QueueChannel;
use Messaging\Config\InMemoryChannelResolver;
use Messaging\Config\MessagingSystem;
use Messaging\Endpoint\ConsumerEndpointFactory;
use Messaging\Endpoint\ConsumerLifecycle;
use Messaging\Endpoint\PollOrThrowPollableFactory;
use Messaging\Future;
use Messaging\Handler\Gateway\GatewayProxy;
use Messaging\Handler\Gateway\GatewayProxyBuilder;
use Messaging\Handler\MessageHandlingException;
use Messaging\Handler\Router\RouterBuilder;
use Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Messaging\Handler\Transformer\TransformerBuilder;
use Messaging\MessageChannel;
use Messaging\MessageHandler;
use Messaging\MessagingException;
use Messaging\PollableChannel;
use Messaging\RunTimeMessagingException;
use Messaging\Support\Assert;

/**
 * Defines application features from the specific context.
 */
class DomainContext implements Context
{
    /**
     * @var array|MessageChannel[]
     */
    private $messageChannels = [];
    /**
     * @var array|ConsumerLifecycle
     */
    private $consumers = [];
    /**
     * @var GatewayProxy[]
     */
    private $gateways;
    /**
     * @var MessagingSystem
     */
    private $messagingSystem;

    /**
     * @var object[]
     */
    private $serviceObjects = [];
    /**
     * @var Future
     */
    private $future;

    /**
     * @Given I register :bookingRequestName as :type
     * @param string $channelName
     * @param string $type
     */
    public function iRegisterAs(string $channelName, string $type)
    {
        switch ($type) {
            case "Direct Channel": {
                $this->messageChannels[$channelName] = DirectChannel::create();
                break;
            }
            case "Pollable Channel": {
                $this->messageChannels[$channelName] = QueueChannel::create();
            }
        }
    }

    /**
     * @Given I activate service with name :handlerName for :className with method :methodName to listen on :channelName channel
     * @param string $handlerName
     * @param string $className
     * @param string $methodName
     * @param string $channelName
     */
    public function iActivateServiceWithNameForWithMethodToListenOnChannel(string $handlerName, string $className, string $methodName, string $channelName)
    {
        $serviceActivatorBuilder = $this->createServiceActivatorBuilder($handlerName, $className, $methodName, $channelName);

        $this->consumers[] = $this->consumerEndpointFactory()->create($serviceActivatorBuilder);
    }

    /**
     * @Given I activate service with name :handlerName for :className with method :methodName to listen on :channelName channel and output channel :outputChannel
     * @param string $handlerName
     * @param string $className
     * @param string $methodName
     * @param string $channelName
     * @param string $outputChannel
     */
    public function iActivateServiceWithNameForWithMethodToListenOnChannelAndOutputChannel(string $handlerName, string $className, string $methodName, string $channelName, string $outputChannel)
    {
        $serviceActivatorBuilder = $this->createServiceActivatorBuilder($handlerName, $className, $methodName, $channelName)
                                        ->withOutputChannel($this->getChannelByName($outputChannel));

        $this->consumers[] = $this->consumerEndpointFactory()->create($serviceActivatorBuilder);
    }

    /**
     * @Given I set gateway for :interfaceName and :methodName with request channel :requestChannel
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannel
     */
    public function iSetGatewayForAndWithRequestChannel(string $interfaceName, string $methodName, string $requestChannel)
    {
        /** @var DirectChannel $messageChannel */
        $messageChannel = $this->getChannelByName($requestChannel);
        Assert::isSubclassOf($messageChannel, DirectChannel::class, "Request Channel for Direct Channel");

        $this->gateways = GatewayProxyBuilder::create($interfaceName, $methodName, $messageChannel);
    }

    /**
     * @Given I activate gateway with name :gatewayName for :interfaceName and :methodName with request channel :requestChannel
     * @param string $gatewayName
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannel
     */
    public function iActivateGatewayWithNameForAndWithRequestChannel(string $gatewayName, string $interfaceName, string $methodName, string $requestChannel)
    {
        $gatewayProxyBuilder = $this->createGatewayBuilder($interfaceName, $methodName, $requestChannel);

        $this->gateways[$gatewayName] = $gatewayProxyBuilder
                                            ->build();
    }

    /**
     * @Given I activate gateway with name :gatewayName for :interfaceName and :methodName with request channel :requestChannel and reply channel :replyChannel
     * @param string $gatewayName
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannel
     * @param string $replyChannel
     */
    public function iActivateGatewayWithNameForAndWithRequestChannelAndReplyChannel(string $gatewayName, string $interfaceName, string $methodName, string $requestChannel, string $replyChannel)
    {
        /** @var PollableChannel $pollableChannel */
        $pollableChannel = $this->getChannelByName($replyChannel);
        Assert::isSubclassOf($pollableChannel, PollableChannel::class, "Reply channel for gateway must be pollable channel");

        $gatewayProxyBuilder = $this->createGatewayBuilder($interfaceName, $methodName, $requestChannel)
                                    ->withReplyChannel($pollableChannel)
                                    ->withMillisecondTimeout(1);

        $this->gateways[$gatewayName] = $gatewayProxyBuilder
            ->build();
    }

    /**
     * @When I book flat with id :flatNumber using gateway :gatewayName
     * @param int $flatNumber
     * @param string $gatewayName
     */
    public function iBookFlatWithIdUsingGateway(int $flatNumber, string $gatewayName)
    {
        /** @var BookingService $gateway */
        $gateway = $this->getGatewayByName($gatewayName);

        $gateway->bookFlat($flatNumber);
    }

    /**
     * @Then flat with id :flatNumber should be reserved when checked by :gatewayName
     * @param int $flatNumber
     * @param string $gatewayName
     */
    public function flatWithIdShouldBeReservedWhenCheckedBy(int $flatNumber, string $gatewayName)
    {
        /** @var BookingService $gateway */
        $gateway = $this->getGatewayByName($gatewayName);

        \PHPUnit\Framework\Assert::assertEquals(true, $gateway->checkIfIsBooked($flatNumber));
    }

    /**
     * @Given I run messaging system
     */
    public function iRunMessagingSystem()
    {
        $this->messagingSystem = MessagingSystem::create($this->consumers);

        $this->messagingSystem->runEventDrivenConsumers();
    }

    /**
     * @param string $channelName
     * @return MessageChannel
     */
    private function getChannelByName(string $channelName) : MessageChannel
    {
        foreach ($this->messageChannels as $messageChannelName => $messageChannel) {
            if ($messageChannelName === $channelName) {
                return $messageChannel;
            }
        }

        throw new \InvalidArgumentException("Channel with name {$channelName} do not exists");
    }

    /**
     * @param string $gatewayNameToFind
     * @return mixed
     */
    private function getGatewayByName(string $gatewayNameToFind)
    {
        foreach ($this->gateways as $gatewayName => $gateway) {
            if ($gatewayName == $gatewayNameToFind) {
                return $gateway;
            }
        }

        throw new \InvalidArgumentException("Channel with name {$gatewayNameToFind} do not exists");
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannel
     * @return \Messaging\Handler\Gateway\GatewayProxyBuilder
     */
    private function createGatewayBuilder(string $interfaceName, string $methodName, string $requestChannel): GatewayProxyBuilder
    {
        $messageChannel = $this->getChannelByName($requestChannel);
        /** @var DirectChannel $messageChannel */
        Assert::isSubclassOf($messageChannel, DirectChannel::class, "Request Channel should be Direct Channel");

        $gatewayProxyBuilder = GatewayProxyBuilder::create($interfaceName, $methodName, $messageChannel);
        return $gatewayProxyBuilder;
    }

    /**
     * @param string $handlerName
     * @param string $className
     * @param string $methodName
     * @param string $channelName
     * @return \Messaging\Handler\ServiceActivator\ServiceActivatorBuilder
     */
    private function createServiceActivatorBuilder(string $handlerName, string $className, string $methodName, string $channelName): ServiceActivatorBuilder
    {
        $object = $this->createObject($className);

        $serviceActivatorBuilder = ServiceActivatorBuilder::create($object, $methodName);
        $serviceActivatorBuilder->withInputMessageChannel($this->getChannelByName($channelName));
        $serviceActivatorBuilder->withName($handlerName);

        return $serviceActivatorBuilder;
    }

    /**
     * @Given I activate transformer with name :name for :className and :methodName with request channel :requestChannelName and output channel :responseChannelName
     * @param string $name
     * @param string $className
     * @param string $methodName
     * @param string $requestChannelName
     * @param string $responseChannelName
     */
    public function iActivateTransformerWithNameForAndWithRequestChannelAndOutputChannel(string $name, string $className, string $methodName, string $requestChannelName, string $responseChannelName)
    {
        $inputChannel = $this->getChannelByName($requestChannelName);
        $outputChannel = $this->getChannelByName($responseChannelName);
        $object = $this->createObject($className);

        $this->consumers[] = $this->consumerEndpointFactory()->create(TransformerBuilder::create($inputChannel, $outputChannel, $object, $methodName, $name));
    }

    /**
     * @When I reserve book named :bookName using gateway :gatewayName
     * @param string $bookName
     * @param string $gatewayName
     */
    public function iReserveBookNamedUsingGateway(string $bookName, string $gatewayName)
    {
        /** @var ShoppingService $gateway */
        $gateway = $this->getGatewayByName($gatewayName);

        $bookWasReserved = $gateway->reserve($bookName);

        \PHPUnit\Framework\Assert::assertInstanceOf(BookWasReserved::class, $bookWasReserved, "Book must be reserved");
    }

    /**
     * @param string $className
     * @return null|object
     */
    private function createObject(string $className)
    {
        $object = null;
        if (array_key_exists($className, $this->serviceObjects)) {
            $object = $this->serviceObjects[$className];
        } else {
            $object = new $className();
            $this->serviceObjects[$className] = $object;
        }
        return $object;
    }

    private function consumerEndpointFactory() : ConsumerEndpointFactory
    {
        return new ConsumerEndpointFactory(InMemoryChannelResolver::createFromAssociativeArray($this->messageChannels), new PollOrThrowPollableFactory());
    }


    /**
     * @Given I activate header router with name :handlerName and input Channel :inputChannelName for header :headerName with mapping:
     * @param string $handlerName
     * @param string $inputChannelName
     * @param string $headerName
     * @param TableNode $mapping
     */
    public function iActivateHeaderRouterWithNameAndInputChannelForHeaderWithMapping(string $handlerName, string $inputChannelName, string $headerName, TableNode $mapping)
    {
        $channelToValue = [];
        foreach ($mapping->getHash() as $headerValue) {
            $channelToValue[$headerValue['value']] = $headerValue['target_channel'];
        }

        $this->consumers[] = $this->consumerEndpointFactory()->create(
            RouterBuilder::createHeaderValueRouter($handlerName, $this->getChannelByName($inputChannelName), $headerName, $channelToValue)
        );
    }

    /**
     * @When I send order request with id :orderId product name :productName using gateway :gatewayName
     * @param int $orderId
     * @param string $productName
     * @param string $gatewayName
     */
    public function iSendOrderRequestWithIdProductNameUsingGateway(int $orderId, string $productName, string $gatewayName)
    {
        /** @var OrderingService $gateway */
        $gateway = $this->getGatewayByName($gatewayName);

        $this->future = $gateway->processOrder(Order::create($orderId, $productName));
    }

    /**
     * @When I expect exception when sending order request with id :orderId product name :productName using gateway :gatewayName
     * @param int $orderId
     * @param string $productName
     * @param string $gatewayName
     */
    public function iExpectExceptionWhenSendingOrderRequestWithIdProductNameUsingGateway(int $orderId, string $productName, string $gatewayName)
    {
        /** @var OrderingService $gateway */
        $gateway = $this->getGatewayByName($gatewayName);


        try {
            $gateway->processOrder(Order::create($orderId, $productName));
            \PHPUnit\Framework\Assert::assertTrue(false, "Expect exception got none");
        }catch (\Exception $e) {}
    }

    /**
     * @Then I should receive confirmation
     */
    public function iShouldReceiveConfirmation()
    {
        \PHPUnit\Framework\Assert::assertInstanceOf(OrderConfirmation::class, $this->future->resolve());
    }

    /**
     * @Then I expect exception during confirmation receiving
     */
    public function iExpectExceptionDuringConfirmationReceiving()
    {
        try {
            $message = $this->future->resolve();
            \PHPUnit\Framework\Assert::assertTrue(false, "Expect exception got none");
        }catch (MessageHandlingException $e) {}
    }

    /**
     * @Given I activate header enricher transformer with name :handlerName with request channel :requestChannelName and output channel :outputChannelName with headers:
     * @param string $handlerName
     * @param string $requestChannelName
     * @param string $outputChannelName
     * @param TableNode $headers
     */
    public function iActivateHeaderEnricherTransformerWithNameWithRequestChannelAndOutputChannelWithHeaders(string $handlerName, string $requestChannelName, string $outputChannelName, TableNode $headers)
    {
        $keyValues = [];
        foreach ($headers->getHash() as $keyValue) {
            $keyValues[$keyValue['key']] = $keyValue['value'];
        }

        $this->consumers[] = $this->consumerEndpointFactory()->create(TransformerBuilder::createHeaderEnricher(
            $handlerName,
            $this->getChannelByName($requestChannelName),
            $this->getChannelByName($outputChannelName),
            $keyValues
        ));
    }

    /**
     * @When :consumerName handles message
     * @param string $consumerName
     */
    public function handlesMessage(string $consumerName)
    {
        $this->messagingSystem->runPollableByName($consumerName);
    }
}
