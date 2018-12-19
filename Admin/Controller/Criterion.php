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
use CMTV\CriteriaBuilder\Data\CriteriaType;
use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\Delete;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;

class Criterion extends AbstractController
{
    /* ============================================================================================================== */
    /* ACTIONS */
    /* ============================================================================================================== */

    public function actionIndex()
    {
        $viewParams = $this->getCriterionRepo()->getCriteriaListData();

        if ($viewParams['totalCriteria'] === 0)
        {
            /** @var ListEmpty $plugin */
            $plugin = $this->plugin(C::ADDON_PREFIX('ListEmpty'));

            return $plugin->actionListEmpty(
                C::PHRASE_PREFIX('criteria'),
                C::PHRASE_PREFIX('add_criterion'),
                C::PHRASE_PREFIX('no_criteria_have_been_created'),
                $this->buildLink('criteria-builder/criteria/add')
            );
        }

        return $this->view(
            C::ADDON_PREFIX('Criterion\Listing'),
            C::TEMPLATE_PREFIX('criterion_list'),
            $viewParams
        );
    }

    public function actionAdd()
    {
        /** @var CriteriaType $supportedCriteriaTypesData */
        $supportedCriteriaTypesData = $this->app->data(C::ADDON_PREFIX('CriteriaType'));

        $viewParams = [
            'criterion' => $this->em()->create(C::ADDON_PREFIX('Criterion')),
            'criteriaTypes' => $supportedCriteriaTypesData->getSupportedTypes(),
            'categories' => $this->getCategoryRepo()->getCategoriesTitlePairs(true),
        ];

        return $this->view(
            C::ADDON_PREFIX('Criterion\Add'),
            C::TEMPLATE_PREFIX('criterion_add'),
            $viewParams
        );
    }

    public function actionEdit(ParameterBag $params)
    {
        $criterion = $this->assertCriterionExists($params->criterion_id);

        /** @var CriteriaType $supportedCriteriaTypesData */
        $supportedCriteriaTypesData = $this->app->data(C::ADDON_PREFIX('CriteriaType'));

        $viewParams = [
            'criterion' => $criterion,
            'criteriaTypes' => $supportedCriteriaTypesData->getSupportedTypes(),
            'categories' => $this->getCategoryRepo()->getCategoriesTitlePairs(true),
            'paramDefinitions' => $this->getParamDefinitionRepo()->findParamDefinitionsForList()->fetch()
        ];

        return $this->view(
            C::ADDON_PREFIX('Criterion\Edit'),
            C::TEMPLATE_PREFIX('criterion_edit'),
            $viewParams
        );
    }

    public function actionDelete(ParameterBag $params)
    {
        $criterion = $this->assertCriterionExists($params->criterion_id);

        /** @var Delete $plugin */
        $plugin = $this->plugin('XF:Delete');

        return $plugin->actionDelete(
            $criterion,
            $this->buildLink('criteria-builder/criteria/delete', $criterion),
            $this->buildLink('criteria-builder/criteria/edit', $criterion),
            $this->buildLink('criteria-builder/criteria'),
            $criterion->title
        );
    }

    public function actionSave(ParameterBag $params)
    {
        if ($params->criterion_id)
        {
            $criterion = $this->assertCriterionExists($params->criterion_id);
        }
        else
        {
            $criterion = $this->em()->create(C::ADDON_PREFIX('Criterion'));
        }

        $this->criterionSaveProcess($criterion)->run();

        if ($this->request->exists('exit'))
        {
            $redirect = $this->buildLink('criteria-builder/criteria');
        }
        else
        {
            $redirect = $this->buildLink('criteria-builder/criteria/edit', $criterion);
        }

        return $this->redirect($redirect);
    }

    /* ============================================================================================================== */
    /* AJAX ACTIONS */
    /* ============================================================================================================== */

    public function actionAjaxSort()
    {
        $categoryId = explode('-', $this->filter('moved', 'str'))[0];

        $moveId = explode('-', $this->filter('moved', 'str'))[1];

        if ($beforeId = $this->filter('before', 'str'))
        {
            $beforeId = explode('-', $this->filter('before', 'str'))[1];
        }

        $criteria = $this->finder(C::ADDON_PREFIX('Criterion'))
            ->where('category_id', $categoryId)->order('display_order')->fetch();

        /** @var AjaxSort $plugin */
        $plugin = $this->plugin(C::ADDON_PREFIX('AjaxSort'));

        return $plugin->actionAjaxSort(
            $moveId,
            $beforeId,
            $criteria,
            $beforeId === ''
        );
    }

    /* ============================================================================================================== */
    /* ... */
    /* ============================================================================================================== */

    protected function criterionSaveProcess(\CMTV\CriteriaBuilder\Entity\Criterion $criterion)
    {
        $form = $this->formAction();

        $criterionInput = $this->filter([
            'criteria_type' => 'str',
            'code' => 'str',
            'category_id' => 'str',
            'display_order' => 'uint'
        ]);

        $criterionInput['criterion_id'] = $this->filter('criterion_id', 'str', $criterion->criterion_id);

        $form->basicEntitySave($criterion, $criterionInput);

        $phraseInput = $this->filter([
            'title' => 'str',
            'description' => 'str'
        ]);

        $form->validate(function (FormAction $form) use ($phraseInput)
        {
            if ($phraseInput['title'] === '')
            {
                $form->logError(\XF::phrase('please_enter_valid_title'), 'title');
            }
        });

        $form->apply(function () use ($phraseInput, $criterion)
        {
            $masterTitle = $criterion->getMasterPhrase(true);
            $masterTitle->phrase_text = $phraseInput['title'];
            $masterTitle->save();

            $masterDescription = $criterion->getMasterPhrase(false);
            $masterDescription->phrase_text = $phraseInput['description'];
            $masterDescription->save();
        });

        return $form;
    }

    /**
     * @param $id
     * @param null $with
     * @param null $phraseKey
     *
     * @return \CMTV\CriteriaBuilder\Entity\Criterion
     */
    protected function assertCriterionExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists(C::ADDON_PREFIX('Criterion'), $id, $with, $phraseKey);
    }

    /**
     * @return \CMTV\CriteriaBuilder\Repository\Category
     */
    protected function getCategoryRepo()
    {
        return $this->repository(C::ADDON_PREFIX('Category'));
    }

    /**
     * @return \CMTV\CriteriaBuilder\Repository\Criterion
     */
    protected function getCriterionRepo()
    {
        return $this->repository(C::ADDON_PREFIX('Criterion'));
    }

    /**
     * @return \CMTV\CriteriaBuilder\Repository\ParamDefinition
     */
    protected function getParamDefinitionRepo()
    {
        return $this->repository(C::ADDON_PREFIX('ParamDefinition'));
    }
}