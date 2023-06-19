<?php
declare(strict_types = 1);

namespace RobotessNet;

trait Singleton
{
    /**
     * @var self
     */
    private static $instance;

    private function __construct()
    { /***/ }

    public static function instance(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}