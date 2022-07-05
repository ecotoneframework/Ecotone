<?php


namespace Tests\Ecotone\Messaging\Fixture\Behat\Calculating;

#[\Attribute]
class AfterMultiplyCalculation
{
    /**
     * @var integer
     */
    public $amount;

    public function __construct(int $amount)
    {
        $this->amount = $amount;
    }


}