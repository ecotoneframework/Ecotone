<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\WithAggregateEvents;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;

#[Asynchronous("orders")]
#[Aggregate]
class Order
{
    use WithAggregateEvents;

    #[AggregateIdentifier]
    private $orderId;

    private $isNotifiedCount = 0;

    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
        $this->record(new OrderWasPlaced($orderId));
    }

    #[CommandHandler("order.register", "orderReceiver")]
    public static function register(PlaceOrder $placeOrder) : self
    {
        return new self($placeOrder->getOrderId());
    }

    #[EventHandler(endpointId:"orderPlaced")]
    public function notify(OrderWasPlaced $order) : void
    {
        $this->isNotifiedCount++;
        $this->record(new OrderWasNotified($this->orderId));
    }

    #[QueryHandler("order.getOrder")]
    public function getRegisteredOrder() : string
    {
        return $this->orderId;
    }

    #[QueryHandler("order.wasNotified")]
    public function getIsNotifiedCount() : int
    {
        return $this->isNotifiedCount;
    }
}