<?php
namespace PHPUnit\Tests\Runner\CleverAndSmart\Integration;

use PHPUnit\Framework\TestCase as TestCase;

/** @group grp */
class DataTest extends TestCase
{
    public static function provideData()
    {
        return array(
            array('PHPUNIT_RUNNER_CLEVERANDSMART_FAILURE'),
            array('PHPUNIT_RUNNER_CLEVERANDSMART_ERROR'),
            array('PHPUNIT_RUNNER_CLEVERANDSMART_SUCCESS'),
        );
    }

    /** @dataProvider provideData */
    public function testData($constant)
    {
        switch ($constant) {
            case 'PHPUNIT_RUNNER_CLEVERANDSMART_FAILURE':
                usleep(1000);
                $this->assertFalse(PHPUNIT_RUNNER_CLEVERANDSMART_FAILURE);
                break;

            case 'PHPUNIT_RUNNER_CLEVERANDSMART_ERROR':
                usleep(2000);
                if (PHPUNIT_RUNNER_CLEVERANDSMART_ERROR) {
                    throw new \Exception('ERROR');
                }
                $this->assertTrue(true);
                break;

            case PHPUNIT_RUNNER_CLEVERANDSMART_SUCCESS:
                usleep(3000);
                $this->assertTrue(PHPUNIT_RUNNER_CLEVERANDSMART_SUCCESS);
                break;
        }
    }
}
