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
 * @property string $criterion_id
 * @property string $definition_id
 * @property int $display_order
 * @property string $title
 * @property string $options
 */
class Param extends TransferArray
{
    public static function getTransferStructure(array $structure): array
    {
        $structure = [
            'id' => [
                'type' => self::ATTRIBUTE,
                'column' => 'param_id'
            ],
            'criterion_id' => [
                'type' => self::ATTRIBUTE
            ],
            'definition_id' => [
                'type' => self::ATTRIBUTE
            ],
            'display_order' => [
                'type' => self::ATTRIBUTE,
                'valueType' => self::INT
            ],
            'title' => [
                'type' => self::ATTRIBUTE,
                'getter' => function (\CMTV\CriteriaBuilder\Entity\Param $param) { return $param->MasterTitle->phrase_text; },
                'setter' => function (\CMTV\CriteriaBuilder\Entity\Param &$param, Param $paramTA) {
                    $masterTitle = $param->getMasterPhrase();
                    $masterTitle->phrase_text = $paramTA->title;
                    $param->addCascadedSave($masterTitle);
                }
            ],
            'options' => [
                'type' => self::CDATA,
                'getter' => function (\CMTV\CriteriaBuilder\Entity\Param $param) {
                    return $param->options ? json_encode($param->options) : '';
                },
                'setter' => function (\CMTV\CriteriaBuilder\Entity\Param &$param, Param $paramTA) {
                    $param->options = $paramTA->options ? json_decode($paramTA->options, true) : [];
                }
            ]
        ];

        return $structure;
    }

    public function getNodeName(): string
    {
        return 'param';
    }

    public function getEntityShortName(): string
    {
        return C::ADDON_PREFIX('Param');
    }
}