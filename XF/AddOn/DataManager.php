<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\XF\AddOn;

use CMTV\CriteriaBuilder\Constants as C;

class DataManager extends XFCP_DataManager
{
    public function getDataTypeClasses()
    {
        $dataTypeClasses = parent::getDataTypeClasses();
        $dataTypeClasses[] = C::ADDON_PREFIX('ParamDefinition');
        return $dataTypeClasses;
    }
}