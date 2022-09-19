<?php

namespace Chenm\Helper;

use Imi\Bean\Annotation\Inherit;
use Imi\Server\Http\Message\Proxy\RequestProxyObject;

/**
 * @Inherit
 */
class Http extends RequestProxyObject
{
    /**
     * 获取post(兼容application/json和application/x-www-urlencode)
     * @param string|null $name
     * @param void $default
     * @return mixed
     */
    public function post(?string $name = null, $default = null)
    {
        $value = parent::post($name, $default);
        if ((!$value || $default == $value) && ($data = $this->getParsedBody())) {
            if (is_null($name)) {
                return $data;
            }
            return $data[$name] ?? null;
        }
        return $value;
    }
}
