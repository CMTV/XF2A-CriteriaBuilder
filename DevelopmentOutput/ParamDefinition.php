<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\DevelopmentOutput;

use XF\DevelopmentOutput\AbstractHandler;
use XF\Mvc\Entity\Entity;
use XF\Util\Json;

class ParamDefinition extends AbstractHandler
{
    protected function getTypeDir()
    {
        return 'param_definitions';
    }

    public function export(Entity $paramDefinition)
    {
        if (!$this->isRelevant($paramDefinition))
        {
            return true;
        }

        $fileName = $this->getFileName($paramDefinition);

        $keys = [
            'definition_class',
            'icon',
            'display_order'
        ];

        $json = $this->pullEntityKeys($paramDefinition, $keys);

        return $this->developmentOutput->writeFile($this->getTypeDir(), $paramDefinition->addon_id, $fileName, Json::jsonEncodePretty($json));
    }

    public function import($name, $addOnId, $contents, array $metadata, array $options = [])
    {
        $json = json_decode($contents, true);

        $paramDefinition = $this->getEntityForImport($name, $addOnId, $json, $options);
        $paramDefinition->bulkSet($json);
        $paramDefinition->definition_id = $name;
        $paramDefinition->addon_id = $addOnId;
        $paramDefinition->save();

        return $paramDefinition;
    }
}