<?php

declare (strict_types = 1);

namespace Chenm\Helper\Log;

class MonoLogLevel extends \Psr\Log\LogLevel
{
    public const ALL = [
        self::ALERT,
        self::CRITICAL,
        self::DEBUG,
        self::EMERGENCY,
        self::ERROR,
        self::INFO,
        self::NOTICE,
        self::WARNING,
    ];
}
