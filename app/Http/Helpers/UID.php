<?php

namespace TLCMap\Http\Helpers;
/**
 * Class which contains methods to handle GHAP UID.
 */
class UID
{
    /**
     * Create a UID based on the original ID.
     *
     * @param string|int $id
     *   The original ID.
     * @param string $prefix
     *   The UID prefix.
     * @return string|null
     *   The UID.
     */
    public static function create($id, $prefix = '')
    {
        if (!empty($id)) {
            return $prefix . base_convert($id, 10, 16);
        }
        return null;
    }

    /**
     * Convert an UID to the original ID.
     *
     * @param string $uid
     *   The UID.
     * @param string $prefix
     *   The UID prefix.
     * @return string|null
     *   The original ID.
     */
    public static function toID($uid, $prefix = '')
    {
        $uidValue = self::getValue($uid, $prefix);
        if (!empty($uidValue)) {
            return base_convert($uidValue, 16, 10);
        }
        return null;
    }

    /**
     * Get the UID prefix.
     *
     * @param string $uid
     *   The UID.
     * @param int $length
     *   The prefix length.
     * @return false|string|null
     *   The UID prefix.
     */
    public static function getPrefix($uid, $length = 1)
    {
        if (!empty($uid)) {
            return substr($uid, 0, $length);
        }
        return null;
    }

    /**
     * Get the UID value without the prefix.
     *
     * @param string $uid
     *   The UID.
     * @param string $prefix
     *   The UID prefix.
     * @return false|string|null
     *   The UID value.
     */
    public static function getValue($uid, $prefix = '')
    {
        if (!empty($uid)) {
            if (empty($prefix)) {
                return $uid;
            } else {
                return substr($uid, strlen($prefix));
            }
        }
        return null;
    }
}
