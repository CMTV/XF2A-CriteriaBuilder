<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Param;

use CMTV\CriteriaBuilder\Constants as C;

class ParamConfig
{
    public $paramId;
    public $definitionId;
    public $criterionId;
    public $title;
    public $options;

    public function __construct($paramId, $definitionId, $criterionId, $title, array $options)
    {
        $this->paramId = $paramId;
        $this->definitionId = $definitionId;
        $this->criterionId = $criterionId;
        $this->title = $title;
        $this->options = $options;
    }

    public static function create($data)
    {
        if (!is_array($data) && !($data instanceof \XF\Mvc\Entity\Entity))
        {
            throw new \InvalidArgumentException(
                \XF::phrase(C::PHRASE_PREFIX('data_passed_into_create_param_config_should_either_be_array_or_entity'))
            );
        }

        if (is_array($data))
        {
            $data = array_replace([
                'param_id' => '',
                'definition_id' => '',
                'criterion_id' => '',
                'title' => '',
                'options' => []
            ], $data);
        }

        return new self(
            $data['param_id'],
            $data['definition_id'],
            $data['criterion_id'],
            $data['title'],
            $data['options'] ?: []
        );
    }
}