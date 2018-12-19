<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Job;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\Entity\Category;

class CategoryCriteriaCount extends AbstractRebuildOffsetJob
{
    protected function getPrimaryKey(): string
    {
        return 'category_id';
    }

    protected function getTable(): string
    {
        return C::DB_PREFIX('category');
    }

    protected function rebuildById($id)
    {
        /** @var Category $category */
        $category = $this->app->finder(C::ADDON_PREFIX('Category'))->whereId($id)->fetchOne();
        $category->updateCriteriaCount();
    }

    protected function getStatusType()
    {
        return \XF::phrase(C::PHRASE_PREFIX('category_counting_criteria_in_categories'));
    }
}