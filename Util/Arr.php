<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Util;

class Arr
{
    public static function equalsExcept(array $a, array $b, string... $keys): bool
    {
        foreach ($keys as $key)
        {
            unset($a[$key], $b[$key]);
        }

        return $a == $b;
    }

    public static function getDiffKeys(array $a, array $b): array
    {
        $diffKeys = array_merge(
            array_keys(array_diff_key($a, $b)),
            array_keys(array_diff_key($b, $a))
        );

        foreach ($a as $aKey => $aValue)
        {
            if (array_key_exists($aKey, $b) && $aValue !== $b[$aKey])
            {
                $diffKeys[] = $aKey;
            }
        }

        return $diffKeys;
    }

    public static function getDiffKey(array $array, array $keys): array
    {
        $assocKeys = [];

        foreach ($keys as $key)
        {
            $assocKeys[$key] = true;
        }

        return array_diff_key($array, $assocKeys);
    }
}