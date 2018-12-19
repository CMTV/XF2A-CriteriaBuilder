<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Data;

class CriteriaType
{
    public function getSupportedTypes()
    {
        $types = [
            'user' => [
                'class' => '\XF\Criteria\User'
            ],
            'page' => [
                'class' => '\XF\Criteria\Page'
            ]
        ];

        array_walk($types, function(&$type, $key)
        {
            $type['phrase'] = \XF::phrase('CMTV_CB_criteria_type.' . $key);
            return $type;
        });

        \XF::app()->fire('CMTV_CB_criteria_types', [&$types]);

        return $types;
    }
}