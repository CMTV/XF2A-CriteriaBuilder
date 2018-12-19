<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\Job;

use XF\Job\AbstractJob;

abstract class AbstractRebuildOffsetJob extends AbstractJob
{
    protected $rebuildDefaultData = [
        'steps' => 0,
        'start' => 0,
        'batch' => 100
    ];

    abstract protected function getPrimaryKey(): string;
    abstract protected function getTable(): string;
    abstract protected function rebuildById($id);
    abstract protected function getStatusType();

    protected function setupData(array $data)
    {
        $this->defaultData = array_merge($this->rebuildDefaultData, $this->defaultData);

        return parent::setupData($data);
    }

    public function run($maxRunTime)
    {
        $startTime = microtime(true);

        $this->data['steps']++;

        $db = $this->app->db();

        $table = $this->getTable();
        $primaryKey = $this->getPrimaryKey();

        $ids = $db->fetchAllColumn(
            $db->limit("SELECT `{$primaryKey}` FROM `{$table}`", $this->data['batch'], $this->data['start'])
        );

        if (!$ids)
        {
            return $this->complete();
        }

        $done = 0;

        foreach ($ids as $id)
        {
            if (microtime(true) - $startTime >= $maxRunTime)
            {
                break;
            }

            $this->data['start']++;

            $this->rebuildById($id);

            $done++;
        }

        $this->data['batch'] = $this->calculateOptimalBatch($this->data['batch'], $done, $startTime, $maxRunTime, 1000);

        return $this->resume();
    }

    public function getStatusMessage()
    {
        $actionPhrase = \XF::phrase('rebuilding');
        $typePhrase = $this->getStatusType();
        return sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, $this->data['start']);
    }

    public function canCancel()
    {
        return true;
    }

    public function canTriggerByChoice()
    {
        return true;
    }
}