<?php

namespace GrownApps\JqlBundle\FieldDefinitions;

use AppBundle\Annotation\TrackedProperty;
use AppBundle\Entity\Codelist\CodeListEntity;
use AppBundle\Utils\HasDefaultOptionCodelist;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use GrownApps\JqlBundle\Exceptions\FieldDefinitionException;
use GrownApps\JqlBundle\FieldDefinitions\Annotation\FieldDefinition;
use GrownApps\JqlBundle\FieldDefinitions\Annotation\FieldDefinitionClass;
use hanneskod\classtools\Iterator\ClassIterator;
use Symfony\Component\Finder\Finder;

/**
 * Class FieldsDefinitionProvider
 *
 * @package AppBundle\Services
 */
class FieldDefinitionsProvider
{
	const CACHE_KEY_PREFIX = 'app.definitions.cache.';

	const ASSOC_ONE_TO_ONE = 'one-to-one';
	const ASSOC_ONE_TO_MANY = 'one-to-many';
	const ASSOC_MANY_TO_ONE = 'many-to-one';
	const ASSOC_MANY_TO_MANY = 'many-to-many';


	/** @var array */
	private $definitions;

	/** @var Reader */
	private $annotationReader;

	/** @var array */
	private $plugins;

	/**
	 * @var ICacheProvider
	 */
	private $cacheProvider;

	/**
	 * @var EntityManagerInterface
	 */
	private $entityManager;

	private $entitiesDir;


	/**
	 * FieldsDefinitionProvider constructor.
	 *
	 * @param Reader $annotationReader
	 * @param ICacheProvider $cacheProvider
	 * @param EntityManagerInterface $entityManager
	 * @param $entitiesDir
	 * @param array $plugins
	 */
	public function __construct(Reader $annotationReader, ICacheProvider $cacheProvider, EntityManagerInterface $entityManager, $entitiesDir, $plugins = [])
	{
		$this->annotationReader = $annotationReader;
		$this->plugins = $plugins;
		$this->cacheProvider = $cacheProvider;
		$this->entityManager = $entityManager;
		$this->entitiesDir = $entitiesDir;
	}


	public function getFieldsDefinitions()
	{
		if ($this->cacheProvider->isValid()) {
			return $this->cacheProvider->getFieldsDefinitions();
		}

		$em = $this->entityManager;

		$finder = new Finder();
		//TODO following should be more configurable...
		$classIterator = new ClassIterator($finder->in($this->entitiesDir));
		$classIterator->enableAutoloading();

		/** @var \ReflectionClass $class */
		foreach ($classIterator as $class) {
			/** @var FieldDefinitionClass $definitionReady */
			$definitionReady = $this->annotationReader->getClassAnnotation($class, FieldDefinitionClass::class);

			if (!$definitionReady) {
				continue;
			}

			$metaData = $em->getClassMetadata($class->getName());

			$properties = $class->getProperties();
			$shortName = lcfirst($class->getShortName());

			$this->definitions[$shortName]['className'] = $class->getName();
			$this->definitions[$shortName]['name'] = $definitionReady->getName() ?? $class->getName();
			$this->definitions[$shortName]['toStringTemplate'] = $definitionReady->getToStringTemplate() ?? '<%=id=%>';

			foreach ($this->plugins as $plugin) {
				if ($plugin instanceof IFieldsDefinitionProviderPlugin) {
					$pluginMetadata = $plugin->getClassMetadata($class);
					if ($pluginMetadata) {
						$this->definitions[$shortName][$plugin->getNamespace()] = $pluginMetadata;
					}
				}
			}

			foreach ($properties as $property) {
				/** @var \GrownApps\JqlBundle\FieldDefinitions\Annotation\FieldDefinition $fieldDefinition */
				$fieldDefinition = $this->annotationReader->getPropertyAnnotation($property, FieldDefinition::class);

				if (!$fieldDefinition) {
					continue;
				}

				$constraints = [];
				foreach ($fieldDefinition->getConstraints() as $constraint){
					$constraints[$constraint->getProperty()] = $constraint->getValue();
				}

				$this->definitions[$shortName]['fields'][$property->getName()] = [
					'id' => "{$class->getName()}::{$property->getName()}", //perhaps not needed
					'title' => $fieldDefinition->getTitle(),
					'section' => $fieldDefinition->getSection(),
					'description' => $fieldDefinition->getDescription(),
					'skipProcessing' => $fieldDefinition->isSkippedProcessing(),
					'toString' => $fieldDefinition->getToString(),
					'constraints' => $constraints,
					'dependencies' => [],
					'hideInUI' => $fieldDefinition->isHideInUI()
				];

				foreach ($this->plugins as $plugin) {
					if ($plugin instanceof IFieldsDefinitionProviderPlugin) {
						$pluginMetadata = $plugin->getFieldMetadata($property);
						if ($pluginMetadata) {
							$this->definitions[$shortName]['fields'][$property->getName()][$plugin->getNamespace()] = $pluginMetadata;
						}
					}
				}

				//TODO convert to plugin
				$tracked = $this->annotationReader->getPropertyAnnotation($property, TrackedProperty::class);
				if ($tracked) {
					$this->definitions[$shortName]['fields'][$property->getName()]['tracked'] = true;
				}

				try {
					$fieldMapping = $metaData->getFieldMapping($property->getName());
					$this->definitions[$shortName]['fields'][$property->getName()]['type'] = $fieldMapping['type'];
					$this->definitions[$shortName]['fields'][$property->getName()]['ormType'] = $fieldMapping['type'];
				} catch (MappingException $e) {
					// it's not regular field - can be association

					try {
						$associationMapping = $metaData->getAssociationMapping($property->getName());
						$this->definitions[$shortName]['fields'][$property->getName()]['ormType'] = 'association';
						$this->definitions[$shortName]['fields'][$property->getName()]['ormAssociation'] = [];
						$this->definitions[$shortName]['fields'][$property->getName()]['ormAssociation']['type'] = $this->associationToText($associationMapping['type']);
						$this->definitions[$shortName]['fields'][$property->getName()]['ormAssociation']['targetEntity'] = $this->resolveTargetEntity($associationMapping['targetEntity']);
						$this->definitions[$shortName]['fields'][$property->getName()]['ormAssociation']['targetEntityClassName'] = $associationMapping['targetEntity'];
						$this->definitions[$shortName]['fields'][$property->getName()]['ormAssociation']['mappedBy'] = $this->associationToText($associationMapping['type']) != self::ASSOC_MANY_TO_MANY ? $associationMapping['mappedBy'] : $associationMapping['mappedBy'] ?? $associationMapping['inversedBy'];
						if (is_subclass_of($associationMapping['targetEntity'], CodeListEntity::class)) {
							$this->definitions[$shortName]['fields'][$property->getName()]['type'] = 'codelist';
							$this->definitions[$shortName]['fields'][$property->getName()]['hasDefaultOption'] = in_array(HasDefaultOptionCodelist::class, class_implements($associationMapping['targetEntity']));
						} else {
							$this->definitions[$shortName]['fields'][$property->getName()]['type'] = $this->resolveAssociationType($associationMapping['type']);
						}

						$this->definitions[$shortName]['fields'][$property->getName()]['targetEntity'] = $this->resolveTargetEntity($associationMapping['targetEntity']);
					} catch (MappingException $e) {
						// @todo throw some exception
					}
				}
			}
			ksort($this->definitions[$shortName]['fields']);
		}

		$this->cacheProvider->setFieldDefinitions($this->definitions);
		ksort($this->definitions);

		return $this->definitions;
	}


	public function getFieldDefinition(string $className, string $fieldPath, $shortClassName = false)
	{
		$classDefinition = $this->getClassDefinition($className, $shortClassName);

		$path = explode(".", $fieldPath);
		if (count($path) > 1) {
			$nextInPath = array_shift($path);
			if (!array_key_exists($nextInPath, $classDefinition['fields'])) {
				throw FieldDefinitionException::fieldNotFound($fieldPath);
			}
			$fieldDefinition = $classDefinition['fields'][$nextInPath];
			if (!array_key_exists('ormAssociation', $fieldDefinition)) {
				throw FieldDefinitionException::fieldIsNotAssociation($className, $nextInPath);
			}
			$associationClass = $fieldDefinition['ormAssociation']['targetEntityClassName'];

			return $this->getFieldDefinition($associationClass, implode('.', $path));
		}

		if (!array_key_exists($fieldPath, $classDefinition['fields'])) {
			throw FieldDefinitionException::fieldNotFound($fieldPath);
		}

		return $classDefinition['fields'][$fieldPath];
	}


	public function getClassDefinition(string $className, $shortName = false)
	{
		$definitions = $this->getFieldsDefinitions();
		if ($shortName) {
			if (array_key_exists($className, $definitions)) {
				return $definitions[$className];
			} else {
				throw FieldDefinitionException::entityNotFound($className);
			}
		}

		foreach ($definitions as $definition) {
			if ($definition['className'] === $className) {
				return $definition;
			}
		}
		throw FieldDefinitionException::entityNotFound($className);
	}

	public function getAnnotationReader() {
		return $this->annotationReader;
	}


	private function resolveAssociationType($associationType)
	{
		switch ($associationType) {
			case ClassMetadataInfo::ONE_TO_ONE:
			case ClassMetadataInfo::MANY_TO_ONE:
				$type = 'reference';
				break;
			case ClassMetadataInfo::ONE_TO_MANY:
			case ClassMetadataInfo::MANY_TO_MANY:
				$type = 'collection';
				break;
			default:
				$type = 'undefined';
		}

		return $type;
	}


	private function associationToText($associationType)
	{
		switch ($associationType) {
			case ClassMetadataInfo::ONE_TO_ONE:
				return self::ASSOC_ONE_TO_ONE;
			case ClassMetadataInfo::MANY_TO_ONE:
				return self::ASSOC_MANY_TO_ONE;
			case ClassMetadataInfo::ONE_TO_MANY:
				return self::ASSOC_ONE_TO_MANY;
			case ClassMetadataInfo::MANY_TO_MANY:
				return self::ASSOC_MANY_TO_MANY;
		}
	}


	private function resolveTargetEntity($entityName)
	{
		return lcfirst(substr(strrchr($entityName, "\\"), 1));
	}
}
