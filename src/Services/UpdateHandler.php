<?php

namespace JqlBundle\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use JqlBundle\Exceptions\EntityNotFoundException;
use JqlBundle\Exceptions\FieldDefinitionException;
use JqlBundle\Exceptions\InvalidValueException;
use JqlBundle\FieldDefinitions\FieldDefinitionsProvider;
use JqlBundle\Hooks\Events\PostDeleteEvent;
use JqlBundle\Hooks\Events\PostFlushEvent;
use JqlBundle\Hooks\Events\PreFetchEvent;
use JqlBundle\Hooks\Events\PreFlushEvent;
use JqlBundle\Hooks\HooksDispatcher;
use JqlBundle\Hooks\JqlHookEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class UpdateHandler
{

	/** @var FieldDefinitionsProvider */
	private $fieldsDefinitionProvider;

	/** @var EntityManagerInterface */
	private $entityManager;

	/** @var EntityFactory */
	private $entityFactory;

	/** @var PropertyAccessor */
	private $accessor;

	/** @var HooksDispatcher */
	private $hooksDispatcher;


	/**
	 * UpdateHandler constructor.
	 *
	 * @param FieldDefinitionsProvider $fieldsDefinitionProvider
	 * @param EntityManagerInterface $entityManager
	 * @param EntityFactory $entityFactory
	 * @param HooksDispatcher $hooksDispatcher
	 */
	public function __construct(
		FieldDefinitionsProvider $fieldsDefinitionProvider,
		EntityManagerInterface $entityManager,
		EntityFactory $entityFactory,
		HooksDispatcher $hooksDispatcher
	) {
		$this->fieldsDefinitionProvider = $fieldsDefinitionProvider;
		$this->entityManager = $entityManager;
		$this->entityFactory = $entityFactory;
		$this->accessor = PropertyAccess::createPropertyAccessor();
		$this->hooksDispatcher = $hooksDispatcher;
	}


	/**
	 * @param $shortName
	 * @param $id
	 * @param $data
	 * @return object|null
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function findAndUpdateEntity($shortName, $id, $data)
	{
		$classFieldDefinitions = $this->fieldsDefinitionProvider->getClassDefinition($shortName, true);

		$this->hooksDispatcher->dispatch(JqlHookEvents::PRE_FETCH, new PreFetchEvent($shortName, (int) $id, $data));

		$entity = $this->entityManager->find($classFieldDefinitions['className'], $id);

		if (!$entity) {
			throw EntityNotFoundException::entityNotFound($shortName, $id);
		}

		$this->updateEntity($entity, $data, $classFieldDefinitions);

		$this->hooksDispatcher->dispatch(JqlHookEvents::PRE_FLUSH, new PreFlushEvent($entity, $data));

		$this->entityManager->flush();

		$this->hooksDispatcher->dispatch(JqlHookEvents::POST_FLUSH, new PostFlushEvent($entity, $data));

		return $entity;
	}


	public function createEntity($shortName, $data)
	{
		$classFieldDefinitions = $this->fieldsDefinitionProvider->getClassDefinition($shortName, true);

		$entity = $this->entityFactory->createEntity($classFieldDefinitions['className']);

		$this->entityManager->persist($entity);

		$this->updateEntity($entity, $data, $classFieldDefinitions);

		$this->hooksDispatcher->dispatch(JqlHookEvents::PRE_FLUSH, new PreFlushEvent($entity, $data));

		$this->entityManager->flush();

		$this->hooksDispatcher->dispatch(JqlHookEvents::POST_FLUSH, new PostFlushEvent($entity, $data));

		return $entity;
	}


	/**
	 * @param $shortName
	 * @param $id
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function deleteEntity($shortName, $id)
	{
		$classFieldDefinitions = $this->fieldsDefinitionProvider->getClassDefinition($shortName, true);
		$data = ['id' => $id];

		$this->hooksDispatcher->dispatch(JqlHookEvents::PRE_FETCH, new PreFetchEvent($shortName, (int) $id, $data));

		$entity = $this->entityManager->find($classFieldDefinitions['className'], $id);

		if (!$entity) {
			throw EntityNotFoundException::entityNotFound($shortName, $id);
		}

		$this->entityManager->remove($entity);

		$this->hooksDispatcher->dispatch(JqlHookEvents::PRE_FLUSH, new PreFlushEvent($entity, $data));

		$this->entityManager->flush();

		$this->hooksDispatcher->dispatch(JqlHookEvents::POST_FLUSH, new PostFlushEvent($entity, $data));
		$this->hooksDispatcher->dispatch(JqlHookEvents::POST_DELETE, new PostDeleteEvent($entity, $data));
	}


	/**
	 * @param $shortName
	 * @param $data
	 * @param $classFieldDefinitions
	 * @param $accessor
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	private function updateEntity($entity, $data, $classFieldDefinitions): void
	{
		foreach ($data as $property => $value) {
			if ($property === 'id' || $property === 'revision') {
				continue;
			}
			$fieldDefinition = $classFieldDefinitions['fields'][$property] ?? false;
			if (!$fieldDefinition) {
				throw FieldDefinitionException::fieldNotFound($property);
			}

			if ($value === null) {
				$this->accessor->setValue($entity, $property, $value);
				continue;
			}

			if ($fieldDefinition['ormType'] === 'association') {
				$associationMapping = $fieldDefinition['ormAssociation'];

				//allow setting entities outside of field definition scope??

				$associationMappingClassName = $associationMapping['targetEntityClassName'];
				switch ($associationMapping['type']) {
					case FieldDefinitionsProvider::ASSOC_MANY_TO_ONE:
					case FieldDefinitionsProvider::ASSOC_ONE_TO_ONE:
						if ($value === null) {
							$relatedEntity = null;
						} else {
							if (!is_array($value)) {
								$relatedEntity = $this->entityManager->find($associationMappingClassName, $value);
								if (!$relatedEntity) {
									throw EntityNotFoundException::entityNotFound($associationMappingClassName, $value);
								}
							} else {
								$relatedEntity = $this->obtainRelatedEntity($value, $associationMapping);
								try {
									$associationClassFieldDefinitions = $this->fieldsDefinitionProvider->getClassDefinition($associationMapping['targetEntity'], true);
								} catch (FieldDefinitionException $e) {
									$associationClassFieldDefinitions = false;
								}
								//if field is part of the field definition scope, updated nested values
								if ($associationClassFieldDefinitions) {
									$this->updateEntity($relatedEntity, $value, $associationClassFieldDefinitions);
								}
							}
						}
						$this->accessor->setValue($entity, $property, $relatedEntity);
						break;
					case FieldDefinitionsProvider::ASSOC_ONE_TO_MANY:
					case FieldDefinitionsProvider::ASSOC_MANY_TO_MANY:
						$entity = $this->updateCollection($entity, $property, $value, $collection = [], $associationMappingClassName, $associationMapping);
						break;
				}
			} else {
				if ($fieldDefinition['ormType'] === 'date') {
					$value = \DateTime::createFromFormat('Y-m-d', $value);
				}
				if ($fieldDefinition['ormType'] === 'datetime') {
					$value = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
				}
				if ($fieldDefinition['ormType'] === 'datetime_immutable') {
					$value = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
				}

				$this->accessor->setValue($entity, $property, $value);
			}
		}
	}


	private function isMulti($a)
	{
		foreach ($a as $v) {
			if (is_array($v)) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param $relatedEntityData
	 * @param $associationMappingClassName
	 * @param $associationClassFieldDefinitions
	 * @return mixed|null|object
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	private function obtainRelatedEntity($relatedEntityData, $associationMapping)
	{
		$associationMappingClassName = $associationMapping['targetEntityClassName'];

		if (array_key_exists('id', $relatedEntityData) && $relatedEntityData['id']) {
			$relatedEntity = $this->entityManager->find($associationMappingClassName, $relatedEntityData['id']);
		} else {
			$relatedEntity = $this->entityFactory->createEntity($associationMappingClassName);
			$this->entityManager->persist($relatedEntity);
		}

		return $relatedEntity;
	}


	/**
	 * @param $entity
	 * @param $property
	 * @param $value
	 * @param array $initialCollection
	 * @param $associationMappingClassName
	 * @param $associationMapping
	 * @return mixed
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	private function updateCollection($entity, $property, $value, array $initialCollection, $associationMappingClassName, $associationMapping)
	{
		$value = $value ?? [];
		if (is_array($value) && !$this->isMulti($value)) {
			$toBeAdded = $this->entityManager->getRepository($associationMappingClassName)->findBy(['id' => $value]);
			$initialCollection = array_merge($initialCollection, $toBeAdded);
			if (count($value) != count($toBeAdded)) {
				throw EntityNotFoundException::someOfTheIdsWereNotFound($associationMappingClassName, $value);
			}
		} else {
			//has modifiers?
			if (array_key_exists('%add', $value)) {
				if (!is_array($value)) {
					throw  InvalidValueException::expectedArray($value, $property, $associationMappingClassName);
				}
				/** @var ArrayCollection $initialCollection */
				$initialCollection = $this->accessor->getValue($entity, $property);

				return $this->updateCollection($entity, $property, $value['%add'], $initialCollection->toArray(), $associationMappingClassName, $associationMapping);
			}

			//is object
			try {
				$associationClassFieldDefinitions = $this->fieldsDefinitionProvider->getClassDefinition($associationMapping['targetEntity'], true);
			} catch (FieldDefinitionException $e) {
				$associationClassFieldDefinitions = false;
			}
			foreach ($value as $relatedEntityData) {
				$relatedEntity = $this->obtainRelatedEntity($relatedEntityData, $associationMapping);
				//if field is part of the field definition scope, updated nested values
				if ($associationClassFieldDefinitions) {
					$this->updateEntity($relatedEntity, $relatedEntityData, $associationClassFieldDefinitions);
				}
				$initialCollection[] = $relatedEntity;
			}
		}
		$this->accessor->setValue($entity, $property, $initialCollection);

		return $entity;
	}

}
