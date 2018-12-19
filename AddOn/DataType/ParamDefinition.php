<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\AddOn\DataType;

use CMTV\CriteriaBuilder\Constants as C;
use XF\AddOn\DataType\AbstractDataType;
use XF\Entity\Option;

class ParamDefinition extends AbstractDataType
{
    public function getShortName()
    {
        return C::ADDON_PREFIX('ParamDefinition');
    }

    public function getContainerTag()
    {
        return 'param_definitions';
    }

    public function getChildTag()
    {
        return 'param_definition';
    }

    public function exportAddOnData($addOnId, \DOMElement $container)
    {
        $entries = $this->finder()
            ->where('addon_id', $addOnId)
            ->order('definition_id')->fetch();

        $doc = $container->ownerDocument;

        foreach ($entries as $entry)
        {
            $node = $doc->createElement($this->getChildTag());
            $this->exportMappedAttributes($node, $entry);
            $container->appendChild($node);
        }

        return $entries->count() ? true : false;
    }

    public function importAddOnData($addOnId, \SimpleXMLElement $container, $start = 0, $maxRunTime = 0)
    {
        $startTime = microtime(true);

        $entries = $this->getEntries($container, $start);
        if (!$entries)
        {
            return false;
        }

        $ids = $this->pluckXmlAttribute($entries, 'definition_id');
        $existing = $this->findByIds($ids);

        $i = 0;
        $last = 0;
        foreach ($entries AS $entry)
        {
            $id = $ids[$i++];

            if ($i <= $start)
            {
                continue;
            }

            /** @var \XF\Entity\Option $entity */
            $entity = isset($existing[$id]) ? $existing[$id] : $this->create();

            $entity->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', false);
            $this->importMappedAttributes($entry, $entity);

            $entity->addon_id = $addOnId;

            $entity->save(true, false);

            if ($this->resume($maxRunTime, $startTime))
            {
                $last = $i;
                break;
            }
        }
        return ($last ?: false);
    }

    public function deleteOrphanedAddOnData($addOnId, \SimpleXMLElement $container)
    {
        $this->deleteOrphanedSimple($addOnId, $container, 'definition_id');
    }

    protected function getMappedAttributes()
    {
        return [
            'definition_id',
            'definition_class',
            'icon',
            'display_order'
        ];
    }
}