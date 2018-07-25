<?php
namespace PHPUnit\Tests\Runner\CleverAndSmart\Integration;

use PHPUnit\Framework\TestCase as TestCase;

/** @group grp */
class DependentTest extends TestCase
{
    public function testSuccess()
    {
        usleep(1000);
        $this->assertTrue(PHPUNIT_RUNNER_CLEVERANDSMART_SUCCESS);
    }

    /** @depends testSuccess */
    public function testFailure()
    {
        usleep(2000);
        $this->assertFalse(PHPUNIT_RUNNER_CLEVERANDSMART_FAILURE);
    }

    /** @depends testFailure */
    public function testError()
    {
        usleep(3000);
        if (PHPUNIT_RUNNER_CLEVERANDSMART_ERROR) {
            throw new \Exception();
        }
        $this->assertTrue(true);
    }
}
