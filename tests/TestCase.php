<?php

namespace Bolt\Common\Tests;

/**
 * Handle differences between PHPUnit versions.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    //region Add 5.7 features to 4.8. (Can be removed when support for PHP 5.5 is dropped)

    private $expectedException;
    private $expectedExceptionCode;
    private $expectedExceptionMessage;
    private $expectedExceptionMessageRegExp;

    /**
     * @param string $exception
     */
    public function expectException($exception)
    {
        if (method_exists(parent::class, __FUNCTION__)) {
            parent::expectException($exception);

            return;
        }

        $this->expectedException = $exception;

        $this->updateParentExceptedException();
    }

    /**
     * @param int|string $code
     */
    public function expectExceptionCode($code)
    {
        if (method_exists(parent::class, __FUNCTION__)) {
            parent::expectExceptionCode($code);

            return;
        }

        $this->expectedExceptionCode = $code;

        $this->updateParentExceptedException();
    }

    /**
     * @param string $message
     */
    public function expectExceptionMessage($message)
    {
        if (method_exists(parent::class, __FUNCTION__)) {
            parent::expectExceptionMessage($message);

            return;
        }

        $this->expectedExceptionMessage = $message;
        $this->expectedExceptionMessageRegExp = null;

        $this->updateParentExceptedException();
    }

    /**
     * @param string $messageRegExp
     */
    public function expectExceptionMessageRegExp($messageRegExp)
    {
        if (method_exists(parent::class, __FUNCTION__)) {
            parent::expectExceptionMessageRegExp($messageRegExp);

            return;
        }

        $this->expectedExceptionMessageRegExp = $messageRegExp;
        $this->expectedExceptionMessage = null;

        $this->updateParentExceptedException();
    }

    private function updateParentExceptedException()
    {
        if ($this->expectedExceptionMessageRegExp) {
            $this->setExpectedException(null, null, null);
            $this->setExpectedExceptionRegExp($this->expectedException, $this->expectedExceptionMessageRegExp, $this->expectedExceptionCode);
        } else {
            $this->setExpectedExceptionRegExp(null, null, null);
            $this->setExpectedException($this->expectedException, $this->expectedExceptionMessage, $this->expectedExceptionCode);
        }
    }

    // Keep these so we know these methods are deprecated in 4.8.

    /**
     * @param mixed  $exception
     * @param string $message
     * @param int    $code
     *
     * @deprecated Method deprecated since Release 5.2.0; use expectException() instead
     */
    public function setExpectedException($exception, $message = null, $code = null)
    {
        parent::setExpectedException($exception, $message, $code);
    }

    /**
     * @param mixed  $exception
     * @param string $messageRegExp
     * @param int    $code
     *
     * @deprecated Method deprecated since Release 5.6.0; use expectExceptionMessageRegExp() instead
     */
    public function setExpectedExceptionRegExp($exception, $messageRegExp = null, $code = null)
    {
        parent::setExpectedExceptionRegExp($exception, $messageRegExp, $code);
    }

    //endregion
}
