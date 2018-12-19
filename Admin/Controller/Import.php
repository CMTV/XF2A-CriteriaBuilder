<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Admin\Controller;

use CMTV\CriteriaBuilder\Constants as C;
use XF\Admin\Controller\AbstractController;

class Import extends AbstractController
{
    public function actionIndex()
    {
        return $this->view(
            C::ADDON_PREFIX('Criterion\Import'),
            C::TEMPLATE_PREFIX('import'),
            []
        );
    }

    public function actionResolve()
    {
        $this->assertPostOnly();

        /** @var \CMTV\CriteriaBuilder\Service\Criterion\Import $importer */
        $importer = $this->service(C::ADDON_PREFIX('Criterion\Import'));

        $upload = $this->request->getFile('upload', false);

        if (!$upload)
        {
            return $this->error(\XF::phrase(C::PHRASE_PREFIX('please_upload_valid_criteria_xml_file')));
        }

        try
        {
            $xml = \XF\Util\Xml::openFile($upload->getTempFile());
        }
        catch (\Exception $e)
        {
            $xml = null;
        }

        if (!$xml || $xml->getName() != 'criteria_export')
        {
            return $this->error(\XF::phrase(C::PHRASE_PREFIX('please_upload_valid_criteria_xml_file')));
        }

        $criteriaData = $importer->getCriteriaDataFromXml($xml);

        // dump($criteriaData); // Debug

        $selectCategories = [];
        $selectCategories['new'] = [];
        $selectCategories['existing'] = $this->getCategoryRepo()->getCategoriesTitlePairs();

        /** @var \CMTV\CriteriaBuilder\TransferArray\Category $category */
        foreach ($criteriaData['newCategories'] as $category)
        {
            $selectCategories['new'][$category->id] = $category->title;
        }

        $viewParams = array_merge($criteriaData, [
            'selectCategories' => $selectCategories
        ]);

        return $this->view(
            C::ADDON_PREFIX('Criterion\Import\Resolve'),
            C::TEMPLATE_PREFIX('import_resolving'),
            $viewParams
        );
    }

    public function actionConfirm()
    {
        $this->assertPostOnly();

        $input = $this->filterFormJson([
            'newCategories' => 'array',
            'criteria' => 'array-array',
            'params' => 'array-array',
            'import' => 'array'
        ]);

        $newCriteria = [];

        if (isset($input['criteria']['new']) && isset($input['import']['new']))
        {
            foreach ($input['criteria']['new'] as $criterionId => $criterion)
            {
                if (array_key_exists($criterionId, $input['import']['new']))
                {
                    $newCriteria[] = $criterion;
                }
            }
        }

        $upgradeCriteria = [];

        if (isset($input['criteria']['upgrade']) && isset($input['import']['upgrade']))
        {
            foreach ($input['criteria']['upgrade'] as $criterionId => $criterion)
            {
                if (array_key_exists($criterionId, $input['import']['upgrade']) && $input['import']['upgrade'][$criterionId] === '1')
                {
                    $upgradeCriteria[] = $criterion;
                }
            }
        }

        /** @var \CMTV\CriteriaBuilder\Service\Criterion\Import $importer */
        $importer = $this->service(C::ADDON_PREFIX('Criterion\Import'));
        $importer->doImport($newCriteria, $upgradeCriteria, $input['newCategories'], $input['params'], $errors);

        if (empty($errors))
        {
            return $this->redirect($this->buildLink('criteria-builder/criteria'));
        }
        else
        {
            return $this->error($errors);
        }
    }

    /**
     * @return \CMTV\CriteriaBuilder\Repository\Category
     */
    protected function getCategoryRepo()
    {
        return $this->repository(C::ADDON_PREFIX('Category'));
    }
}