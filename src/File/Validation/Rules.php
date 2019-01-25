<?php

namespace WsdlToPhp\PackageGenerator\File\Validation;

use WsdlToPhp\PhpGenerator\Element\PhpMethod;
use WsdlToPhp\PackageGenerator\File\AbstractModelFile;
use WsdlToPhp\PackageGenerator\Model\AbstractModel;
use WsdlToPhp\PackageGenerator\Model\Struct;
use WsdlToPhp\PackageGenerator\Model\StructAttribute;

class Rules
{

    /**
     * @var StructAttribute
     */
    protected $attribute;

    /**
     * @var AbstractModelFile
     */
    protected $file;

    /**
     * @var PhpMethod
     */
    protected $method;

    /**
     * @param AbstractModelFile $file
     * @param PhpMethod $method
     * @param StructAttribute $attribute
     */
    public function __construct(AbstractModelFile $file, PhpMethod $method, StructAttribute $attribute)
    {
        $this
            ->setFile($file)
            ->setMethod($method)
            ->setAttribute($attribute);
    }

    /**
     * @param string $parameterName
     * @param bool $itemType
     */
    public function applyRules($parameterName, $itemType = false)
    {
        if ($this->attribute->isArray() && !$itemType) {
            $this->getArrayRule()->applyRule($parameterName, null, $itemType);
        } elseif ($this->attribute->isList() && !$itemType) {
            $this->getListRule()->applyRule($parameterName, null, $itemType);
        } elseif ($this->getFile()->getRestrictionFromStructAttribute($this->attribute)) {
            $this->getEnumerationRule()->applyRule($parameterName, null, $itemType);
        } elseif ($itemType) {
            $this->getItemTypeRule()->applyRule($parameterName, null, $itemType);
        } elseif (($rule = $this->getRule($this->getFile()->getStructAttributeTypeAsPhpType($this->attribute))) instanceof AbstractRule) {
            $rule->applyRule($parameterName, null, $itemType);
        }
        $this->applyRulesFromModel($this->attribute, $parameterName, $itemType);
    }

    /**
     * This method is called when an attribute has a union meta which means the attribute is of several types.
     * In this case, the types are currently only of type string (normally) so we add the rules according to each type
     * @param string $parameterName
     * @param bool $itemType
     * @param string[] $unionTypes
     */
    protected function applyUnionRules($parameterName, $itemType, array $unionTypes)
    {
        foreach ($unionTypes as $type) {
            $struct = $this->getAttribute()->getGenerator()->getStructByName($type);
            if ($struct instanceof Struct) {
                $this->applyRulesFromModel($struct, $parameterName, $itemType);
            }
        }
    }

    /**
     * Generic method to apply rules from current model
     * @param AbstractModel $model
     * @param string $parameterName
     * @param bool $itemType
     */
    protected function applyRulesFromModel(AbstractModel $model, $parameterName, $itemType = false)
    {
        foreach ($model->getMeta() as $metaName => $metaValue) {
            $rule = $this->getRule($metaName);
            if ($rule instanceof AbstractRule) {
                $rule->applyRule($parameterName, $metaValue, $itemType);
            } elseif ($metaName === 'union' && is_array($metaValue) && count($metaValue) > 0) {
                $this->applyUnionRules($parameterName, $itemType, $metaValue);
            } elseif ($metaName === 'choiceNames' && is_array($metaValue) && count($metaValue) > 0) {
                $this->getChoiceRule()->applyRule($parameterName, $metaValue, $itemType);
            }
        }
    }

    /**
     * @param string $metaName
     * @return AbstractRule|null
     */
    protected function getRule($metaName)
    {
        if (is_string($metaName)) {
            $className = sprintf('%s\%sRule', __NAMESPACE__, ucfirst($metaName));
            if (class_exists($className)) {
                return new $className($this);
            }
        }
        return null;
    }

    /**
     * @return ArrayRule
     */
    public function getArrayRule()
    {
        return $this->getRule('array');
    }

    /**
     * @return EnumerationRule
     */
    public function getEnumerationRule()
    {
        return $this->getRule('enumeration');
    }

    /**
     * @return ItemTypeRule
     */
    public function getItemTypeRule()
    {
        return $this->getRule('itemType');
    }

    /**
     * @return ChoiceRule
     */
    public function getChoiceRule()
    {
        return $this->getRule('choice');
    }

    /**
     * @return ListRule
     */
    public function getListRule()
    {
        return $this->getRule('list');
    }
    /**
     * @return StructAttribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param StructAttribute $attribute
     * @return Rules
     */
    public function setAttribute(StructAttribute $attribute)
    {
        $this->attribute = $attribute;
        return $this;
    }

    /**
     * @return AbstractModelFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param AbstractModelFile $file
     * @return Rules
     */
    public function setFile(AbstractModelFile $file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return PhpMethod
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param PhpMethod $method
     * @return Rules
     */
    public function setMethod(PhpMethod $method)
    {
        $this->method = $method;
        return $this;
    }
}
