<?php

namespace Bolt\Common\Tests;

use Bolt\Common\Thrower;

class ThrowerTest extends TestCase
{
    public function testSet()
    {
        $orig = $this->getHandler();

        Thrower::set();

        $current = $this->getHandler();

        $this->assertNotSame($orig, $current);
        restore_error_handler();

        $now = $this->getHandler();
        $this->assertSame($orig, $now);
    }

    public function testSetUsesSameHandler()
    {
        Thrower::set();
        Thrower::set();

        $handler1 = $this->getHandler();
        restore_error_handler();
        $handler2 = $this->getHandler();
        restore_error_handler();

        $this->assertSame($handler1, $handler2);
    }

    public function testCall()
    {
        $origHandler = $this->getHandler();

        $result = Thrower::call(
            function ($arg1, $arg2) {
                $this->assertSame('arg1', $arg1);
                $this->assertSame('arg2', $arg2);

                return 'blue';
            },
            'arg1',
            'arg2'
        );

        $this->assertSame('blue', $result);

        $nowHandler = $this->getHandler();
        $this->assertSame($origHandler, $nowHandler);
    }

    public function testCallWithError()
    {
        $origHandler = $this->getHandler();

        $e = null;
        try {
            Thrower::call(
                function () {
                    trigger_error('I errored', E_USER_ERROR);
                }
            );
        } catch (\ErrorException $e) {
        } catch (\Exception $e) {
        }

        $this->assertInstanceOf(\ErrorException::class, $e);
        $this->assertSame('I errored', $e->getMessage());
        $this->assertSame(E_USER_ERROR, $e->getSeverity());

        $nowHandler = $this->getHandler();
        $this->assertSame($origHandler, $nowHandler);
    }

    private function getHandler()
    {
        $handler = set_error_handler('var_dump');
        restore_error_handler();

        return $handler;
    }
}
