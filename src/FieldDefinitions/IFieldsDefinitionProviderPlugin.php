<?php

namespace JqlBundle\FieldDefinitions;

/**
 * Interface IFieldsDefinitionProviderPlugin
 *
 * @package AppBundle\Services\Acl
 */
interface IFieldsDefinitionProviderPlugin
{
	public function getNamespace(): string;


	public function getClassMetadata(\ReflectionClass $class): ?array;


	public function getFieldMetadata(\ReflectionProperty $property): ?array;

}
