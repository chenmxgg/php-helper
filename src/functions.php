<?php

use Chenm\Helper\File;
use Chenm\Helper\Http;

if (!function_exists('file_check_dir')) {
    function file_check_dir(string $path = null)
    {
        return File::checkDir($path);
    }
}

if (!function_exists('file_download')) {
    function file_download(string $filePath = null, string $targetPath = null)
    {
        return File::copyWebFileByGet($filePath, $targetPath);
    }
}

if (!function_exists('var_to_format_string')) {

    /**
     * 多种数据类型的变量转为格式化字符串内容 如对象和数组
     * @param void    $value   变量值
     * @param string  $type    格式化类型 print 和var_dump
     */
    function var_to_format_string($value = null, string $type = 'print')
    {
        if (ob_start()) {
            if ($type == 'var_dump') {
                var_dump($value);
            } else {
                print_r($value);
            }
            $data = ob_get_clean();
            return $data;
        }
        return null;
    }
}

if (!function_exists('object_to_array')) {
    /**
     * 对象转数组
     * @param object   $value    变量值
     * @param bool     $get_all  是否获取完整对象数据
     * @return array
     */
    function object_to_array(Object $value, $get_all = false): array
    {
        if ($get_all) {
            $arr = json_decode(json_encode($value), true);
        } else {
            $arr = get_object_vars($value);
        }
        return $arr;
    }
}

if (!function_exists('strip_xss')) {
    /**
     * xss过滤
     * @param mixed  $data 要过滤的数据
     */
    function strip_xss($data = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                $value = strip_xss($value);
            }
        } else {
            if (is_string($data) && $data) {
                $key    = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
                $key2   = ['<script(.*?)>', "<script", '<link', '<link(.*?)>', '<iframe', '<head(.*?)>', '<applet', "<meta(.*?)>", "<meta", "<javascript(.*?)>", "<javascript", "<vbscript(.*?)>", "<vbscript", "<base", "<title", "<embed", "object", "xml", "<xml", "<\?php", "<\?", "<\?=", "<%", "<%="];
                $key    = array_merge($key, $key2);
                $newStr = preg_replace('/' . implode('|', $key) . '/i', '', $data);
                if ($newStr) {
                    return $newStr;
                }
                return preg_replace('/' . implode('|', $key2) . '/i', '', $data);
            }
        }
        return $data;
    }
}

if (!function_exists('strip_trim')) {
    /**
     * 批量过滤两侧空字符串
     * @param mixed  $data 要过滤的数据
     */
    function strip_trim($data = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                $value = strip_trim($value);
            }
        } else {
            if (is_string($data) && $data) {
                $data = trim($data);
            }
        }
        return $data;
    }
}

if (!function_exists('get')) {
    /**
     * 获取get 数据 当 $name 为 null 时，返回所有.
     * @param string    $name    获取的内容字段名称 第一个字符为?号时可判断是否存在该字段
     * @param null      $default 默认数据
     * @return mixed
     */
    function get(?string $name = null, $default = null)
    {
        $Http = new Http();
        if (!is_null($name) && substr($name, 0, 1) === '?') {
            return $Http->hasGet(substr($name, 1));
        } else {
            return strip_trim($Http->get($name, $default));
        }
    }
}

if (!function_exists('post')) {
    /**
     * 获取post数据 当 $name 为 null 时，返回所有.
     * @param string    $name    获取的内容字段名称 第一个字符为?号时可判断是否存在该字段
     * @param null      $default 默认数据
     * @return mixed
     */
    function post(?string $name = null, $default = null)
    {
        $Http = new Http();
        if (!is_null($name) && substr($name, 0, 1) === '?') {
            return $Http->hasPost(substr($name, 1));
        } else {
            return strip_trim($Http->post($name, $default));
        }
    }
}

if (!function_exists('params')) {
    /**
     * 获取参数数据 支持GET|POST params('get.name')  当 $name 为 null 时，返回所有.
     * @param string    $name    获取的内容字段名称 第一个字符为?号时可判断是否存在该字段
     * @param null      $default 默认数据
     * @return mixed
     */
    function params(?string $name = null, $default = null)
    {
        $temp    = $name;
        $needHas = false;
        if (!is_null($temp) && substr($temp, 0, 1) === '?') {
            $needHas = true;
            $temp    = substr($temp, 1);
        }

        $method  = null;
        $methods = ['get', 'post'];
        if (!is_null($temp)) {
            if (($pos = strpos($temp, '.')) !== false) {
                $method = strtolower(substr($temp, 0, $pos));
                $name   = substr($temp, $pos + 1);
            } else {
                $method = null;
                $name   = $needHas ? substr($name, 1) : $name;
            }
        }
        if (!$needHas && !is_null($method) && in_array($method, $methods)) {
            return $method($name, $default);
        } else {
            $Http = new Http();
            if ($Http->hasGet($name)) {
                return $needHas ? true : strip_trim($Http->get($name, $default));
            } elseif ($Http->hasPost($name)) {
                return $needHas ? true : strip_trim($Http->post($name, $default));
            }
            return $needHas ? false : $default;
        }
    }
}

if (!function_exists('input')) {
    /**
     * 可自定义处理函数的获取参数
     * @param string    $name    获取的内容字段名称 第一个字符为?号时可判断是否存在该字段
     * @param null      $default 默认数据
     * @param null      $filter  默认数据
     * @return mixed
     */
    function input(?string $key = null, $default = '', $filter = ['trim', 'strip_tags', 'htmlspecialchars'])
    {
        if (is_string($filter)) {
            $filter = [$filter];
        }

        if (substr($key, 0, 1) === '?') {
            return params($key, $default);
        }

        $type_array = [
            'f' => "float", 's' => "string", 'a' => "array", 'd' => "integer", 'b' => "bool",
        ];
        if ($key) {
            $keys  = array_filter(explode('/', $key));
            $type  = isset($keys[1]) && isset($type_array[$keys[1]]) ? $keys[1] : false;
            $key   = $type ? $keys[0] : $key;
            $value = in_array(params($key, $default), ['', null]) ? $default : params($key, $default);
        } else {
            $value = params();
        }

        $value = filter($value, $filter);
        if (isset($type) && $type) {
            settype($value, $type_array[$keys[1]]);
        }

        return $value;
    }
}

if (!function_exists('filter')) {
    /**
     * 可自定义处理函数的获取参数
     * @param string    $name    获取的内容字段名称 第一个字符为?号时可判断是否存在该字段
     * @param null      $default 默认数据
     * @param null      $filter  默认数据
     * @return mixed
     */
    function filter($data, $filter = ['trim', 'htmlspecialchars', 'strip_xss'])
    {
        if (is_string($filter)) {
            $filter = [$filter];
        }

        if ($filter) {
            if (is_array($data)) {
                foreach ($data as $key => &$value) {
                    $value = filter($value, $filter);
                }
            } else {
                if (is_string($data)) {
                    foreach ($filter as $k => $v) {
                        function_exists($v) && $data = $v($data);
                    }
                }
            }
        }
        return $data;
    }
}
