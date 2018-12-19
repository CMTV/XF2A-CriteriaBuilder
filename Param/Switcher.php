<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Param;

use XF\Entity\User;
use XF\Http\Request;

class Switcher extends AbstractParam
{
    protected $defaultOptions = [
        'enabled' => false
    ];

    public function verifyOptions(Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'enabled' => 'bool'
        ]);

        return true;
    }

    public function prettyValue($raw, User $user)
    {
        return $raw === '1';
    }
}