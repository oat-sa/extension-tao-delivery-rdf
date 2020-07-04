<?php

namespace oat\taoDeliveryRdf\test\unit\model;

use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\CoverageTesting;

class CoverageTestingTest extends TestCase
{
    public function testConstructor()
    {
        $this->assertInstanceOf(CoverageTesting::class, new CoverageTesting());
    }

    public function testAdd()
    {
        $sut = new CoverageTesting();

        $this->assertEquals(30, $sut->add());
    }
}

