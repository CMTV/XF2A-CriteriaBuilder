<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Param;

use XF\Entity\User;
use XF\Http\Request;
use XF\Repository\Node;

class ForumSelect extends AbstractParam
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

    public function prettyValue($raw, User $user)
    {
        if (is_array($raw))
        {
            $forumIds = [];

            foreach ($raw as $forumId)
            {
                if (is_numeric($forumId))
                {
                    $forumIds[] = $forumId + 0;
                }
                else
                {
                    $forumIds = 'all';
                    break;
                }
            }

            return $forumIds;
        }
        else
        {
            return is_numeric($raw) ? $raw + 0 : 'all';
        }
    }

    protected function getDefaultTemplateParams($context)
    {
        $params = parent::getDefaultTemplateParams($context);

        if ($context === 'render')
        {
            /** @var Node $nodeRepo */
            $nodeRepo = $this->app->repository('XF:Node');
            $params['nodeTree'] = $nodeRepo->createNodeTree($nodeRepo->getFullNodeList());
        }

        return $params;
    }
}