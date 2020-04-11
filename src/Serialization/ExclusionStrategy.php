<?php


namespace JqlBundle\Serialization;


use Doctrine\ORM\PersistentCollection;
use JqlBundle\Exceptions\FieldDefinitionException;
use JqlBundle\FieldDefinitions\FieldDefinitionsProvider;
use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

class ExclusionStrategy implements ExclusionStrategyInterface
{
	private $fieldDefinitionsProvider;

	private $fieldList;

	private $rootEntityClassName;


	/**
	 * ExclusionStrategy constructor.
	 *
	 * @param FieldDefinitionsProvider $fieldDefinitionsProvider
	 * @param array $fieldList
	 * @param string $rootEntityClassName
	 */
	public function __construct(FieldDefinitionsProvider $fieldDefinitionsProvider, array $fieldList = [], string $rootEntityClassName)
	{
		$this->fieldDefinitionsProvider = $fieldDefinitionsProvider;
		$this->fieldList = $fieldList;
		$this->rootEntityClassName = $rootEntityClassName;
	}


	public function shouldSkipClass(ClassMetadata $metadata, Context $context): bool
	{
		if ($metadata->name === PersistentCollection::class) {
			return false;
		}
		//allow class if its property has a field definition, even of the class it self has not
		$propertyPath = array_merge($context->getCurrentPath());
		if (in_array(implode('.', $propertyPath), $this->fieldList)) {
			return false;
		};
		try {
			$this->fieldDefinitionsProvider->getClassDefinition($metadata->name);
		} catch (FieldDefinitionException $e) {
			return true;
		}

		return false;
	}


	public function shouldSkipProperty(PropertyMetadata $property, Context $context): bool
	{
		if ($property->name === 'id') {
			return false;
		}

		$propertyPath = array_merge($context->getCurrentPath(), [$property->name]);

		try {
			$currentFieldDefinition = $this->fieldDefinitionsProvider->getFieldDefinition($this->rootEntityClassName, implode('.', $propertyPath), true);
		} catch (FieldDefinitionException $e) {
			if (count($propertyPath) > 1) {
				try {
					$parentPropertyPath = array_slice($propertyPath, 0, -1);
					$prevFieldDefinition = $this->fieldDefinitionsProvider->getFieldDefinition($this->rootEntityClassName, implode('.', $parentPropertyPath), true);
				} catch (FieldDefinitionException $e) {
					return true;
				}
				//there is no field definition for class check property path
				if (in_array(implode('.', $parentPropertyPath), $this->fieldList)) {
					return false;
				};
			}
		}

		return !in_array(implode('.', $propertyPath), $this->fieldList);
	}

}
