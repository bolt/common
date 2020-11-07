<?php

declare(strict_types=1);

namespace Bolt\Common\Tests\Fixtures;

use Bolt\Common\Json;

// @codingStandardsIgnoreFile

/**
 * Proxies native JSON methods through this singleton so they can be easily modified.
 *
 * This only works for our Json class.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class JsonMocker
{
    /** @var callable */
    private $encoder;
    /** @var callable */
    private $decoder;
    /** @var callable */
    private $lastCodeGetter;
    /** @var callable */
    private $lastMsgGetter;

    /**
     * Return the singleton instance.
     */
    public static function instance(): self
    {
        static $instance;
        if (! $instance) {
            static::register();
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Register override functions for Bolt\Common namespace.
     *
     * We use eval() here so we can loop over methods to reduce boilerplate and so that our IDEs
     * don't pick up these methods and try to auto complete to them instead of the native methods.
     */
    private static function register(): void
    {
        if (class_exists(Json::class, false)) {
            throw new \LogicException(sprintf('%s() must be called before %s is loaded', __METHOD__, Json::class));
        }

        $code = <<<'PHP'
namespace Bolt\Common;

function %s() { return call_user_func_array([\%s::instance(), '%1$s'], func_get_args()); }
PHP;
        $methods = [
            'json_decode',
            'json_encode',
            'json_last_error',
            'json_last_error_msg',
        ];
        foreach ($methods as $name) {
            eval(sprintf($code, $name, static::class));
        }
    }

    private function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->setEncoder();
        $this->setDecoder();
        $this->setLastCodeGetter();
        $this->setLastMessageGetter();
    }

    public function setEncoder(?callable $encoder = null): void
    {
        $this->encoder = $encoder ?: 'json_encode';
    }

    public function setDecoder(?callable $decoder = null): void
    {
        $this->decoder = $decoder ?: 'json_decode';
    }

    public function setLastCodeGetter(?callable $callable = null): void
    {
        $this->lastCodeGetter = $callable ?: 'json_last_error';
    }

    public function setLastMessageGetter(?callable $callable = null): void
    {
        $this->lastMsgGetter = $callable ?: 'json_last_error_msg';
    }

    // @codingStandardsIgnoreStart

    public function json_decode($value, $options = 0, $depth = 512)
    {
        return \call_user_func_array($this->decoder, \func_get_args());
    }

    public function json_encode($json, $assoc = false, $depth = 512, $options = 0)
    {
        return \call_user_func_array($this->encoder, \func_get_args());
    }

    public function json_last_error()
    {
        return \call_user_func($this->lastCodeGetter);
    }

    public function json_last_error_msg()
    {
        return \call_user_func($this->lastMsgGetter);
    }
}

JsonMocker::instance();
