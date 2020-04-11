<?php


namespace JqlBundle\FieldDefinitions\Plugins;

use AppBundle\Entity\Codelist\CodeListEntity;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use JqlBundle\FieldDefinitions\Annotation\ConditionalCodelist;
use JqlBundle\FieldDefinitions\IFieldsDefinitionProviderPlugin;

class FieldDefinitionProviderCodelistPlugin implements IFieldsDefinitionProviderPlugin
{

	/** @var EntityManagerInterface  */
	private $entityManager;

	/** @var Reader */
	private $annotationReader;


	public function __construct(EntityManagerInterface $entityManager, Reader $annotationReader)
	{
		$this->entityManager = $entityManager;
		$this->annotationReader = $annotationReader;
	}


	public function getNamespace(): string
	{
		return "codelist";
	}


	public function getClassMetadata(\ReflectionClass $class): ?array
	{
		return null;
	}


	public function getFieldMetadata(\ReflectionProperty $property): ?array
	{

		$class = $property->getDeclaringClass();
		$doctrineMetadata = $this->entityManager->getClassMetadata($class->getName());

		try {
			$associationMapping = $doctrineMetadata->getAssociationMapping($property->getName());
			if (is_subclass_of($associationMapping['targetEntity'], CodeListEntity::class)) {
				return ['isCodelist' => true];
			}

			$codelistAnnotation = $this->annotationReader->getPropertyAnnotation($property, ConditionalCodelist::class);
			if ($codelistAnnotation) {
				return ['isConditionalCodelist' => true];
			}
		} catch (MappingException $e) {
			//nothing to be done
		}

		return null;
	}

}
