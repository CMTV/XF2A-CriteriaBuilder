<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Param;

use XF\Entity\User;

class ThreadPrefixSelect extends AbstractParam
{
    protected function getDefaultTemplateParams($context)
    {
        $params = parent::getDefaultTemplateParams($context);

        if ($context === 'render')
        {
            /** @var \XF\Repository\ThreadPrefix $prefixRepo */
            $prefixRepo = $this->app->repository('XF:ThreadPrefix');
            $prefixListData = $prefixRepo->getPrefixListData();

            $params['prefixGroups'] = $prefixListData['prefixGroups'];
            $params['prefixesGrouped'] = $prefixListData['prefixesGrouped'];
        }

        return $params;
    }

    public function prettyValue($raw, User $user)
    {
        $prefixIds = [];

        if (is_array($raw))
        {
            foreach (array_keys($raw) as $prefixId)
            {
                $prefixIds[] = $prefixId;
            }
        }

        return $prefixIds;
    }
}