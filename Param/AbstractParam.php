<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Param;

use CMTV\CriteriaBuilder\Constants as C;
use XF\App;
use XF\Entity\User;
use XF\Http\Request;

abstract class AbstractParam
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var ParamConfig
     */
    protected $paramConfig;

    protected $options;
    protected $defaultOptions = [];

    public function __construct(App $app, ParamConfig $paramConfig)
    {
        $this->app = $app;
        $this->paramConfig = $paramConfig;
        $this->options = $this->setupOptions($paramConfig->options);
    }

    protected function setupOptions(array $options)
    {
        return array_replace($this->defaultOptions, $options);
    }

    public function renderOptions()
    {
        $templateName = $this->getOptionsTemplate();

        if (!$this->app->templater()->isKnownTemplate($templateName))
        {
            return '';
        }

        return $this->app->templater()->renderTemplate(
            $templateName, $this->getDefaultTemplateParams('options')
        );
    }

    public function render($criteriaInput, $criterionName, $criteria, $criteriaData)
    {
        $templateName = $this->getTemplate();

        return $this->app->templater()->renderTemplate(
            $templateName, $this->getDefaultTemplateParams('render') + [
                'name' => $criteriaInput . '[' . $criterionName . '][data][' . $this->paramConfig->paramId . ']',
                'value' => $criteria[$criterionName][$this->paramConfig->paramId],
                'criterionSelected' => array_key_exists($criterionName, $criteria),
                'criteriaInput' => $criteriaInput,
                'criterionName' => $criterionName,
                'criteria' => $criteria,
                'criteriaData' => $criteriaData
            ]
        );
    }

    public function getOptionsTemplate()
    {
        return 'admin:' . C::TEMPLATE_PREFIX('param_def_options_' . $this->paramConfig->definitionId);
    }

    public function getTemplate()
    {
        return 'admin:' . C::TEMPLATE_PREFIX('param_def_' . $this->paramConfig->definitionId);
    }

    protected function getDefaultTemplateParams($context)
    {
        $paramConfig = $this->paramConfig;

        return [
            'paramId' => $paramConfig->paramId,
            'paramTitle' => $paramConfig->title,
            'definitionId' => $paramConfig->definitionId,
            'options' => $this->options
        ];
    }

    public function verifyOptions(Request $request, array &$options, &$error = null)
    {
        return true;
    }

    public function prettyValue($raw, User $user)
    {
        return $raw;
    }
}