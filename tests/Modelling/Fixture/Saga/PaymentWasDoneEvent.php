<?php


namespace Ecotone\Tests\Modelling\Fixture\Saga;


class PaymentWasDoneEvent
{
    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }
}