<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Service\Criterion;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\Entity\Category;
use CMTV\CriteriaBuilder\Entity\Criterion;
use CMTV\CriteriaBuilder\Entity\Param;
use CMTV\CriteriaBuilder\TransferArray\TransferArray;
use XF\Mvc\Entity\Finder;
use XF\Service\AbstractXmlExport;

class Export extends AbstractXmlExport
{
    public function getRootName()
    {
        return 'criteria_export';
    }

    public function export(Finder $criteria)
    {
        $criteria = $criteria->fetch();

        if ($criteria->count())
        {
            $categories = $this->finder(C::ADDON_PREFIX('Category'))
                ->with('MasterTitle')
                ->order(['display_order'])
                ->where('category_id', $criteria->pluckNamed('category_id'));

            return $this->exportFromEntities($criteria, $categories->fetch());
        }
        else
        {
            $this->throwNoCriteriaError();
        }
    }

    public function exportFromEntities($criteria, $categories)
    {
        $document = $this->createXml();
        $rootNode = $document->createElement($this->getRootName());
        $document->appendChild($rootNode);

        $categoriesNode = $document->createElement('categories');

        /** @var Category $category */
        foreach ($categories as $category)
        {
            $categoriesNode->appendChild(TransferArray::getFromEntity($category)->toXml($document));
        }

        $criteriaNode = $document->createElement('criteria');
        $paramsNode = $document->createElement('params');

        /** @var Criterion $criterion */
        foreach ($criteria as $criterion)
        {
            $criteriaNode->appendChild(TransferArray::getFromEntity($criterion)->toXml($document));

            /** @var Param $param */
            foreach ($criterion->params as $param)
            {
                $paramsNode->appendChild(TransferArray::getFromEntity($param)->toXml($document));
            }
        }

        $rootNode->appendChild($categoriesNode);
        $rootNode->appendChild($criteriaNode);
        $rootNode->appendChild($paramsNode);

        return $document;
    }

    protected function throwNoCriteriaError()
    {
        throw new \XF\PrintableException(\XF::phrase(C::PHRASE_PREFIX('please_select_at_least_one_criterion_to_export'))->render());
    }
}