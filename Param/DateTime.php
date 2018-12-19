<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Param;

use XF\Entity\User;

class DateTime extends AbstractParam
{
    public function prettyValue($raw, User $user)
    {
        try
        {
            $tz = new \DateTimeZone($raw['user_tz'] ? $user->timezone : $raw['timezone']);
        }
        catch (\Exception $e)
        {
            $tz = \XF::language()->getTimeZone();
        }

        return new \DateTime("$raw[ymd] $raw[hh]:$raw[mm]", $tz);
    }
}