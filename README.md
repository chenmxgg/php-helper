# Chenm - helper php 开发助手

## 介绍

Chenm - helper 是为广大 phper 解决多个繁琐且常用的操作封装，如文件类、日志类、邮件类、短信类等无需自行封装直接使用

> 部分来源于网络收集封装

QQ：857285711

助手交流群：暂无

微信群：暂无


## 安装使用

目前功能还未完善，后续将完善其他功能类

```bash
$ composer require chenm/helper
```
Log类支持自动清理过期日志，具体参数请参考源代码
```php
#使用例子 
use Chenm\Helper\Log;
Log::getInstance()->setSaveDir(__DIR__)->write();
Log::getInstance()->setLogWrite(false)->write(Log::ERROR, '测试日志内容')->getLog();
Log::getInstance()->setLogWrite(true)->user('测试日志内容')->getLog();
```
