<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Param;

use XF\Entity\User;
use XF\Http\Request;

class NumberBox extends AbstractParam
{
    protected $defaultOptions = [
        'step' => 1,
        'default' => '',
        'max' => '',
        'min' => ''
    ];

    public function verifyOptions(Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'step' => 'uint',
            'default' => 'str',
            'max' => 'str',
            'min' => 'str'
        ]);

        $this->ifNumCast($options['default']);
        $this->ifNumCast($options['max']);
        $this->ifNumCast($options['min']);

        if (
            (is_int($options['max']) && is_int($options['min']))
            &&
            ($options['max'] < $options['min'])
        )
        {
            $_temp = $options['max'];

            $options['max'] = $options['min'];
            $options['min'] = $_temp;
        }

        return true;
    }

    public function prettyValue($raw, User $user)
    {
        $this->ifNumCast($raw);
        return $raw;
    }

    protected function ifNumCast(&$var)
    {
        if (is_numeric($var))
        {
            $var += 0;
        }
    }
}