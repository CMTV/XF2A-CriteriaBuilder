<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Entity;

use XF\Mvc\Entity\Entity;

abstract class TransferableEntity extends Entity
{
    public abstract function transferArrayClass(): string;
}