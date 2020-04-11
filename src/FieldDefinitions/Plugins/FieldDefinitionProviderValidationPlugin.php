<?php


namespace JqlBundle\FieldDefinitions\Plugins;

use Doctrine\Common\Annotations\Reader;
use JqlBundle\FieldDefinitions\IFieldsDefinitionProviderPlugin;
use Symfony\Component\Validator\Constraint;

class FieldDefinitionProviderValidationPlugin implements IFieldsDefinitionProviderPlugin
{

	private $reader;


	/**
	 * FieldDefinitionProviderValidationPlugin constructor.
	 *
	 * @param $reader
	 */
	public function __construct(Reader $reader)
	{
		$this->reader = $reader;
	}


	public function getNamespace(): string
	{
		return "validation";
	}


	public function getClassMetadata(\ReflectionClass $class): ?array
	{
		return null;
	}


	public function getFieldMetadata(\ReflectionProperty $property): ?array
	{
		$validators = [];
		$annotations = $this->reader->getPropertyAnnotations($property);
		foreach ($annotations as $annotation) {
			if (is_subclass_of($annotation, Constraint::class)) {
				$reflectionClass = new \ReflectionClass($annotation);
				$validators[] = $reflectionClass->getShortName();
			}
		}

		return $validators;
	}

}
