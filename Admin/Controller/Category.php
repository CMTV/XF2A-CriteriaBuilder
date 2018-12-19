<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Admin\Controller;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\ControllerPlugin\AjaxSort;
use CMTV\CriteriaBuilder\ControllerPlugin\ListEmpty;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;

class Category extends AbstractController
{
    /* ============================================================================================================== */
    /* ACTIONS */
    /* ============================================================================================================== */

    public function actionIndex()
    {
        $categories = $this->getCategoryRepo()->getCategoriesForList();

        if ($categories->count() === 0)
        {
            /** @var ListEmpty $plugin */
            $plugin = $this->plugin(C::ADDON_PREFIX('ListEmpty'));

            return $plugin->actionListEmpty(
                C::PHRASE_PREFIX('criteria_categories'),
                'add_category',
                C::PHRASE_PREFIX('no_categories_have_been_created'),
                $this->buildLink('criteria-builder/categories/add')
            );
        }

        $viewParams = [
            'categories' => $categories
        ];

        return $this->view(
            C::ADDON_PREFIX('Category\Listing'),
            C::TEMPLATE_PREFIX('category_list'),
            $viewParams
        );
    }

    public function actionAdd()
    {
        $category = $this->em()->create(C::ADDON_PREFIX('Category'));
        return $this->categoryAddEdit($category);
    }

    public function actionEdit(ParameterBag $params)
    {
        $category = $this->assertCategoryExists($params->category_id);
        return $this->categoryAddEdit($category);
    }

    public function actionSave(ParameterBag $params)
    {
        if ($params->category_id)
        {
            $category = $this->assertCategoryExists($params->category_id);
        }
        else
        {
            $category = $this->em()->create(C::ADDON_PREFIX('Category'));
        }

        $this->categorySaveProcess($category)->run();

        return $this->redirect($this->buildLink('criteria-builder/categories'));
    }

    public function actionDelete(ParameterBag $params)
    {
        /** @var \CMTV\CriteriaBuilder\Entity\Category $category */
        $category = $this->assertCategoryExists($params->category_id);

        if (!$category->preDelete())
        {
            return $this->error($category->getErrors());
        }

        if ($this->isPost())
        {
            if (!$category->Criteria->count())
            {
                $category->delete();
            }

            switch ($this->filter('criteria_action', 'str'))
            {
                case 'move':
                    $category->deleteMove($this->filter('target_category', 'str'));
                    break;
                case 'delete':
                    $category->deleteWithCriteria();
                    break;
            }

            return $this->redirect($this->buildLink('criteria-builder/categories'));
        }
        else
        {
            $toMoveCategories = $this->getCategoryRepo()->getCategoriesTitlePairs(true);

            unset($toMoveCategories[$category->category_id]);

            $viewParams = [
                'category' => $category,
                'hasCriteria' => !!$category->Criteria->count(),
                'toMoveCategories' => $toMoveCategories
            ];

            return $this->view(
                C::ADDON_PREFIX('Category\Delete'),
                C::TEMPLATE_PREFIX('category_delete'),
                $viewParams
            );
        }
    }

    /* ============================================================================================================== */
    /* AJAX ACTIONS */
    /* ============================================================================================================== */

    public function actionAjaxSort()
    {
        $movedId = $this->filter('moved', 'str');
        $beforeId = $this->filter('before', 'str');

        $categories = $this->finder(C::ADDON_PREFIX('Category'))->order('display_order')->fetch();

        /** @var AjaxSort $plugin */
        $plugin = $this->plugin(C::ADDON_PREFIX('AjaxSort'));

        return $plugin->actionAjaxSort(
            $movedId,
            $beforeId,
            $categories,
            $beforeId === ''
        );
    }

    /* ============================================================================================================== */
    /* ... */
    /* ============================================================================================================== */

    protected function setCategoryDisplayOrder(\CMTV\CriteriaBuilder\Entity\Category $category, int &$order)
    {
        $category->display_order = $order;
        $category->saveIfChanged();

        $order += 10;
    }

    protected function categoryAddEdit(\CMTV\CriteriaBuilder\Entity\Category $category)
    {
        $viewParams = [
            'category' => $category
        ];

        return $this->view(
            C::ADDON_PREFIX('Category\Edit'),
            C::TEMPLATE_PREFIX('category_edit'),
            $viewParams
        );
    }

    protected function categorySaveProcess(\CMTV\CriteriaBuilder\Entity\Category $category)
    {
        $form = $this->formAction();

        $categoryInput = $this->filter([
            'icon' => 'str',
            'display_order' => 'uint'
        ]);

        $categoryInput['category_id'] = $this->filter('category_id', 'str', $category->category_id);

        $form->basicEntitySave($category, $categoryInput);

        $phraseInput = $this->filter([
            'title' => 'str'
        ]);

        $form->validate(function (FormAction $form) use ($phraseInput)
        {
            if ($phraseInput['title'] === '')
            {
                $form->logError(\XF::phrase('please_enter_valid_title'), 'title');
            }
        });

        $form->apply(function () use ($phraseInput, $category)
        {
            $masterTitle = $category->getMasterPhrase();
            $masterTitle->phrase_text = $phraseInput['title'];
            $masterTitle->save();
        });

        return $form;
    }

    /**
     * @param $id
     * @param null $with
     * @param null $phraseKey
     *
     * @return \CMTV\CriteriaBuilder\Entity\Category
     */
    protected function assertCategoryExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists(C::ADDON_PREFIX('Category'), $id, $with, $phraseKey);
    }

    /**
     * @return \CMTV\CriteriaBuilder\Repository\Category
     */
    protected function getCategoryRepo()
    {
        return $this->repository(C::ADDON_PREFIX('Category'));
    }
}