<?php


namespace Tests\Ecotone\Modelling\Fixture\TwoAsynchronousSagas;


class OrderWasPaid
{
    public function __construct(private string $orderId) {}

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}