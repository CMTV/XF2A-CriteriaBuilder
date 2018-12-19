<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Repository;

use CMTV\CriteriaBuilder\Constants as C;
use XF\Mvc\Entity\Repository;

class ParamDefinition extends Repository
{
    /**
     * @return \XF\Mvc\Entity\Finder
     */
    public function findParamDefinitionsForList(bool $activeOnly = false)
    {
        $finder = $this->finder(C::ADDON_PREFIX('ParamDefinition'))->order('display_order');

        if ($activeOnly)
        {
            $finder->with('AddOn')->whereAddOnActive();
        }

        return $finder;
    }

    public function getParamDefinitionIds()
    {
        $db = $this->db();
        $table = C::DB_PREFIX('param_definition');
        $query = $db->query("SELECT `definition_id` FROM {$table}");

        return $query->fetchAllColumn();
    }
}