<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Service\Criterion;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\Data\CriteriaType;
use CMTV\CriteriaBuilder\Entity\Category;
use CMTV\CriteriaBuilder\Entity\Criterion;
use CMTV\CriteriaBuilder\Repository\Param;
use CMTV\CriteriaBuilder\TransferArray\TransferArray;
use XF\Service\AbstractService;

class Import extends AbstractService
{
    public function getCriteriaDataFromXml(\SimpleXMLElement $xml)
    {
        // dump($xml); // Debug

        $paramsData = $this->getParams($xml);

        return [
            'newCategories' => $this->getNewCategories($xml),
            'criteria' => $this->getCriteriaForResolving($xml, $paramsData['errorMap'], $paramsData['criteriaParamIds'], $paramsData['upgradeMap']),
            'params' => $paramsData['params']
        ];
    }

    public function doImport(array $newCriteria, array $upgradeCriteria, array $categories, array $params, &$errors = [])
    {
        $this->db()->beginTransaction();

        $importParamIds = [];

        foreach ($categories as $categoryId => $category)
        {
            $import = false;

            foreach (array_merge($newCriteria, $upgradeCriteria) as $criterion)
            {
                if ($categoryId === $criterion['category_id'])
                {
                    $import = true;
                }
            }

            if (!$import)
            {
                continue;
            }

            /** @var Category $newCategory */
            $newCategory = TransferArray::getFromArray(C::ADDON_PREFIX('Category'), $category)->toEntity();

            if (!$newCategory->preSave())
            {
                foreach ($newCategory->getErrors() as $field => $error)
                {
                    $errors[$field . '__' . $newCategory->category_id] = $error;
                }

                $this->db()->rollback();
                return false;
            }

            $newCategory->save(true, false);
        }

        foreach ($newCriteria as $criterion)
        {
            $importParamIds[] = $criterion['id'];

            /** @var Criterion $newCriterion */
            $newCriterion = TransferArray::getFromArray(C::ADDON_PREFIX('Criterion'), $criterion)->toEntity();
            $newCriterion->is_imported = true;

            if (!$newCriterion->preSave())
            {
                foreach ($newCriterion->getErrors() as $field => $error)
                {
                    $errors[$field . '__' . $newCriterion->criterion_id] = $error;
                }

                $this->db()->rollback();
                return false;
            }

            $newCriterion->save(true, false);
        }

        foreach ($upgradeCriteria as $upgradeData)
        {
            $importParamIds[] = $upgradeData['id'];

            /** @var Criterion $criterion */
            $criterion = $this->finder(C::ADDON_PREFIX('Criterion'))->whereId($upgradeData['id'])->fetchOne();

            $criterion->code = $upgradeData['code'];

            // Deleting all params if upgraded criteria has no params

            $existingParamIds = $this->getParamRepo()->getCriterionParamIds($criterion->criterion_id);

            if($existingParamIds && !in_array($criterion->criterion_id, array_keys($params)))
            {
                foreach ($existingParamIds as $existingParamId)
                {
                    $this->finder(C::ADDON_PREFIX('Param'))->whereId([$criterion->criterion_id, $existingParamId])->fetchOne()->delete();
                }
            }

            //

            if (!$criterion->preSave())
            {
                foreach ($criterion->getErrors() as $field => $error)
                {
                    $errors[$field . '__' . $criterion->criterion_id] = $error;
                }

                $this->db()->rollback();
                return false;
            }

            //dump($criterion);
            $criterion->save(true, false);
        }

        foreach ($params as $criterionId => $_params)
        {
            if (in_array($criterionId, array_column($upgradeCriteria, 'id')))
            {
                $existingParamIds = $this->getParamRepo()->getCriterionParamIds($criterionId);

                if ($existingParamIds)
                {
                    foreach ($existingParamIds as $existingParamId)
                    {
                        if (!in_array($existingParamId, array_keys($_params)))
                        {
                            $this->finder(C::ADDON_PREFIX('Param'))->whereId([$criterionId, $existingParamId])->fetchOne()->delete();
                        }
                    }
                }
            }

            if (in_array($criterionId, $importParamIds))
            {
                foreach ($_params as $_paramId => $_paramData)
                {
                    /** @var \CMTV\CriteriaBuilder\Entity\Param $param */
                    $param = $this->finder(C::ADDON_PREFIX('Param'))->whereId([$criterionId, $_paramId])->fetchOne();

                    if ($param)
                    {
                        $param->definition_id = $_paramData['definition_id'];
                        $param->options = $_paramData['options'] ? json_decode($_paramData['options'], true) : [];
                    } else
                    {
                        /** @var \CMTV\CriteriaBuilder\Entity\Param $param */
                        $param = TransferArray::getFromArray(C::ADDON_PREFIX('Param'), $_paramData)->toEntity();
                    }

                    if (!$param->preSave())
                    {
                        foreach ($param->getErrors() as $field => $error)
                        {
                            $errors[$field . '__' . $param->param_id] = $error;
                        }

                        $this->db()->rollback();
                        return false;
                    }

                    //dump($param);
                    $param->save(true, false);
                }
            }
        }

        if (!$errors)
        {
            $this->db()->commit();
            return true;
        }
        else
        {
            $this->db()->rollback();
            return false;
        }
    }

    protected function getNewCategories(\SimpleXMLElement $xml)
    {
        $newCategories = [];
        $existingIds = $this->getCategoryRepo()->getCategoryIds();

        foreach ($xml->categories->category as $xmlCategory)
        {
            /** @var \CMTV\CriteriaBuilder\TransferArray\Category $newCategory */
            $newCategory = TransferArray::getFromXml(C::ADDON_PREFIX('Category'), $xmlCategory);

            if (!in_array($newCategory->id, $existingIds))
            {
                $newCategories[] = $newCategory;
            }
        }

        return $newCategories;
    }

    protected function getParams(\SimpleXMLElement $xml)
    {
        $params = [];
        $errorMap = [];
        $upgradeMap = [];
        $criteriaParamIds = [];

        $existingParams = $this->getParamRepo()->getParamsForImport();

        foreach ($xml->params->param as $xmlParam)
        {
            /** @var \CMTV\CriteriaBuilder\TransferArray\Param $param */
            $param = TransferArray::getFromXml(C::ADDON_PREFIX('Param'), $xmlParam);

            if ($errors = $this->paramErrors($param))
            {
                $errorMap[$param->criterion_id] = $errors;
                continue;
            }

            $criteriaParamIds[$param->criterion_id][] = $param->id;

            if (array_key_exists($param->criterion_id . '-' . $param->id, $existingParams))
            {
                $diff = TransferArray::diff($param, $existingParams[$param->criterion_id . '-' . $param->id]);

                if (in_array('definition_id', $diff->_diffKeys) || in_array('options', $diff->_diffKeys))
                {
                    $upgradeMap[$param->criterion_id] = true;
                }
            }

            $params[] = $param;
        }

        return [
            'params' => $params,
            'errorMap' => $errorMap,
            'criteriaParamIds' => $criteriaParamIds,
            'upgradeMap' => $upgradeMap
        ];
    }

    protected function getCriteriaForResolving(\SimpleXMLElement $xml, array $paramsErrorMap, array $criteriaParamIds, array $paramsUpgradeMap)
    {
        $new = $upgrade = $error = [];
        $existingCriteria = $this->getCriterionRepo()->getCriteriaForImport();

        foreach ($xml->criteria->criterion as $xmlCriterion)
        {
            /** @var \CMTV\CriteriaBuilder\TransferArray\Criterion $criterion */
            $criterion = TransferArray::getFromXml(C::ADDON_PREFIX('Criterion'), $xmlCriterion);

            $criterionErrors = $this->criterionErrors($criterion);
            $paramErrors = isset($paramsErrorMap[$criterion->id]) ? $paramsErrorMap[$criterion->id] : [];

            if ($criterionErrors || $paramErrors)
            {
                $error[$criterion->title] = array_merge($criterionErrors, $paramErrors);
                continue;
            }

            if (isset($paramsUpgradeMap[$criterion->id]))
            {
                $upgrade[] = $criterion;
                continue;
            }

            if (array_key_exists($criterion->id, $existingCriteria))
            {
                $criterion = TransferArray::diff($criterion, $existingCriteria[$criterion->id]);

                if (in_array('code', $criterion->_diffKeys))
                {
                    $upgrade[] = $criterion;
                }

                $existingParamIds = $this->getParamRepo()->getCriterionParamIds($criterion->id);

                if ($existingParamIds && !isset($criteriaParamIds[$criterion->id]))
                {
                    $upgrade[] = $criterion;
                }

                if (isset($criteriaParamIds[$criterion->id]))
                {
                    foreach ($existingParamIds as $existingParamId)
                    {
                        if (!in_array($existingParamId, $criteriaParamIds[$criterion->id]))
                        {
                            $upgrade[] = $criterion;
                        }
                    }

                    if (sort($criteriaParamIds[$criterion->id]) != sort($this->getParamRepo()->getCriterionParamIds($criterion->id)))
                    {
                        $upgrade[] = $criterion;
                    }
                }
            }
            else
            {
                $new[] = $criterion;
            }
        }

        return [
            'new' => $new,
            'upgrade' => $upgrade,
            'error' => $error
        ];
    }

    protected function paramErrors(\CMTV\CriteriaBuilder\TransferArray\Param $param)
    {
        $errors = [];

        $definitionIds = $this->getParamDefinitionRepo()->getParamDefinitionIds();

        if (!in_array($param->definition_id, $definitionIds))
        {
            $errors[] = \XF::phrase(C::PHRASE_PREFIX('unknown_param_definition_x'), ['definition' => $param->definition_id]);
        }

        return $errors;
    }

    protected function criterionErrors(\CMTV\CriteriaBuilder\TransferArray\Criterion $criterion)
    {
        $errors = [];

        /** @var CriteriaType $supportedCriteriaTypesData */
        $supportedCriteriaTypesData = $this->app->data(C::ADDON_PREFIX('CriteriaType'));
        $criteriaTypes = $supportedCriteriaTypesData->getSupportedTypes();

        if (!array_key_exists($criterion->criteria_type, $criteriaTypes))
        {
            $errors[] = \XF::phrase(C::PHRASE_PREFIX('x_criteria_type_not_supported'), ['type' => $criterion->criteria_type]);
        }

        return $errors;
    }

    /**
     * @return \CMTV\CriteriaBuilder\Repository\Category
     */
    protected function getCategoryRepo()
    {
        return $this->repository(C::ADDON_PREFIX('Category'));
    }

    /**
     * @return \CMTV\CriteriaBuilder\Repository\Criterion
     */
    protected function getCriterionRepo()
    {
        return $this->repository(C::ADDON_PREFIX('Criterion'));
    }

    /**
     * @return \CMTV\CriteriaBuilder\Repository\ParamDefinition
     */
    protected function getParamDefinitionRepo()
    {
        return $this->repository(C::ADDON_PREFIX('ParamDefinition'));
    }

    /**
     * @return Param
     */
    protected function getParamRepo()
    {
        return $this->repository(C::ADDON_PREFIX('Param'));
    }
}