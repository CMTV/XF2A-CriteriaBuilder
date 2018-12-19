<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Repository;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\Data\CriteriaType;
use CMTV\CriteriaBuilder\Entity\Param;
use CMTV\CriteriaBuilder\TransferArray\TransferArray;
use XF\Entity\User;
use XF\Mvc\Entity\Repository;

class Criterion extends Repository
{
    public function check($rule, array $data, User $user, array $extra = [])
    {
        if (!$this->isCBRule($rule))
        {
            return false;
        }

        $criterionId = substr($rule, strlen('CMTV_CB_'));

        /** @var \CMTV\CriteriaBuilder\Entity\Criterion $criterion */
        $criterion = $this->finder(C::ADDON_PREFIX('Criterion'))->whereId($criterionId)->fetchOne();

        if ($criterion)
        {
            $check = static function () use ($criterion, $rule, $data, $user, $extra)
            {
                try
                {
                    $app = \XF::app();
                    $db = $app->db();

                    $get = static function (string $paramName) use ($criterion, $data, $user)
                    {
                        /** @var Param $param */
                        $param = \XF::finder(C::ADDON_PREFIX('Param'))->whereId([
                            $criterion->criterion_id,
                            $paramName
                        ])->fetchOne();

                        $raw = array_key_exists($paramName, $data) ? $data[$paramName] : null;

                        if (!$param)
                        {
                            return $raw;
                        }

                        return $param->handler->prettyValue($raw, $user);
                    };

                    if (is_bool($evalResult = eval($criterion->code)))
                    {
                        return $evalResult;
                    }
                }
                catch (\Throwable $e)
                {
                    \XF::logError("Error when matching \"{$criterion->title}\" criterion! Error: " . $e->getMessage());
                }

                return false;
            };

            return $check();
        }

        return false;
    }

    public function isCBRule($rule)
    {
        return substr($rule, 0, strlen('CMTV_CB_')) === 'CMTV_CB_';
    }

    /**
     * @return \XF\Mvc\Entity\Finder
     */
    public function findCriteriaForList()
    {
        $finder = $this->finder(C::ADDON_PREFIX('Criterion'))->order('display_order');

        return $finder;
    }

    public function getCriteriaListData()
    {
        $criteria = $this->findCriteriaForList()->fetch();
        $categories = $this->getCategoryRepo()->getCategoriesForList(true);

        /** @var CriteriaType $supportedCriteriaTypesData */
        $supportedCriteriaTypesData = $this->app()->data(C::ADDON_PREFIX('CriteriaType'));

        $typeGrouped = $criteria->groupBy('criteria_type');

        foreach ($typeGrouped as &$typeCriteria)
        {
            $typeCriteria = \XF::em()->getBasicCollection($typeCriteria);
            $typeCriteria = $typeCriteria->groupBy('category_id');

            $typeCriteriaNew = [];
            foreach ($categories as $category)
            {
                if (array_key_exists($category->category_id, $typeCriteria))
                {
                    $typeCriteriaNew[$category->category_id] = $typeCriteria[$category->category_id];
                }
            }

            $typeCriteria = $typeCriteriaNew;
        }

        return [
            'categories' => $categories,
            'criteria' => $typeGrouped,
            'totalCriteria' => $criteria->count(),
            'criteriaTypes' => $supportedCriteriaTypesData->getSupportedTypes()
        ];
    }

    public function getCriteriaDataOfType(string $criteriaType)
    {
        $criteria = $this->finder(C::ADDON_PREFIX('Criterion'))
            ->where('criteria_type', $criteriaType)
            ->order('display_order')
            ->fetch();

        $categories = $this->getCategoryRepo()->getCategoriesForList(true);

        return [
            'criteria' => $criteria->groupBy('category_id'),
            'categories' => $categories
        ];
    }

    public function getCriteriaForImport()
    {
        $criteriaForImport = [];

        $criteria = $this->findCriteriaForList()->fetch();

        foreach ($criteria as $criterion)
        {
            $criteriaForImport[$criterion->criterion_id] = TransferArray::getFromEntity($criterion);
        }

        return $criteriaForImport;
    }

    /**
     * @return Category
     */
    protected function getCategoryRepo()
    {
        return $this->repository(C::ADDON_PREFIX('Category'));
    }
}