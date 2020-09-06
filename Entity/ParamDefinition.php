<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Entity;

use CMTV\CriteriaBuilder\Constants as C;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Repository\AddOn;

/**
 * COLUMNS
 * @property string definition_id
 * @property string definition_class
 * @property string icon
 * @property int display_order
 * @property string addon_id
 *
 * GETTERS
 * @property \XF\Phrase title
 * @property \XF\Phrase description
 * @property \CMTV\CriteriaBuilder\Param\AbstractParam handler
 *
 * RELATIONS
 * @property \XF\Entity\AddOn AddOn
 * @property \XF\Entity\Phrase MasterTitle
 * @property \XF\Entity\Phrase MasterDescription
 */
class ParamDefinition extends Entity
{
    /* ============================================================================================================== */
    /* STRUCTURE */
    /* ============================================================================================================== */

    public static function getStructure(Structure $structure)
    {
        $structure->table = C::DB_PREFIX('param_definition');
        $structure->shortName = C::ADDON_PREFIX('ParamDefinition');
        $structure->primaryKey = 'definition_id';

        $structure->columns = [
            'definition_id' => [
                'type' => self::STR,
                'maxLength' => 50,
                'match' => 'alphanumeric',
                'required' => true
            ],
            'definition_class' => [
                'type' => self::STR,
                'maxLength' => 100,
                'required' => true
            ],
            'icon' => [
                'type' => self::STR,
                'maxLength' => 50,
                'default' => ''
            ],
            'display_order' => [
                'type' => self::UINT,
                'default' => 10
            ],
            'addon_id' => [
                'type' => self::BINARY,
                'maxLength' => 50,
                'default' => ''
            ]
        ];

        $structure->behaviors = [
            'XF:DevOutputWritable' => []
        ];

        $structure->getters = [
            'title' => true,
            'description' => true,
            'handler' => true
        ];

        $structure->relations = [
            'AddOn' => [
                'entity' => 'XF:AddOn',
                'type' => self::TO_ONE,
                'conditions' => 'addon_id',
                'primary' => true
            ],
            'MasterTitle' => [
                'entity' => 'XF:Phrase',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['language_id', '=', 0],
                    ['title', '=', C::PHRASE_PREFIX('param_def.'), '$definition_id']
                ]
            ],
            'MasterDescription' => [
                'entity' => 'XF:Phrase',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['language_id', '=', 0],
                    ['title', '=', C::PHRASE_PREFIX('param_def_desc.'), '$definition_id']
                ]
            ]
        ];

        return $structure;
    }

    /* ============================================================================================================== */
    /* LIFE CYCLE */
    /* ============================================================================================================== */

    protected function _setupDefaults()
    {
        /** @var AddOn $addOnRepo */
        $addOnRepo = $this->_em->getRepository('XF:AddOn');
        $this->addon_id = $addOnRepo->getDefaultAddOnId();
    }

    protected function _preSave()
    {
        if (strpos($this->definition_class, ':') !== false)
        {
            $this->definition_class = \XF::stringToClass($this->definition_class, '%s\Param\%s');
        }
        if (!class_exists($this->definition_class))
        {
            $this->error(\XF::phrase('invalid_class_x', ['class' => $this->definition_class]), 'definition_class');
        }
    }

    protected function _postSave()
    {
        if ($this->isUpdate())
        {
            if ($this->isChanged('addon_id'))
            {
                $writeDevOutput = $this->getBehavior('XF:DevOutputWritable')->getOption('write_dev_output');

                /** @var Phrase $titlePhrase */
                $titlePhrase = $this->getExistingRelation('MasterTitle');
                if ($titlePhrase)
                {
                    $titlePhrase->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', $writeDevOutput);

                    $titlePhrase->addon_id = $this->addon_id;
                    $titlePhrase->title = $this->getTitlePhraseName();
                    $titlePhrase->save();
                }

                /** @var Phrase $descriptionPhrase */
                $descriptionPhrase = $this->getExistingRelation('MasterDescription');
                if ($descriptionPhrase)
                {
                    $descriptionPhrase->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', $writeDevOutput);

                    $descriptionPhrase->addon_id = $this->addon_id;
                    $descriptionPhrase->title = $this->getDescriptionPhraseName();
                    $descriptionPhrase->save();
                }
            }
        }
    }

    protected function _postDelete()
    {
        $writeDevOutput = $this->getBehavior('XF:DevOutputWritable')->getOption('write_dev_output');

        $titlePhrase = $this->MasterTitle;
        if ($titlePhrase)
        {
            $titlePhrase->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', $writeDevOutput);

            $titlePhrase->delete();
        }

        $descriptionPhrase = $this->MasterDescription;
        if ($descriptionPhrase)
        {
            $descriptionPhrase->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', $writeDevOutput);

            $descriptionPhrase->delete();
        }

        $finder = $this->finder(C::ADDON_PREFIX('Param'))->where('definition_id', $this->definition_id);

        foreach ($finder->fetch() as $param)
        {
            $param->delete();
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
        return \XF::phrase($this->getTitlePhraseName());
    }

    /**
     * @return \XF\Phrase
     */
    public function getDescription()
    {
        return \XF::phrase($this->getDescriptionPhraseName());
    }

    /**
     * @return \CMTV\CriteriaBuilder\Param\AbstractParam
     */
    public function getHandler()
    {
        $class = \XF::stringToClass($this->definition_class, '%s\Param\$s');

        if (!class_exists($class))
        {
            $this->error(\XF::phrase('invalid_class_x', ['class' => $this->definition_class]), 'definition_class');
        }

        $class = \XF::extendClass($class);
        $paramConfig = \CMTV\CriteriaBuilder\Param\ParamConfig::create($this);

        return new $class($this->app(), $paramConfig);
    }

    /* ============================================================================================================== */
    /* ... */
    /* ============================================================================================================== */

    public function isActive()
    {
        $addOn = $this->AddOn;
        return $addOn ? $addOn->active : false;
    }

    public function renderOptions()
    {
        return $this->handler ? $this->handler->renderOptions() : '';
    }

    public function getTitlePhraseName()
    {
        return C::PHRASE_PREFIX('param_def.' . $this->definition_id);
    }

    public function getDescriptionPhraseName()
    {
        return C::PHRASE_PREFIX('param_def_desc.' . $this->definition_id);
    }

    public function getMasterPhrase(bool $title = true)
    {
        $phrase = $title ? $this->MasterTitle : $this->MasterDescription;

        if (!$phrase)
        {
            $phrase = $this->_em->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function () use ($title) {
                return $title ? $this->getTitlePhraseName() : $this->getDescriptionPhraseName();
            }, 'save');
            $phrase->language_id = 0;
            $phrase->addon_id = ''; //$this->_getDeferredValue(function () { return $this->addon_id; });
        }

        return $phrase;
    }
}