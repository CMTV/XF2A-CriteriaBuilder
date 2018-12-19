<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Admin\View\Criterion;

use XF\Mvc\View;

class Export extends View
{
    public function renderXml()
    {
        /** @var \DOMDocument $document */
        $document = $this->params['xml'];

        $this->response->setDownloadFileName('criteria.xml');

        return $document->saveXml();
    }
}