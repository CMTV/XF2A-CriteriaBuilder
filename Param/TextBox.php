<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Param;

use XF\Http\Request;

class TextBox extends AbstractParam
{
    protected $defaultOptions = [
        'max_length' => 50,
        'default' => '',
        'pre_icon' => '',
        'username_autocomplete' => false,
        'regex' => ''
    ];

    public function verifyOptions(Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'max_length' => 'uint',
            'default' => 'str',
            'pre_icon' => 'str',
            'username_autocomplete' => 'bool',
            'regex' => 'str'
        ]);

        if ($options['max_length'] < 1)
        {
            $options['max_length'] = 1;
        }

        $options['regex'] = trim($options['regex'], '/');

        return true;
    }
}