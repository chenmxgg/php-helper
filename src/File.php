<?php

namespace Chenm\Helper;

!defined('DS') && define('DS', DIRECTORY_SEPARATOR);

class File
{

    private static $log = null;

    public function __construct()
    {

        return true;
    }

    /**
     * 远程文件下载
     * @param  string $filePath   远程文件直链
     * @param  string $targetPath 本地目标路径
     * @return string
     */
    public static function copyWebFile($filePath = '', $targetPath = '')
    {
        $array = self::file_get_by_headers($filePath);
        if ($array['code'] == 200) {
            $filedata = $array['data'];
            if (file_put_contents($targetPath, $filedata)) {
                @chmod($targetPath, 0755);
                return true;
            }
            return '权限异常：' . $targetPath;
        } else {
            return '远程下载文件失败：' . $array['data'];
        }
    }

    public function file_get_by_curl($filePath = '')
    {
        $ch = curl_init($filePath);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        curl_close($ch);
        return $temp;
    }

    /**
     * 远程文件内容获取
     * @param  string $filePath   远程文件直链
     */
    public static function file_get_by_headers($filePath = '')
    {
        //下载远程文件
        if (function_exists("set_time_limit")) {
            @set_time_limit(0);
        }

        if (function_exists("ignore_user_abort")) {
            @ignore_user_abort(true);
        }
        // 读取文件头
        $header = get_headers($filePath, 1);
        if (!$header) {
            return [
                'code' => 500,
                'msg' => '下载文件失败[' . $filePath . ']',
            ];
        } elseif (strpos($header[0], '200') === false) {
            return [
                'code' => intval($header[0]),
                'msg' => '下载文件错误[' . $header[0] . ']',
            ];
        }
        $filename = mb_substr($filePath, strripos($filePath, '/') + 1);
        ob_end_clean(); //清空输出并结束
        //echo str_repeat('', 10000);
        header('Content-Type: text/html; charset=utf-8');
        ob_start(); //打开输出
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $header['Content-Length']);
        flush();
        readfile($filePath);
        $filedata = ob_get_contents(); //得到浏览器输出
        ob_end_clean(); //清空输出
        return [
            'code' => 200,
            'data' => $filedata,
        ];
    }

    /**
     * 远程文件下载file_get_contents方式
     * @param  string $filePath   远程文件直链
     * @param  string $targetPath 本地目标路径
     * @param  array  $info       文件信息
     * @return string
     */
    public static function copyWebFileByGet($filePath = '', $targetPath = '', $info = [])
    {
        //下载远程文件
        if (function_exists("set_time_limit")) {
            @set_time_limit(0);
        }

        if (function_exists("ignore_user_abort")) {
            @ignore_user_abort(true);
        }
        $local = $targetPath;
        $temp = $targetPath . '.temp';
        header('Content-Type: text/html; charset=utf-8');
        $filedata = file_get_contents($filePath); //得到浏览器输出

        if (mb_substr($filedata, 0, 2) === "错误") {
            return $filedata;
        }
        //获取到的文件为空时
        if (mb_strlen(trim($filedata)) == 0) {
            if (isset($info['size']) && $info['size'] > 10) {
                //尝试另一种方式获取远程文件内容
                $filedata = self::file_get_by_curl($filePath);
                if (trim($filedata) == '') {
                    return '下载远程文件失败[' . (round($info['size'] / 1024, 3)) . 'kb]，请检查服务器是否卡顿或连接外网时是否稳定';
                }
            } else {
                file_put_contents($temp, "{$filedata}");
                return rename($temp, $targetPath) ? true : '下载文件重命名失败，请检查服务器主机权限[504]';
            }
        }
        $handle = fopen($temp, 'w');
        if ($handle) {
            if (($fwsize = fwrite($handle, $filedata)) > 0) {
                @fclose($handle);
                //如果写入文件字节数小于文件字节数则尝试使用file_put_contents写入
                if (isset($info['size']) && $fwsize < $info['size'] * 4 / 5) {
                    return self::file_put($temp, $targetPath, $filedata, $info);
                }

                if (filesize($temp) > 5) {
                    if (file_exists($targetPath)) {
                        unlink($targetPath);
                    }
                    if (!rename($temp, $targetPath)) {
                        self::setLog('下载文件[' . $temp . ']成功但重命名为[' . $targetPath . ']失败，请检查服务器主机权限[504]');
                        return '下载文件成功但重命名失败，请检查服务器主机权限[504]';
                    }
                    return true;
                }
                self::setLog('文件写入不完整，可能服务器异常');
                return '文件写入不完整，可能服务器异常';
            }
            self::setLog('文件写入失败，请参照如下操作尝试<br/>请检查路径手动创建或检查所有文件夹是否有www级别的0755权限');
            return '文件写入失败，请参照如下操作尝试<br/>请检查路径手动创建或检查所有文件夹是否有www级别的0755权限';
        }
        return self::file_put($temp, $targetPath, $filedata, $info);
    }

    public static function file_put($fileTemp = '', $targetPath = '', $data = '', $info = [])
    {
        if (file_exists($fileTemp)) {
            unlink($fileTemp);
        }
        $filename = self::getFileName($fileTemp);
        $msg = null;
        if (file_put_contents($fileTemp, $data)) {
            if (isset($info['size']) && ($fwsize = filesize($fileTemp)) < ($info['size'] * 4 / 5)) {
                $msg = '文件[' . $filename . ']写入不完整！[应写入大小：'
                    . $info['size'] . '字节；实际写入大小：'
                    . $fwsize . '字节；]';

            } else {
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }
                if (!rename($fileTemp, $targetPath)) {
                    $msg = '下载文件[' . $fileTemp . ']成功但重命名为[' . $targetPath . ']失败，请检查服务器主机权限[506]';
                }
                return true;
            }
        }
        $msg = '文件[' . $filename . ']写入失败[505]，可能无权限或路径错误[' . $fileTemp . ']';
        if ($msg != null) {
            self::setLog($msg);
            return $msg;
        }
        return true;
    }

    public static function getFileName($pathname = '')
    {
        if (($pos = mb_strrpos($pathname, '/')) !== false) {
            return mb_substr($pathname, $pos + 1);
        }
        return $pathname;
    }

    /**
     * 解压ZIP文件
     * @param  string $src  ZIP压缩文件
     * @param  string $dest 目标地址目录
     * @return bool
     */
    public static function zipExtract($src, $dest)
    {
        $zip = new \ZipArchive();
        if ($zip->open($src) === true) {
            $zip->extractTo($dest);
            $zip->close();
            return true;
        }
        return false;
    }

    /**
     * 打包一个目录到ZIP文件，支持多层
     * @param  string $dir      要压缩的文件来源目录
     * @param  string $filepath ZIP压缩文件路径，包含文件名
     * @return bool
     */
    public static function zipCreateDir($dir, $filepath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filepath) === true) {
            if (self::zipCreate($zip, $dir)) {
                return true;
            }
            return false;
        }
        return false;
    }
    /**
     * 打包创建当前目标和目录内的文件 可用于递归处理打包多个文件夹和文件夹内文件
     * @param  [type] $zipObj zip对象
     * @param  [type] $dir    目标
     */
    private static function zipCreate($zipObj, $dir)
    {
        $files = scandir($dir);
        if (count($files) > 0) {
            foreach ($files as $filename) {
                if ($filename === "." || $filename === "..") {
                    continue;
                }
                if (is_dir($dir . $filename)) {
                    $zipObj->addEmptyDir($dir . $filename);
                    self::zipCreate($zipObj, $dir . $filename);
                } else {
                    $zipObj->addFile($filename);
                    //$zipObj->addFile($dir . $filename);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 移动文件
     * @param  string $dir    文件来源地址
     * @param  string $newDir 文件目标地址
     * @return bool
     */
    public static function moveDir($dir = '', $newDir = '')
    {
        if (!is_dir($dir)) {
            self::setLog('要批量移动的路径[' . $dir . ']不是一个有效文件夹');
            return false;
        }

        $newDir = rtrim($newDir, DS);
        @chmod($dir, 0755);
        @chmod($newDir, 0755);
        if (!is_dir($newDir)) {
            @mkdir($newDir);
        }
        //$dh    = opendir($dir);
        $ok = true;
        $files = scandir($dir);
        if (count($files) > 0) {
            foreach ($files as $file) {
                if ($file === "." || $file === "..") {
                    continue;
                }
                if (is_dir($dir . DS . $file)) {
                    $rs = self::moveDir($dir . DS . $file, $newDir . DS . $file);
                    if ($rs !== true) {
                        $ok = $rs;
                        break;
                    }
                } else {
                    $fullpath = $newDir . DS . $file;
                    if (file_exists($fullpath)) {
                        @unlink($fullpath);
                    }
                    $oldpath = $dir . DS . $file;
                    @chmod($oldpath, 0755);
                    @chmod($fullpath, 0755);
                    $data = file_get_contents($oldpath);
                    if (file_put_contents($fullpath, $data) || filesize($fullpath) == filesize($oldpath)) {
                        @unlink($oldpath);
                    } else {
                        $ok = $fullpath . '|' . $oldpath;
                        break;
                    }
                }
            }
        }
        rmdir($dir);
        return $ok;
    }

    /**
     * 批量删除文件
     * @param string $dir 需要批量删除文件的目录
     */
    public static function delFiles(string $dir = '')
    {
        $dir = str_replace('/', DS, $dir);
        if (!is_dir($dir)) {
            self::setLog('要批量删除的路径[' . $dir . ']不是一个有效文件夹');
            return false;
        }
        $dir = rtrim($dir, DS) . DS;
        $files = scandir($dir);
        if (count($files) > 0) {
            foreach ($files as $filename) {
                if ($filename === "." || $filename === "..") {
                    continue;
                }
                if (is_dir($dir . $filename)) {
                    self::delFiles($dir . $filename . DS);
                } else {
                    @chmod($dir . $filename, 0755);
                    @unlink($dir . $filename);
                    @rmdir($dir);
                }
            }
            return true;

        }
        return false;
    }

    /**
     * 验证文件目录 如果不存在就自动递归创建
     * @param  string $dir    目录
     * @return bool
     */
    public static function checkDir(string $dir = ''): bool
    {
        if (empty($dir)) {
            self::setLog('要验证的文件夹[' . $dir . ']不能为空');
            return false;
        }

        if (mb_substr($dir, -1) != DS) {
            if (is_file($dir)) {
                $dir = dirname($dir) . DS;
            } else {
                $dir = $dir . DS;
            }
        }
        $dir = str_replace('/', DS, $dir);
        $dir_new = '/';
        $arr = explode(DS, $dir);
        foreach ($arr as $key => $value) {
            if ($value == "") {
                continue;
            }
            $dir_new .= $value . DS;
            if (!is_dir($dir_new)) {
                if (!mkdir($dir_new, 0755)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 写入日志
     */
    private static function setLog($msg)
    {
        self::$log .= "\n" . $msg;

        // if (is_null(self::$log) || !is_object(self::$log)) {
        //     try {
        //         self::$log = new Log(1, 10, 'File');
        //     } catch (\Exception $e) {
        //         //
        //     }
        // }
        // if (is_object(self::$log)) {
        //     self::$log->add('文件类日志', $msg);
        // }
    }

    /**
     * 获取执行日志
     */
    public static function getLog()
    {
        return self::$log;
    }
}
