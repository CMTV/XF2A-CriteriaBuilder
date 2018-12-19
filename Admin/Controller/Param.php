<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Admin\Controller;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\ControllerPlugin\AjaxSort;
use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\Delete;
use XF\Http\Request;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;

class Param extends AbstractController
{
    /* ============================================================================================================== */
    /* ACTIONS */
    /* ============================================================================================================== */

    public function actionAdd(ParameterBag $params)
    {
        /** @var \CMTV\CriteriaBuilder\Entity\Param $param */
        $param = $this->em()->create(C::ADDON_PREFIX('Param'));
        $param->criterion_id = $params->criterion_id;
        $param->definition_id = $this->filter('definition_id', 'str');

        return $this->paramAddEdit($param);
    }

    public function actionEdit(ParameterBag $params)
    {
        $param = $this->assertParamExists($params['criterion_id'], $params['param_id']);
        return $this->paramAddEdit($param);
    }

    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        if ($params['criterion_id'] && $params['param_id'])
        {
            $param = $this->assertParamExists($params['criterion_id'], $params['param_id']);
        }
        else
        {
            /** @var \CMTV\CriteriaBuilder\Entity\Param $param */
            $param = $this->em()->create(C::ADDON_PREFIX('Param'));
            $param->criterion_id = $params->criterion_id;
            $param->definition_id = $this->filter('definition_id', 'str');
        }

        $isInsert = $param->isInsert();

        $id = $param->criterion_id . '-' . $param->param_id;

        if ($isInsert)
        {
            $id .= $this->filter('param_id', 'str');
        }

        $this->paramSaveProcess($param)->run();

        $view = $this->view(
            C::ADDON_PREFIX('Param\Edit'),
            C::TEMPLATE_PREFIX('param_edit_row'),
            ['param' => $param]
        );

        $view->setJsonParams([
            'insert' => $isInsert,
            'id' => $id
        ]);

        return $view;
    }

    public function actionDelete(ParameterBag $params)
    {
        $param = $this->assertParamExists($params['criterion_id'], $params['param_id']);

        if (!$param->preDelete())
        {
            return $this->error($param->getErrors());
        }

        if ($this->isPost())
        {
            $id = $param->criterion_id . '-' . $param->param_id;

            $param->delete();

            $view = $this->view('Param\Listing', '', []);
            $view->setJsonParam('id', $id);

            return $view;
        }
        else
        {
            $viewParams = [
                'param' => $param
            ];

            return $this->view(
                C::ADDON_PREFIX('Param\Delete'),
                C::TEMPLATE_PREFIX('param_delete'),
                $viewParams
            );
        }
    }

    /* ============================================================================================================== */
    /* AJAX ACTIONS */
    /* ============================================================================================================== */

    public function actionAjaxSort()
    {
        $criterionId = explode('-', $this->filter('moved', 'str'))[0];

        $moveId = $this->filter('moved', 'str');
        $beforeId = $this->filter('before', 'str');

        $params = $this->finder(C::ADDON_PREFIX('Param'))->where(
            'criterion_id', $criterionId
        )->order('display_order')->fetch();

        /** @var AjaxSort $plugin */
        $plugin = $this->plugin(C::ADDON_PREFIX('AjaxSort'));

        return $plugin->actionAjaxSort(
            $moveId,
            $beforeId,
            $params,
            $beforeId === ''
        );
    }

    public function actionQuickView()
    {
        $criterionId = explode('-', $this->filter('id', 'str'))[0];
        $paramId = explode('-', $this->filter('id', 'str'))[1];

        $param = $this->finder(C::ADDON_PREFIX('Param'))->whereId([$criterionId, $paramId])->fetchOne();

        return $this->view(
            C::ADDON_PREFIX('Param\QuickView'),
            C::TEMPLATE_PREFIX('param_quick_view'),
            ['param' => $param]
        );
    }

    /* ============================================================================================================== */
    /* ... */
    /* ============================================================================================================== */

    protected function paramSaveProcess(\CMTV\CriteriaBuilder\Entity\Param $param)
    {
        $form = $this->formAction();

        $paramInput['param_id'] = $this->filter('param_id', 'str', $param->param_id);

        $form->validate(function (FormAction $form) use ($param)
        {
            $options = $this->filter('options', 'array');

            $request = new Request($this->app->inputFilterer(), $options, [], []);
            $handler = $param->getHandler();

            if ($handler && !$handler->verifyOptions($request, $options, $error))
            {
                if ($error)
                {
                    $form->logError($error);
                }
            }

            $param->options = $options;
        });

        $form->basicEntitySave($param, $paramInput);

        $phraseInput = $this->filter([
            'title' => 'str'
        ]);

        $form->apply(function () use ($phraseInput, $param)
        {
            $title = $param->getMasterPhrase();
            $title->phrase_text = $phraseInput['title'];
            $title->save();
        });

        return $form;
    }

    protected function paramAddEdit(\CMTV\CriteriaBuilder\Entity\Param $param)
    {
        $viewParams = [
            'param' => $param
        ];

        return $this->view(
            C::ADDON_PREFIX('Param\Edit'),
            C::TEMPLATE_PREFIX('param_edit'),
            $viewParams
        );
    }

    /**
     * @param string $criterionId
     * @param string $paramId
     * @param array|string|null $with
     * @param string|null $phraseKey
     *
     * @return \CMTV\CriteriaBuilder\Entity\Param
     */
    protected function assertParamExists($criterionId, $paramId, $with = null, $phraseKey = null)
    {
        $id = ['criterion_id' => $criterionId, 'param_id' => $paramId];
        return $this->assertRecordExists(C::ADDON_PREFIX('Param'), $id, $with, $phraseKey);
    }
}