<?php
/**
 * Criteria Builder xF2 addon by CMTV
 * You can do whatever you want with this code
 * Enjoy!
 */

namespace CMTV\CriteriaBuilder\TransferArray;

use CMTV\CriteriaBuilder\Constants as C;
use CMTV\CriteriaBuilder\Entity\TransferableEntity;
use CMTV\CriteriaBuilder\Util\Arr;
use XF\Mvc\Entity\Entity;
use XF\Util\Xml;

abstract class TransferArray implements \ArrayAccess
{
    // Transfer types

    const ATTRIBUTE = 1;
    const TAG       = 2;
    const CDATA     = 3;

    // Value types

    const STRING = 'string';
    const INT    = 'integer';
    const BOOL   = 'boolean';

    protected $_structure = [];
    protected $_values = [];

    public abstract static function getTransferStructure(array $structure): array;
    public abstract function getNodeName(): string;
    public abstract function getEntityShortName(): string;

    public function __construct(array $structure, array $values)
    {
        $this->_structure = $structure;
        $this->_values = $values;
    }

    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    //

    public function getAttributes(): array
    {
        return $this->getValuesOfType(self::ATTRIBUTE);
    }

    public function getTags(): array
    {
        return $this->getValuesOfType(self::TAG);
    }

    public function getCData(): array
    {
        return $this->getValuesOfType(self::CDATA);
    }

    protected function getValuesOfType(int $type)
    {
        if (!self::isValidType($type))
        {
            throw new \LogicException('Unknown "' . $type . '" structure item type.');
        }

        $keysValues = [];

        foreach ($this->_structure as $key => $structureParams)
        {
            if ($structureParams['type'] !== $type)
            {
                continue;
            }

            $keysValues[$key] = $this->_values[$key];
        }

        return $keysValues;
    }

    // Instantiate methods

    public static function getFromEntity(TransferableEntity $entity): TransferArray
    {
        $class = self::getClass($entity->transferArrayClass());
        $structure = self::getStructure($class);
        $values = [];

        foreach ($structure as $valueId => $structureParams)
        {
            if (isset($structureParams['column']))
            {
                $values[$valueId] = $entity->getValue($structureParams['column']);
            }
            else if (isset($structureParams['getter']))
            {
                $values[$valueId] = $structureParams['getter']($entity);
            }
            else
            {
                $values[$valueId] = $entity->getValue($valueId);
            }
        }

        return new $class($structure, $values);
    }

    public static function getFromXml(string $class, \SimpleXMLElement $xml): TransferArray
    {
        $class = self::getClass($class);
        $structure = self::getStructure($class);
        $values = [];

        foreach ($structure as $valueId => $structureParams)
        {
            $value = ($structureParams['type'] === self::ATTRIBUTE ? $xml->attributes() : $xml->children())->$valueId;
            settype($value, $structureParams['valueType']);
            $values[$valueId] = $value;
        }

        return new $class($structure, $values);
    }

    public static function getFromArray(string $class, array $array): TransferArray
    {
        $class = self::getClass($class);
        $structure = self::getStructure($class);
        $values = [];

        foreach ($structure as $valueId => $structureParams)
        {
            $value = $array[$valueId];
            settype($value, $structureParams['valueType']);
            $values[$valueId] = $value;
        }

        return new $class($structure, $values);
    }

    //

    public function toEntity(): Entity
    {
        $entity = \XF::em()->create($this->getEntityShortName());

        foreach ($this->_structure as $valueId => $structureParams)
        {
            if (isset($structureParams['column']))
            {
                $entity->set($structureParams['column'], $this->_values[$valueId]);
            }
            else if (isset($structureParams['setter']))
            {
                $structureParams['setter']($entity, $this);
            }
            else
            {
                $entity->set($valueId, $this->_values[$valueId]);
            }
        }

        return $entity;
    }

    public function toXml(\DOMDocument $document): \DOMElement
    {
        $node = $document->createElement($this->getNodeName());

        foreach ($this->getAttributes() as $attrName => $attrValue)
        {
            $node->setAttribute($attrName, $attrValue);
        }

        // todo: Handle TAG type

        foreach ($this->getCData() as $tagName => $content)
        {
            if ($content)
            {
                $CDataSection = Xml::createDomCdataSection($document, $content);
                $node->appendChild(Xml::createDomElement($document, $tagName, $CDataSection));
            }
        }

        return $node;
    }

    public function renderInput(string $container, array $except = [])
    {
        return \XF::app()->templater()->renderTemplate(
            'admin:' . C::TEMPLATE_PREFIX('transfer_array_to_input'),
            [
                'transferArray' => $this,
                'container' => $container,
                'except' => $except
            ]
        );
    }

    //

    private static function getClass(string $class): string
    {
        /** @var TransferArray $class */
        $class = \XF::stringToClass($class, '%s\TransferArray\%s');

        if (!class_exists($class))
        {
            throw new \LogicException(
                $class . " class does not exist."
            );
        }

        return \XF::extendClass($class);
    }

    private static function getStructure(string $class): array
    {
        $structure = $class::getTransferStructure([]);
        self::verifyStructure($structure);
        return $structure;
    }

    //

    public static function diff(TransferArray $new, TransferArray $old): TransferArray
    {
        $new->_diffKeys = Arr::getDiffKeys($new->_values, $old->_values);
        $new->_old_values = \XF\Util\Arr::arrayFilterKeys($old->_values, $new->_diffKeys);

        return $new;
    }

    //

    private static function verifyStructure(array &$structure)
    {
        foreach ($structure as $valueId => $structureParams)
        {
            if (!isset($structureParams['type']))
            {
                throw new \LogicException('Structure item "' . $valueId . '" does not have a "type" property.');
            }

            $type = $structureParams['type'];

            if (!self::isValidType($type))
            {
                throw new \InvalidArgumentException('Unknown type "' . $type . '" of "' . $valueId . '" structure item.');
            }

            if (!isset($structureParams['valueType']))
            {
                $structure[$valueId]['valueType'] = self::STRING;
            }
            else
            {
                $valueType = $structureParams['valueType'];

                if (!self::isValidValueType($valueType))
                {
                    throw new \InvalidArgumentException('Unknown value type "' . $valueType . '" of "' . $valueId . '" structure item.');
                }
            }
        }
    }

    private static function isValidType(int $type): bool
    {
        switch ($type)
        {
            case self::ATTRIBUTE:
            case self::TAG:
            case self::CDATA:
                return true;
        }

        return false;
    }

    private static function isValidValueType(string $type): bool
    {
        switch ($type)
        {
            case self::STRING:
            case self::INT:
            case self::BOOL:
                return true;
        }

        return false;
    }

    private function verifyValueKey(string $key)
    {
        if (!$this->offsetExists($key))
        {
            throw new \InvalidArgumentException('Unknown value "' . $key . '".');
        }
    }

    /* ============================================================================================================== */
    /* ARRAY ACCESS */
    /* ============================================================================================================== */

    public function offsetExists($key)
    {
        return array_key_exists($key, $this->_values);
    }

    public function offsetGet($key)
    {
        $this->verifyValueKey($key);

        return $this->_values[$key];
    }

    public function offsetSet($key, $value)
    {
        $this->verifyValueKey($key);

        $this->_values[$key] = $value;
    }

    public function offsetUnset($key)
    {
        throw new \LogicException("Deleting TransferArray values is not allowed!");
    }
}