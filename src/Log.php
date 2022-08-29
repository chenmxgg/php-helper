<?php

declare (strict_types = 1);

namespace Chenm\Helper;

use Chenm\Helper\File;
use Chenm\Helper\Log\MonoLineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as baseLog;

/**
 * 日志助手
 */
class Log
{
    /**
     * Detailed debug information
     */
    public const DEBUG = 100;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    public const INFO = 200;

    /**
     * Uncommon events
     */
    public const NOTICE = 250;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    public const WARNING = 300;

    /**
     * Runtime errors
     */
    public const ERROR = 400;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public const CRITICAL = 500;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    public const ALERT = 550;

    /**
     * Urgent alert.
     */
    public const EMERGENCY = 600;

    /**
     * 日志容器
     */
    public static $container = [];

    /**
     * 单例
     */
    private static $instance = null;

    /**
     * 是否记录运行日志
     */
    public static $log_write = true;

    /**
     * 运行日志
     */
    public static $log = null;

    /**
     * 单行日志自定义格式日志内容
     * %datetime% 时间
     * %level_name% 日志级别
     * %channel% 容器名称
     * %message% 标题
     * %context% 内容
     * %extra% 扩展信息
     */
    public static $output_formatter = "[%datetime%]%level_name%:  %message% > %context%\n";

    private static $log_name = 'ChenmLogs';

    /**
     * 自定义时间路径
     */
    private static $path_date_format = '{Ym}/{d}';

    public function __construct()
    {
        // 清理过期日志
        self::clear();
    }

    /**
     * 获取单例 成功后返回对象可链式操作
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 设置日志目录名称 默认[ChenmLogs]
     * @param string $name 日志名称
     */
    public function setLogDirName(string $name = 'ChenmLogs')
    {
        self::$log_name = $name;
        return self::getInstance();
    }

    /**
     * 设置是否记录运行日志
     * @param bool $value 是否记录
     */
    public function setLogWrite(bool $value = false)
    {
        self::$log_write = $value;
        return self::getInstance();
    }

    /**
     * 设置单行日志内容自定义格式 如 [%datetime%]%level_name%:  %message% > %context%\n
     * %datetime% 时间
     * %level_name% 日志级别
     * %channel% 容器名称
     * %message% 标题
     * %context% 内容
     * %extra% 扩展信息
     */
    public function setLineOutputFormatter(string $format = '')
    {
        self::$output_formatter = $format;
    }

    /**
     * 获取默认配置
     */
    public static function getDefaultOptions()
    {
        return [
            //日志目录
            'dir' => RUNTIME_PATH . self::$log_name,
            //日志容器名称
            'name' => 'Default',
            //日志文件名称
            'filename' => 'log.txt',
            //日志单天记录级别 h 时 m 分
            'log_level' => 'h',
            //日志文件过期时间 单位天
            'expire' => 7,
        ];
    }

    /**
     * 清理过期日志
     */
    public static function clear()
    {
        $container = self::$container;
        $nowDay = date("Ymd");
        foreach ($container as $key => $value) {
            if (isset($value['options'])) {
                $options = $value['options'];
                if (is_dir($path = $options['dir'] . DS . $options['name'])) {
                    $dirs = scandir($path);
                    if ($dirs) {
                        $expire = $options['expire'];
                        foreach ($dirs as $key2 => $dirname) {
                            if ($dirname < $nowDay - $expire) {
                                File::delFiles($path . DS . $dirname);
                            }
                        }
                    }
                }
            }
        }
        self::setLog('|-日志清理:处理成功');
    }

    /**
     * 解析配置数据
     */
    public static function parseOptions(array &$options = [])
    {
        if (!isset($options['log_level'])) {
            $options['log_level'] = 'h';
        }
        $options['name'] = ucfirst($options['name']);
        if (isset($options['dir']) && $options['dir'] && isset($options['filename'])) {
            $options['path'] = $options['dir'] . DS . $options['name'] . DS;
            $options['path'] .= self::parseDateDir() . DS;
            switch ($options['log_level']) {
                case 'm':
                    //分
                    $options['path'] .= date('H_m') . '_';
                    break;
                default:
                    //时
                    $options['path'] .= date('H') . '_';
                    break;
            }
            $options['path'] .= $options['filename'];
            self::parseDir(dirname($options['path']));
            $options['construct']['filename'] = $options['path'];
        }
    }

    private function parseDateDir()
    {
        $path_date_format = self::$path_date_format;
        if (!$path_date_format) {
            $path_date_format = '{Ym}/{d}';
        }
        // self::setLog('|-路径格式:' . $path_date_format);
        $path_dir = str_replace(['Y', 'm', 'd', '{', '}'], [date('Y'), date('m'), date('d'), '', ''], $path_date_format);
        if (!$path_dir) {
            $path_dir = date('Ym') . DS . date('d');
        }
        self::setLog('|-时间路径:' . $path_dir);
        return rtrim($path_dir, DS);
    }

    /**
     * 解析目录
     */
    public static function parseDir(string $dir = '')
    {
        $dir = rtrim($dir, DS);
        $arr = explode(DS, $dir);
        $newDir = '';
        if ($arr) {
            foreach ($arr as $key => $value) {
                if (!$value) {
                    continue;
                }
                $newDir .= DS . $value;
                if (!is_dir($newDir)) {
                    mkdir($newDir);
                }
            }
        }
        return $newDir;
    }

    /**
     * 写入自定义级别日志 其他配置请通过set开头方法设置
     * @param int    $level     级别 参见\Chenm\Helper\Log::的常量
     * @param string $message   日志标题
     * @param array  $context   日志内容
     * @param string $name      容器名称
     */
    public function write(int $level, string $message = '', ?array $context = [], string $name = 'Default')
    {
        self::getInstance()->addRecord($message, $context, ['name' => $name], $level);
        return self::getInstance();
    }

    /**
     * 写入日志
     * @param string $message   日志标题
     * @param array  $context   日志内容
     * @param array  $options   日志选项
     * @param int    $level     级别
     */
    public function addRecord(string $message = '', ?array $context = [], ?array $options, ?int $level = Log::INFO)
    {

        try {
            $defaultOptions = self::getDefaultOptions();
            if (!is_null($options)) {
                $options = array_merge($defaultOptions, $options);
            } else {
                $options = $defaultOptions;
            }
            self::parseOptions($options);

            $name = $options['name'];
            if (!isset(self::$container[$name]) || !is_object(self::$container[$name])) {
                self::$container[$name] = [
                    'logger' => new baseLog($name),
                    'options' => $options,
                ];

                $logger = self::$container[$name]['logger'];
                //文件保存本地
                $stream_handler = new StreamHandler($options['path']);
                $dateFormat = "Y-m-d H:i:s"; # 自定义时间格式
                # 将日志数据转化为一行字符, 可自定义格式
                $line_formatter = new MonoLineFormatter(self::$output_formatter, $dateFormat);
                $stream_handler->setFormatter($line_formatter); # 定义日志内容
                $logger->pushHandler($stream_handler);
            }
            $context = is_array($context) ? $context : [];
            self::setLog('|-日志标题:' . $message);
            self::setLog('|-日志路径:' . $options['path']);
            self::setLog('|-日志内容:' . json_encode($context));
            return self::$container[$name]['logger']->addRecord($level, $message, $context);
        } catch (\Throwable $th) {
            self::setLog('日志写入错误：' . $th->getMessage());
        }
    }

    /**
     * 快捷写入Debug日志
     * @param string $msg 日志内容
     */
    public function debug(string $msg = '', array $context = [])
    {
        self::getInstance()->addRecord($msg, $context, [
            'name' => 'Debug',
            'expire' => 15,
        ], Log::DEBUG);
        return self::getInstance();
    }

    /**
     * 快捷写入用户日志
     * @param string $msg 日志内容
     */
    public function user(string $msg = '', array $context = [])
    {
        self::getInstance()->addRecord($msg, $context, [
            'name' => 'User',
            'expire' => 7,
        ], Log::INFO);
        return self::getInstance();
    }

    /**
     * 快捷写入系统日志
     * @param string $msg 日志内容
     */
    public function system(string $msg = '', array $context = [])
    {
        self::getInstance()->addRecord($msg, $context, [
            'name' => 'System',
            'expire' => 15,
        ], Log::NOTICE);
        return self::getInstance();
    }

    /**
     * 写入运行日志
     * @param string $msg 日志内容
     */
    private static function setLog(string $msg = '')
    {
        if (self::$log_write) {
            self::$log .= "\n" . $msg;
        }
    }

    /**
     * 获取运行日志
     */
    public function getLog()
    {
        return self::$log;
    }
}
