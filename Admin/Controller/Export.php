<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Admin\Controller;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\ControllerPlugin\ListEmpty;
use XF\Admin\Controller\AbstractController;
use XF\ControllerPlugin\Xml;

class Export extends AbstractController
{
    /* ============================================================================================================== */
    /* ACTIONS */
    /* ============================================================================================================== */

    public function actionIndex()
    {
        $viewParams = $this->getCriterionRepo()->getCriteriaListData();

        if ($viewParams['totalCriteria'] === 0)
        {
            /** @var ListEmpty $plugin */
            $plugin = $this->plugin(C::ADDON_PREFIX('ListEmpty'));

            return $plugin->actionListEmpty(
                C::PHRASE_PREFIX('criteria'),
                C::PHRASE_PREFIX('add_criterion'),
                C::PHRASE_PREFIX('no_criteria_have_been_created'),
                $this->buildLink('criteria-builder/criteria/add')
            );
        }

        return $this->view(
            C::ADDON_PREFIX('Criterion\Export'),
            C::TEMPLATE_PREFIX('export'),
            $viewParams
        );
    }

    public function actionProceed()
    {
        $exportIds = $this->filter('export', 'array');

        $criteria = $this->finder(C::ADDON_PREFIX('Criterion'))
            ->whereIds($exportIds)
            ->order(['Category.display_order', 'display_order']);

        /** @var Xml $plugin */
        $plugin = $this->plugin('XF:Xml');

        return $plugin->actionExport(
            $criteria,
            C::ADDON_PREFIX('Criterion\Export')
        );
    }

    /* ============================================================================================================== */
    /* ... */
    /* ============================================================================================================== */

    /**
     * @return \CMTV\CriteriaBuilder\Repository\Criterion
     */
    protected function getCriterionRepo()
    {
        return $this->repository(C::ADDON_PREFIX('Criterion'));
    }
}