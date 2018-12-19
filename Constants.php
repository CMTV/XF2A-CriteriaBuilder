<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder;

class Constants
{
    const ADDON_ID = 'CMTV\CriteriaBuilder';
    const DB_PREFIX = 'xf_cmtv_cb';
    const PHRASE_PREFIX = 'CMTV_CB';
    const TEMPLATE_PREFIX = self::PHRASE_PREFIX;

    /* ============================================================================================================== */
    /* QUICK ACCESS */
    /* ============================================================================================================== */

    public static function ADDON_PREFIX(string $postString): string
    {
        return self::ADDON_ID . ':' . $postString;
    }

    public static function DB_PREFIX(string $tableName): string
    {
        return self::DB_PREFIX . '_' . $tableName;
    }

    public static function PHRASE_PREFIX(string $phraseName): string
    {
        return self::PHRASE_PREFIX . '_' . $phraseName;
    }

    public static function TEMPLATE_PREFIX(string $templateName): string
    {
        return self::TEMPLATE_PREFIX . '_' . $templateName;
    }
}