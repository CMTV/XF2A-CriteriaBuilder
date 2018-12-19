<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Repository;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\TransferArray\TransferArray;
use XF\Mvc\Entity\Repository;

class Param extends Repository
{
    public function getParamsForList()
    {
        $finder = $this->finder(C::ADDON_PREFIX('Param'))->order('display_order');

        return $finder->fetch();
    }

    public function getParamsForImport()
    {
        $paramsForImport = [];

        $params = $this->getParamsForList();

        /** @var \CMTV\CriteriaBuilder\Entity\Param $param */
        foreach ($params as $param)
        {
            $paramsForImport[$param->criterion_id . '-' . $param->param_id] = TransferArray::getFromEntity($param);
        }

        return $paramsForImport;
    }

    public function getCriterionParamIds(string $criterionId): array
    {
        $db = $this->db();
        $table = C::DB_PREFIX('param');
        $query = "SELECT `param_id` FROM {$table} WHERE `criterion_id` = ?";

        return $db->query($query, [$criterionId])->fetchAllColumn();
    }
}