<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\TransferArray;

use CMTV\CriteriaBuilder\Constants as C;

/**
 * VALUES
 * @property string $id
 * @property string $icon
 * @property int $display_order
 * @property string $title
 */
class Category extends TransferArray
{
    public static function getTransferStructure(array $structure): array
    {
        $structure = [
            'id' => [
                'type' => self::ATTRIBUTE,
                'column' => 'category_id'
            ],
            'icon' => [
                'type' => self::ATTRIBUTE
            ],
            'display_order' => [
                'type' => self::ATTRIBUTE,
                'valueType' => self::INT
            ],
            'title' => [
                'type' => self::ATTRIBUTE,
                'getter' => function (\CMTV\CriteriaBuilder\Entity\Category $category) { return $category->MasterTitle->phrase_text; },
                'setter' => function (\CMTV\CriteriaBuilder\Entity\Category &$category, Category $categoryTA) {
                    $masterTitle = $category->getMasterPhrase();
                    $masterTitle->phrase_text = $categoryTA->title;
                    $category->addCascadedSave($masterTitle);
                }
            ]
        ];

        return $structure;
    }

    public function getNodeName(): string
    {
        return 'category';
    }

    public function getEntityShortName(): string
    {
        return C::ADDON_PREFIX('Category');
    }
}