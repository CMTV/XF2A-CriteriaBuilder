<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Entity;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\Param\AbstractParam;
use CMTV\CriteriaBuilder\Param\ParamConfig;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property string criterion_id
 * @property string param_id
 * @property string definition_id
 * @property array options
 * @property int display_order
 *
 * GETTERS
 * @property \XF\Phrase title
 * @property AbstractParam handler
 *
 * RELATIONS
 * @property \XF\Entity\Phrase MasterTitle
 * @property ParamDefinition Definition
 */
class Param extends TransferableEntity
{
    /* ============================================================================================================== */
    /* STRUCTURE */
    /* ============================================================================================================== */

    public static function getStructure(Structure $structure)
    {
        $structure->table = C::DB_PREFIX('param');
        $structure->shortName = C::ADDON_PREFIX('Param');
        $structure->primaryKey = ['criterion_id', 'param_id'];

        $structure->columns = [
            'criterion_id' => [
                'type' => self::STR,
                'match' => 'alphanumeric',
                'maxLength' => 50,
                'required' => true
            ],
            'param_id' => [
                'type' => self::STR,
                'match' => 'alphanumeric',
                'maxLength' => 50,
                'required' => true
            ],
            'definition_id' => [
                'type' => self::STR,
                'match' => 'alphanumeric',
                'maxLength' => 50,
                'required' => true
            ],
            'options' => [
                'type' => self::JSON_ARRAY,
                'default' => []
            ],
            'display_order' => [
                'type' => self::UINT,
                'default' => 10
            ]
        ];

        $structure->getters = [
            'title' => true,
            'handler' => true
        ];

        $structure->relations = [
            'MasterTitle' => [
                'entity' => 'XF:Phrase',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['language_id', '=', 0],
                    ['title', '=', C::PHRASE_PREFIX('param.'), '$criterion_id', '_', '$param_id']
                ]
            ],
            'Definition' => [
                'entity' => C::ADDON_PREFIX('ParamDefinition'),
                'type' => self::TO_ONE,
                'conditions' => 'definition_id',
                'primary' => true
            ]
        ];

        return $structure;
    }

    /* ============================================================================================================== */
    /* LIFE CYCLE */
    /* ============================================================================================================== */

    protected function _postDelete()
    {
        if ($this->MasterTitle)
        {
            $this->MasterTitle->delete();
        }
    }

    protected function _preSave()
    {
        $duplicate = $this->finder(C::ADDON_PREFIX('Param'))
            ->whereId([$this->criterion_id, $this->param_id])->fetchOne();

        if ($duplicate && $this->isInsert())
        {
            $this->error(\XF::phrase(C::PHRASE_PREFIX('param_id_must_be_unique_for_criterion')));
        }

        $table = C::DB_PREFIX('param');
        $maxDisplayOrder = $this->db()->query("SELECT MAX(`display_order`) FROM {$table}")->fetchAllColumn()[0];

        if ($maxDisplayOrder && $this->isInsert())
        {
            $this->display_order = $maxDisplayOrder + 10;
        }
    }

    /* ============================================================================================================== */
    /* GETTERS */
    /* ============================================================================================================== */

    /**
     * @return \XF\Phrase
     */
    public function getTitle()
    {
        $definition = $this->Definition;
        $paramPhrase = \XF::phrase($this->getPhraseName());
        return $paramPhrase->render('html', ['nameOnInvalid' => false]) ?: ($definition ? $definition->title : '');
    }

    /**
     * @return AbstractParam|null
     */
    public function getHandler()
    {
        $definition = $this->Definition;

        if (!$definition)
        {
            return null;
        }

        $class = \XF::stringToClass($definition->definition_class, '%s\Param\%s');

        if (!class_exists($class))
        {
            return null;
        }

        $class = \XF::extendClass($class);

        $paramConfig = ParamConfig::create($this);
        return new $class($this->app(), $paramConfig);
    }

    /* ============================================================================================================== */
    /* TRANSFER */
    /* ============================================================================================================== */

    public function transferArrayClass(): string
    {
        return C::ADDON_PREFIX('Param');
    }

    /* ============================================================================================================== */
    /* ... */
    /* ============================================================================================================== */

    public function renderQuickView()
    {
        $output = $this->app()->templater()->renderTemplate(
            'admin:' . C::TEMPLATE_PREFIX('param_quick_view'),
            ['param' => $this]
        );

        return $output;
    }

    public function renderEditRow()
    {
        $output = $this->app()->templater()->renderTemplate(
            'admin:' . C::TEMPLATE_PREFIX('param_edit_row'),
            ['param' => $this]
        );

        return $output;
    }

    public function renderOptions()
    {
        return $this->handler ? $this->handler->renderOptions() : '';
    }

    public function render($criteriaInput, $criterionName, $criteria, $criteriaData)
    {
        return $this->handler ? $this->handler->render($criteriaInput, $criterionName, $criteria, $criteriaData) : '';
    }

    public function getPhraseName()
    {
        return C::PHRASE_PREFIX('param.' . $this->criterion_id . '_' . $this->param_id);
    }

    public function getMasterPhrase()
    {
        $phrase = $this->MasterTitle;

        if (!$phrase)
        {
            $phrase = $this->_em->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function() { return $this->getPhraseName(); });
            $phrase->language_id = 0;
            $phrase->addon_id = 'CMTV/CriteriaBuilder';
        }

        return $phrase;
    }
}