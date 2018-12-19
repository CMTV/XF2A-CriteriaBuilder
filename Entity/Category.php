<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Entity;

use CMTV\CriteriaBuilder\Constants as C;
use function GuzzleHttp\Psr7\parse_header;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property string category_id
 * @property int display_order
 * @property string icon
 * @property int criteria
 *
 * GETTERS
 * @property \XF\Phrase title
 *
 * RELATIONS
 * @property \XF\Entity\Phrase MasterTitle
 * @property \CMTV\CriteriaBuilder\Entity\Criterion[] Criteria
 */
class Category extends TransferableEntity
{
    /* ============================================================================================================== */
    /* STRUCTURE */
    /* ============================================================================================================== */

    public static function getStructure(Structure $structure)
    {
        $structure->table = C::DB_PREFIX('category');
        $structure->shortName = C::ADDON_PREFIX('Category');
        $structure->primaryKey = 'category_id';

        $structure->columns = [
            'category_id' => [
                'type' => self::STR,
                'nullable' => true,
                'maxLength' => 50,
                'required' => true,
                'unique' => true,
                'match' => 'alphanumeric'
            ],
            'display_order' => [
                'type' => self::UINT,
                'default' => 10
            ],
            'icon' => [
                'type' => self::STR,
                'maxLength' => 50,
                'default' => ''
            ],
            'criteria' => [
                'type' => self::UINT,
                'default' => 0
            ]
        ];

        $structure->getters = [
            'title' => true
        ];

        $structure->relations = [
            'MasterTitle' => [
                'entity' => 'XF:Phrase',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['language_id', '=', 0],
                    ['title', '=', C::PHRASE_PREFIX('category_title.'), '$category_id']
                ]
            ],
            'Criteria' => [
                'entity' => C::ADDON_PREFIX('Criterion'),
                'type' => self::TO_MANY,
                'conditions' => [
                    ['category_id', '=', '$category_id']
                ]
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

    /* ============================================================================================================== */
    /* GETTERS */
    /* ============================================================================================================== */

    /**
     * @return \XF\Phrase
     */
    public function getTitle()
    {
        return \XF::phrase($this->getPhraseName());
    }

    /* ============================================================================================================== */
    /* DELETE VARIANTS */
    /* ============================================================================================================== */

    public function deleteMove($targetId)
    {
        $this->delete();

        $this->db()->update(
            C::DB_PREFIX('criterion'),
            ['category_id' => $targetId],
            'category_id = ?', $this->category_id
        );

        if ($targetId !== '')
        {
            /** @var Category $targetCategory */
            $targetCategory = $this->finder(C::ADDON_PREFIX('Category'))->whereId($targetId)->fetchOne();
            $targetCategory->updateCriteriaCount();
        }
    }

    public function deleteWithCriteria()
    {
        $this->delete();

        foreach ($this->Criteria as $criterion)
        {
            $criterion->delete();
        }
    }

    /* ============================================================================================================== */
    /* TRANSFER */
    /* ============================================================================================================== */

    public function transferArrayClass(): string
    {
        return C::ADDON_PREFIX('Category');
    }

    /* ============================================================================================================== */
    /* ... */
    /* ============================================================================================================== */

    public function updateCriteriaCount()
    {
        $categoryRepo = $this->getCategoryRepo();
        $count = $categoryRepo->getCategoryCriteriaCount($this->category_id);
        $this->fastUpdate('criteria', $count);
    }

    public function getPhraseName()
    {
        if ($this->category_id === null)
        {
            return C::PHRASE_PREFIX('category_title_uncategorised');
        }
        else
        {
            return C::PHRASE_PREFIX('category_title.' . $this->category_id);
        }
    }

    public function getMasterPhrase()
    {
        $phrase = $this->MasterTitle;

        if (!$phrase)
        {
            $phrase = $this->_em->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function() { return $this->getPhraseName(); }, 'save');
            $phrase->language_id = 0;
            $phrase->addon_id = 'CMTV/CriteriaBuilder';
        }

        return $phrase;
    }

    /**
     * @return \CMTV\CriteriaBuilder\Repository\Category
     */
    protected function getCategoryRepo()
    {
        return $this->repository(C::ADDON_PREFIX('Category'));
    }
}