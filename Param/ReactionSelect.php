<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Param;

use XF\Entity\User;
use XF\Repository\Reaction;

class ReactionSelect extends AbstractParam
{
    protected function getDefaultTemplateParams($context)
    {
        $params = parent::getDefaultTemplateParams($context);

        if ($context === 'render')
        {
            /** @var Reaction $reactionRepo */
            $reactionRepo = $this->app->repository('XF:Reaction');

            $params['reactions'] = $reactionRepo->findReactionsForList(true)->fetch();
        }

        return $params;
    }

    public function prettyValue($raw, User $user)
    {
        $reactionIds = [];

        if (is_array($raw))
        {
            foreach (array_keys($raw) as $reactionId)
            {
                $reactionIds[] = $reactionId;
            }
        }

        return $reactionIds;
    }
}