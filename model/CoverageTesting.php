<?php


namespace oat\taoDeliveryRdf\model;


class CoverageTesting
{
    /** @var int  */
    private $a;

    /** @var int  */
    private $b;

    public function __construct()
    {
        $this->a = 10;
        $this->b = 20;
    }

    public function add()
    {
        return $this->a + $this->b;
    }
}


