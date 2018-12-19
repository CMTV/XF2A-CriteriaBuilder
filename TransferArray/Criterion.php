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
 * @property string $criteria_type
 * @property string $category_id
 * @property string $code
 * @property int $display_order
 * @property string $title
 * @property string $description
 */
class Criterion extends TransferArray
{
    public static function getTransferStructure(array $structure): array
    {
        $structure = [
            'id' => [
                'type' => self::ATTRIBUTE,
                'column' => 'criterion_id'
            ],
            'criteria_type' => [
                'type' => self::ATTRIBUTE
            ],
            'category_id' => [
                'type' => self::ATTRIBUTE
            ],
            'display_order' => [
                'type' => self::ATTRIBUTE,
                'valueType' => self::INT
            ],
            'title' => [
                'type' => self::ATTRIBUTE,
                'getter' => function (\CMTV\CriteriaBuilder\Entity\Criterion $criterion) { return $criterion->MasterTitle->phrase_text; },
                'setter' => function (\CMTV\CriteriaBuilder\Entity\Criterion &$criterion, Criterion $criterionTA) {
                    $masterTitle = $criterion->getMasterPhrase(true);
                    $masterTitle->phrase_text = $criterionTA->title;
                    $criterion->addCascadedSave($masterTitle);
                }
            ],
            'description' => [
                'type' => self::CDATA,
                'getter' => function (\CMTV\CriteriaBuilder\Entity\Criterion $criterion) { return $criterion->MasterDescription->phrase_text; },
                'setter' => function (\CMTV\CriteriaBuilder\Entity\Criterion &$criterion, Criterion $criterionTA) {
                    $masterDescription = $criterion->getMasterPhrase(false);
                    $masterDescription->phrase_text = $criterionTA->description;
                    $criterion->addCascadedSave($masterDescription);
                }
            ],
            'code' => [
                'type' => self::CDATA
            ]
        ];

        return $structure;
    }

    public function getNodeName(): string
    {
        return 'criterion';
    }

    public function getEntityShortName(): string
    {
        return C::ADDON_PREFIX('Criterion');
    }
}