<?php declare(strict_types=1);

namespace JqlBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use JqlBundle\FieldDefinitions\FieldDefinitionsProvider;
use JqlBundle\Security\ISecurityProvider;
use JqlBundle\Serialization\ExclusionStrategy;
use JqlBundle\Services\QueryBuilder;
use JqlBundle\Services\UpdateHandler;
use JqlBundle\Utils\JqlHelper;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Class ApiController
 *
 * @package JqlBundle\Controller
 */
class ApiController
{

	/** @var QueryBuilder */
	private $queryBuilder;

	/** @var UpdateHandler */
	private $updateHandler;

	/** @var FieldDefinitionsProvider */
	private $definitionProvider;

	/** @var SerializerInterface */
	private $serializer;

	/**
	 * @var ISecurityProvider
	 */
	private $securityProvider;


	/**
	 * ApiController constructor.
	 *
	 * @param QueryBuilder $queryBuilder
	 * @param UpdateHandler $updateHandler
	 * @param FieldDefinitionsProvider $definitionProvider
	 * @param SerializerInterface $serializer
	 * @param ISecurityProvider $securityProvider
	 */
	public function __construct(
		QueryBuilder $queryBuilder,
		UpdateHandler $updateHandler,
		FieldDefinitionsProvider $definitionProvider,
		SerializerInterface $serializer,
		ISecurityProvider $securityProvider
	) {
		$this->queryBuilder = $queryBuilder;
		$this->updateHandler = $updateHandler;
		$this->definitionProvider = $definitionProvider;
		$this->serializer = $serializer;
		$this->securityProvider = $securityProvider;
	}


	public function retrieve(Request $request): JsonResponse
	{
		$jql = json_decode($request->query->get('query', "[]"), true);
		$qb = $this->queryBuilder->createQuery($jql);

		try {
			$this->securityProvider->applySecurity($qb);
		}catch (AccessDeniedException $e){
			return new JsonResponse([], 403);
		}

		$data = $qb->getQuery()->getArrayResult();

		return new JsonResponse($data, 200);
	}


	/**
	 * @param Request $request
	 * @return JsonResponse
	 *
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function update(Request $request): JsonResponse
	{
		$id = $request->get('id');
		$entityClass = $request->get('entityClass');
		$data = $request->get('data');
		$dependencies = $request->get('dependencies');
		$fieldList = $request->get('responseFieldsOverride');

		try {
			$entity = $this->updateHandler->findAndUpdateEntity($entityClass, $id, $data);
		} catch (ValidatorException $e) {
			//TODO just for illustration, we will use own validation exceptions in the future
		}
		$context = new SerializationContext();

		if (!$fieldList) {
			$fieldList = array_merge(JqlHelper::extractFieldListFromRequest($data), $dependencies);
		}

		$context->addExclusionStrategy(new ExclusionStrategy($this->definitionProvider, $fieldList, $entityClass));
		$json = $this->serializer->serialize($entity, 'json', $context);

		return new JsonResponse($json, 200, [], true);
	}


	/**
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function create(Request $request): JsonResponse
	{
		$entityClass = $request->get('entityClass');
		$data = $request->get('data');
		$dependencies = $request->get('dependencies');
		$fieldList = $request->get('responseFieldsOverride');

		$entity = $this->updateHandler->createEntity($entityClass, $data);

		if (!$fieldList) {
			$fieldList = array_merge(JqlHelper::extractFieldListFromRequest($data), $dependencies);
		}

		//TODO serialization need to be improved (ACL...)
		$context = new SerializationContext();
		$context->addExclusionStrategy(new ExclusionStrategy($this->definitionProvider, $fieldList, $entityClass));
		$json = $this->serializer->serialize($entity, 'json', $context);

		return new JsonResponse($json, 201, [], true);
	}


	/**
	 * @param Request $request
	 * @return JsonResponse
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function delete(Request $request): JsonResponse
	{
		$id = $request->get('id');
		$entityClass = $request->get('entityClass');

		$this->updateHandler->deleteEntity($entityClass, $id);

		return new JsonResponse([], 200);
	}

}
