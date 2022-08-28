<?php

use Chenm\Helper\File;

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
