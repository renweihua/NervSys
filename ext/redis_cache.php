<?php

/**
 * Redis Cache Extension
 *
 * Copyright 2018 秋水之冰 <27206617@qq.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace ext;

class redis_cache extends redis
{
    //Cache prefix
    public static $prefix = 'cache:';

    /**
     * Set cache
     *
     * @param string $name
     * @param array  $data
     * @param int    $life (in seconds)
     *
     * @return bool
     * @throws \Exception
     */
    public static function set(string $name, array $data, int $life = 600): bool
    {
        $name = self::$prefix . $name;
        $cache = json_encode($data);

        $result = 0 < $life ? self::connect()->set($name, $cache, $life) : self::connect()->set($name, $cache);

        unset($name, $data, $life, $cache);
        return $result;
    }

    /**
     * Get cache
     *
     * @param string $name
     *
     * @return array
     * @throws \Exception
     */
    public static function get(string $name): array
    {
        $cache = self::connect()->get(self::$prefix . $name);
        if (false === $cache) return [];

        $data = json_decode($cache, true);
        if (!is_array($data)) return [];

        unset($name, $cache);
        return $data;
    }

    /**
     * Delete cache
     *
     * @param string $name
     *
     * @return int
     * @throws \Exception
     */
    public static function del(string $name): int
    {
        $result = self::connect()->del(self::$prefix . $name);

        unset($name);
        return $result;
    }
}