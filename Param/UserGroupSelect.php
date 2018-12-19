<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Param;

use XF\Entity\User;
use XF\Http\Request;
use XF\Repository\UserGroup;

class UserGroupSelect extends AbstractParam
{
    protected $defaultOptions = [
        'multiselect' => true
    ];

    public function verifyOptions(Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'multiselect' => 'bool'
        ]);

        return true;
    }

    protected function getDefaultTemplateParams($context)
    {
        $params = parent::getDefaultTemplateParams($context);

        if ($context === 'render')
        {
            /** @var UserGroup $userGroupRepo */
            $userGroupRepo = $this->app->repository('XF:UserGroup');
            $params['userGroups'] = $userGroupRepo->getUserGroupTitlePairs();
        }

        return $params;
    }

    public function prettyValue($raw, User $user)
    {
        if (is_array($raw))
        {
            $userGroupIds = [];

            foreach ($raw as $forumId)
            {
                if (is_numeric($forumId))
                {
                    $userGroupIds[] = $forumId + 0;
                }
                else
                {
                    $userGroupIds = 'all';
                    break;
                }
            }

            return $userGroupIds;
        }
        else
        {
            return is_numeric($raw) ? $raw + 0 : 'all';
        }
    }
}