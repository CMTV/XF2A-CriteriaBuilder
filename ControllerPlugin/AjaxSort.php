<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\ControllerPlugin;

use XF\ControllerPlugin\AbstractPlugin;
use XF\Mvc\Entity\Entity;

class AjaxSort extends AbstractPlugin
{
    public function actionAjaxSort($movedId, $beforeId, $entities, bool $isMovedToLast, int $step = 10, int $start = 10)
    {
        $this->assertPostOnly();

        $movedEntity = $entities[$movedId];

        unset($entities[$movedId]);

        if ($isMovedToLast)
        {
            $entities[$movedId] = $movedEntity;
        }

        /**
         * @param Entity $entity
         * @param int $currentOrder
         */
        $_setOrder = function ($entity, &$currentOrder) use ($step)
        {
            $entity->display_order = $currentOrder;
            $entity->saveIfChanged();

            $currentOrder += $step;
        };

        $currentOrder = $start;
        foreach ($entities as $entityId => $entity)
        {
            if ($entityId === $beforeId)
            {
                $_setOrder($movedEntity, $currentOrder);
            }

            $_setOrder($entity, $currentOrder);
        }

        return $this->view();
    }
}