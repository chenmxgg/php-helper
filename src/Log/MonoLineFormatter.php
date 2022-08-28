<?php

declare (strict_types = 1);

namespace Chenm\Helper\Log;

use Chenm\Helper\Log\MonoLogLevel;
use Monolog\Formatter\LineFormatter;

class MonoLineFormatter extends LineFormatter
{
    /**
     * 错误等级的显示样式.
     */
    protected $levelStyles = [
        MonoLogLevel::EMERGENCY => '<fg=red>',
        MonoLogLevel::ALERT => '<fg=red>',
        MonoLogLevel::CRITICAL => '<fg=red>',
        MonoLogLevel::ERROR => '<fg=red>',
        MonoLogLevel::WARNING => '<fg=yellow>',
        MonoLogLevel::NOTICE => '<fg=yellow>',
        MonoLogLevel::INFO => '<fg=green>',
        MonoLogLevel::DEBUG => '<fg=blue>',
    ];

    /**
     * 消息内容的显示样式.
     */
    protected $messageStyles = [
        MonoLogLevel::EMERGENCY => '<error>',
        MonoLogLevel::ALERT => '<error>',
        MonoLogLevel::CRITICAL => '<error>',
        MonoLogLevel::ERROR => '<error>',
        MonoLogLevel::WARNING => '<fg=yellow>',
        MonoLogLevel::NOTICE => '<fg=yellow>',
        MonoLogLevel::DEBUG => '<fg=blue>',
    ];

    /**
     * 格式化内容
     */
    protected $format = '';

    /**
     * {@inheritDoc}
     */
    public function __construct(?string $format = null, ?string $dateFormat = 'Y-m-d H:i:s', bool $allowInlineLineBreaks = true, bool $ignoreEmptyContextAndExtra = true)
    {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
        $vars = $this->normalize($record);

        $output = $this->format;

        foreach ($vars['extra'] as $var => $val) {
            if (str_contains($output, '%extra.' . $var . '%')) {
                $output = str_replace('%extra.' . $var . '%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }

        foreach ($vars['context'] as $var => $val) {
            if (str_contains($output, '%context.' . $var . '%')) {
                $output = str_replace('%context.' . $var . '%', $this->stringify($val), $output);
                unset($vars['context'][$var]);
            }
        }

        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }

            if (empty($vars['extra'])) {
                unset($vars['extra']);
                $output = str_replace('%extra%', '', $output);
            }
        }

        foreach ($vars as $var => $val) {
            if (str_contains($output, '%' . $var . '%')) {
                $replace = $this->stringify($val);
                switch ($var) {
                    case 'level_name':
                        $style = $this->levelStyles[strtolower($vars['level_name'])] ?? null;
                        if ($style) {
                            $replace = $style . $replace . '</>';
                        }
                        break;
                    case 'message':
                        $style = $this->messageStyles[strtolower($vars['level_name'])] ?? null;
                        if ($style) {
                            $replace = $style . $replace . '</>';
                        }
                        break;
                }
                $output = str_replace('%' . $var . '%', $replace, $output);
            }
        }

        // remove leftover %extra.xxx% and %context.xxx% if any
        if (str_contains($output, '%')) {
            $output = preg_replace('/%(?:extra|context)\..+?%/', '', $output);
        }

        return $output;
    }
}
