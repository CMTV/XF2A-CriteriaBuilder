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

/**
 * COLUMNS
 * @property string criterion_id
 * @property string criteria_type
 * @property string code
 * @property string category_id
 * @property int display_order
 * @property bool is_imported
 *
 * GETTERS
 * @property \XF\Phrase title
 * @property \XF\Phrase description
 * @property Param[] params
 *
 * RELATIONS
 * @property Category Category
 * @property \XF\Entity\Phrase MasterTitle
 * @property \XF\Entity\Phrase MasterDescription
 */
class Criterion extends TransferableEntity
{
    /* ============================================================================================================== */
    /* STRUCTURE */
    /* ============================================================================================================== */

    public static function getStructure(Structure $structure)
    {
        $structure->table = C::DB_PREFIX('criterion');
        $structure->shortName = C::ADDON_PREFIX('Criterion');
        $structure->primaryKey = 'criterion_id';

        $structure->columns = [
            'criterion_id' => [
                'type' => self::STR,
                'maxLength' => 50,
                'required' => true,
                'unique' => true,
                'match' => 'alphanumeric'
            ],
            'criteria_type' => [
                'type' => self::STR,
                'maxLength' => 100,
                'required' => true
            ],
            'code' => [
                'type' => self::STR,
                'default' => ''
            ],
            'category_id' => [
                'type' => self::STR,
                'maxLength' => 50,
                'default' => ''
            ],
            'display_order' => [
                'type' => self::UINT,
                'default' => 10
            ],
            'is_imported' => [
                'type' => self::BOOL,
                'default' => false
            ]
        ];

        $structure->getters = [
            'title' => true,
            'description' => true,
            'params' => true
        ];

        $structure->relations = [
            'Category' => [
                'entity' => C::ADDON_PREFIX('Category'),
                'type' => self::TO_ONE,
                'conditions' => 'category_id'
            ],
            'MasterTitle' => [
                'entity' => 'XF:Phrase',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['language_id', '=', 0],
                    ['title', '=', C::PHRASE_PREFIX('criterion_title.'), '$criterion_id']
                ]
            ],
            'MasterDescription' => [
                'entity' => 'XF:Phrase',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['language_id', '=', 0],
                    ['title', '=', C::PHRASE_PREFIX('criterion_description.'), '$criterion_id']
                ]
            ]
        ];

        return $structure;
    }

    /* ============================================================================================================== */
    /* LIFE CYCLE */
    /* ============================================================================================================== */

    protected function _postSave()
    {
        if ($this->isInsert())
        {
            if ($this->Category)
            {
                $this->Category->updateCriteriaCount();
            }
        }
    }

    protected function _postDelete()
    {
        if ($this->MasterTitle)
        {
            $this->MasterTitle->delete();
        }

        if ($this->MasterDescription)
        {
            $this->MasterDescription->delete();
        }

        foreach ($this->params as $param)
        {
            $param->delete();
        }

        if ($this->Category)
        {
            $this->Category->updateCriteriaCount();
        }
    }

    /* ============================================================================================================== */
    /* GETTERS */
    /* ============================================================================================================== */

    public function getTitle()
    {
        return \XF::phrase($this->getTitlePhraseName());
    }

    public function getDescription()
    {
        return \XF::phrase($this->getDescriptionPhraseName());
    }

    public function getParams()
    {
        return $this->finder(C::ADDON_PREFIX('Param'))
            ->where('criterion_id', $this->criterion_id)
            ->order('display_order')
            ->fetch();
    }

    /* ============================================================================================================== */
    /* TRANSFER */
    /* ============================================================================================================== */

    public function transferArrayClass(): string
    {
        return C::ADDON_PREFIX('Criterion');
    }

    /* ============================================================================================================== */
    /* ... */
    /* ============================================================================================================== */

    public function getTitlePhraseName()
    {
        return C::PHRASE_PREFIX('criterion_title.' . $this->criterion_id);
    }

    public function getDescriptionPhraseName()
    {
        return C::PHRASE_PREFIX('criterion_description.' . $this->criterion_id);
    }

    public function getMasterPhrase(bool $title)
    {
        $phrase = $title ? $this->MasterTitle : $this->MasterDescription;

        if (!$phrase)
        {
            $phrase = $this->_em->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function () use ($title) {
                return $title ? $this->getTitlePhraseName() : $this->getDescriptionPhraseName();
            }, 'save');
            $phrase->language_id = 0;
            $phrase->addon_id = 'CMTV/CriteriaBuilder';
        }

        return $phrase;
    }
}