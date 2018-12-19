<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\Data\CriteriaType;
use CMTV\CriteriaBuilder\Repository\Criterion;
use XF\Entity\User;

class EventListener
{
    public static function criteriaTemplateData(array &$templateData)
    {
        $data = [];

        /** @var CriteriaType $supportedCriteriaTypesData */
        $supportedCriteriaTypesData = \XF::app()->data(C::ADDON_PREFIX('CriteriaType'));

        foreach ($supportedCriteriaTypesData->getSupportedTypes() as $criteriaTypeId => $criteriaType)
        {
            /** @var Criterion $criterionRepo */
            $criterionRepo = \XF::repository(C::ADDON_PREFIX('Criterion'));

            $data[$criteriaTypeId] = $criterionRepo->getCriteriaDataOfType($criteriaTypeId);
        }

        $templateData['CMTV_CB'] = $data;
    }

    public static function criteriaUser($rule, array $data, User $user, &$returnValue)
    {
        $returnValue = self::getCriterionRepo()->check($rule, $data, $user);
    }

    public static function criteriaPage($rule, array $data, User $user, array $params, &$returnValue)
    {
        $returnValue = self::getCriterionRepo()->check($rule, $data, $user, $params);
    }

    /**
     * @return Criterion
     */
    protected static function getCriterionRepo()
    {
        return \XF::repository(C::ADDON_PREFIX('Criterion'));
    }
}