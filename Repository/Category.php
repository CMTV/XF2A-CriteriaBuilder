<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Repository;

use CMTV\CriteriaBuilder\Constants as C;
use XF\Mvc\Entity\Repository;

class Category extends Repository
{
    public function getCategoriesForList(bool $getDefault = false)
    {
        $categories = $this->finder(C::ADDON_PREFIX('Category'))
            ->order('display_order')
            ->fetch();

        if ($getDefault)
        {
            $defaultCategory = $this->getDefaultCategory();

            $categories = $categories->toArray();
            $categories = [$defaultCategory] + $categories;
            $categories = $this->em->getBasicCollection($categories);
        }

        return $categories;
    }

    public function getDefaultCategory()
    {
        $category = $this->em->create(C::ADDON_PREFIX('Category'));
        $category->setTrusted('category_id', null);
        $category->setTrusted('display_order', 0);
        $category->setTrusted('criteria', $this->getCategoryCriteriaCount(null));
        $category->setReadOnly(true);

        return $category;
    }

    public function getCategoryCriteriaCount($categoryId)
    {
        if ($categoryId === null)
        {
            $categoryId = '';
        }

        if ($categoryId instanceof \CMTV\CriteriaBuilder\Entity\Category)
        {
            $categoryId = $categoryId->criteria;
        }

        $table = C::DB_PREFIX('criterion');
        $query = "SELECT COUNT(*) FROM `{$table}` WHERE `category_id` = ?";

        return $this->db()->fetchOne($query, $categoryId);
    }

    public function getCategoriesTitlePairs(bool $getDefault = false)
    {
        $categories = $this->finder(C::ADDON_PREFIX('Category'))
            ->order('display_order')->fetch();

        if ($getDefault)
        {
            $defaultCategory = $this->getDefaultCategory();

            $categories = $categories->toArray();
            $categories = [$defaultCategory] + $categories;
            $categories = $this->em->getBasicCollection($categories);
        }

        return $categories->pluckNamed('title', 'category_id');
    }

    public function getCategoryIds()
    {
        $db = $this->db();
        $table = C::DB_PREFIX('category');
        $query = $db->query("SELECT `category_id` FROM {$table}");

        return $query->fetchAllColumn();
    }
}