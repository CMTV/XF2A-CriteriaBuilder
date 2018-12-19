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
use XF\ControllerPlugin\Delete;
use XF\Http\Request;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;

class ParamDefinition extends AbstractController
{
    /* ============================================================================================================== */
    /* ACTIONS */
    /* ============================================================================================================== */

    public function actionIndex()
    {
        $definitions = $this->getParamDefinitionRepo()->findParamDefinitionsForList()->fetch();

        if ($definitions->count() === 0)
        {
            /** @var ListEmpty $plugin */
            $plugin = $this->plugin(C::ADDON_PREFIX('ListEmpty'));

            return $plugin->actionListEmpty(
                C::PHRASE_PREFIX('param_definitions'),
                C::PHRASE_PREFIX('add_param_definition'),
                C::PHRASE_PREFIX('no_param_definitions_have_been_created'),
                $this->buildLink('criteria-builder/params/definitions/add')
            );
        }

        $viewParams = [
            'definitions' => $definitions
        ];

        return $this->view(
            C::ADDON_PREFIX('Param\Definition\Listing'),
            C::TEMPLATE_PREFIX('param_definition_list'),
            $viewParams
        );
    }

    public function actionAdd()
    {
        $definition = $this->em()->create(C::ADDON_PREFIX('ParamDefinition'));
        return $this->definitionAddEdit($definition);
    }

    public function actionEdit(ParameterBag $params)
    {
        $definition = $this->assertParamDefinitionExists($params->definition_id);
        return $this->definitionAddEdit($definition);
    }

    public function actionSave(ParameterBag $params)
    {
        if ($params->definition_id)
        {
            $definition = $this->assertParamDefinitionExists($params->definition_id);
        }
        else
        {
            $definition = $this->em()->create(C::ADDON_PREFIX('ParamDefinition'));
        }

        $this->definitionSaveProcess($definition)->run();

        return $this->redirect($this->buildLink('criteria-builder/params/definitions'));
    }

    public function actionDelete(ParameterBag $params)
    {
        $definition = $this->assertParamDefinitionExists($params->definition_id);

        /** @var Delete $plugin */
        $plugin = $this->plugin('XF:Delete');

        return $plugin->actionDelete(
            $definition,
            $this->buildLink('criteria-builder/params/definitions/delete', $definition),
            $this->buildLink('criteria-builder/params/definitions/edit', $definition),
            $this->buildLink('criteria-builder/params/definitions'),
            $definition->title
        );
    }

    public function actionVerifyParam()
    {
        $paramInput = $this->filter([
            'param_id' => 'str',
            'param_title' => 'str',
            'param_type' => 'str',
            'options' => 'array'
        ]);

        if (!preg_match('/^[a-z0-9_]*$/i', $paramInput['param_id']))
        {
            return $this->error(\XF::phrase('please_enter_title_using_only_alphanumeric_underscore'));
        }

        if ($paramInput['param_title'] === '')
        {
            return $this->error(\XF::phrase('please_enter_valid_title'));
        }

        /** @var \CMTV\CriteriaBuilder\Entity\ParamDefinition $paramDefinition */
        $paramDefinition = $this->finder(
            C::ADDON_PREFIX('ParamDefinition')
        )->whereId($paramInput['param_type'])->fetchOne();

        $options = $paramInput['options'];
        $request = new Request($this->app->inputFilterer(), $options, [], []);
        $handler = $paramDefinition->handler;

        if ($handler && !$handler->verifyOptions($request, $options, $error))
        {
            if ($error)
            {
                return $this->error($error);
            }
        }

        return $this->message('');
    }

    /* ============================================================================================================== */
    /* AJAX ACTIONS */
    /* ============================================================================================================== */

    public function actionAjaxSort()
    {
        $movedId = $this->filter('moved', 'str');
        $beforeId = $this->filter('before', 'str');

        $categories = $this->finder(C::ADDON_PREFIX('ParamDefinition'))->order('display_order')->fetch();

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

    protected function definitionAddEdit(\CMTV\CriteriaBuilder\Entity\ParamDefinition $definition)
    {
        $viewParams = [
            'definition' => $definition
        ];

        return $this->view(
            C::ADDON_PREFIX('Param\Definition\Edit'),
            C::TEMPLATE_PREFIX('param_definition_edit'),
            $viewParams
        );
    }

    protected function definitionSaveProcess(\CMTV\CriteriaBuilder\Entity\ParamDefinition $definition)
    {
        $form = $this->formAction();

        $definitionInput = $this->filter([
            'definition_class' => 'str',
            'icon' => 'str',
            'display_order' => 'uint',
            'addon_id' => 'str'
        ]);

        $definitionInput['definition_id'] = $this->filter('definition_id', 'str', $definition->definition_id);

        $form->basicEntitySave($definition, $definitionInput);

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

        $form->apply(function () use ($phraseInput, $definition)
        {
            $title = $definition->getMasterPhrase();
            $title->phrase_text = $phraseInput['title'];
            $title->save();

            $description = $definition->getMasterPhrase(false);
            $description->phrase_text = $phraseInput['description'];
            $description->save();
        });

        return $form;
    }

    /**
     * @param $id
     * @param null $with
     * @param null $phraseKey
     *
     * @return \CMTV\CriteriaBuilder\Entity\ParamDefinition
     */
    protected function assertParamDefinitionExists($id, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists(C::ADDON_PREFIX('ParamDefinition'), $id, $with, $phraseKey);
    }

    /**
     * @return \CMTV\CriteriaBuilder\Repository\ParamDefinition
     */
    protected function getParamDefinitionRepo()
    {
        return $this->repository(C::ADDON_PREFIX('ParamDefinition'));
    }
}