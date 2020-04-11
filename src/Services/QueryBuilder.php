<?php

namespace GrownApps\JqlBundle\Services;

use AppBundle\Services\Acl\FieldsPermissionLoader;
use AppBundle\Services\Acl\IPermissionLoader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use GrownApps\JqlBundle\FieldDefinitions\FieldDefinitionsProvider;
use GrownApps\JqlBundle\Utils\ComparisonOperatorNode;
use GrownApps\JqlBundle\Utils\LogicalOperatorNode;
use GrownApps\JqlBundle\Utils\Node;
use GrownApps\JqlBundle\Utils\PathNode;
use Nette\Utils\Strings;

class QueryBuilder
{

	/** @var array */
	private $fieldDefinitions;

	/** @var IPermissionLoader */
	private $permissionLoader;

	/** @var SelectParser */
	private $selectParser;

	/** @var ConditionParser */
	private $conditionParser;

	/** @var \Doctrine\ORM\EntityManagerInterface */
	private $entityManager;


	/**
	 * FundingService constructor.
	 *
	 * @param FieldDefinitionsProvider $fieldsDefinitionProvider
	 * @param FieldsPermissionLoader $permissionLoader
	 * @param EntityManagerInterface $entityManager
	 * @param SelectParser $selectBuilder
	 * @param ConditionParser $whereBuilder
	 */
	public function __construct(
		FieldDefinitionsProvider $fieldsDefinitionProvider,
		FieldsPermissionLoader $permissionLoader,
		EntityManagerInterface $entityManager,
		SelectParser $selectBuilder,
		ConditionParser $whereBuilder
	) {
		$this->fieldDefinitions = $fieldsDefinitionProvider->getFieldsDefinitions();
		$this->permissionLoader = $permissionLoader;
		$this->entityManager = $entityManager;
		$this->selectParser = $selectBuilder;
		$this->conditionParser = $whereBuilder;
	}


	public function createQuery(array $jql)
	{
		$qb = $this->entityManager->createQueryBuilder();

		$select = $this->selectParser->buildSelect($jql);

		$rootEntityDefinition = $this->fieldDefinitions[$select->getAlias()];
		$qb->from($rootEntityDefinition['className'], $select->getAlias());

		$this->traverseSelects($select, $qb);

		if (array_key_exists('$conditions', $jql) && count($jql['$conditions']) > 0) {
			$where = $this->conditionParser->build($select->getAlias(), $jql['$conditions']);

			$this->traverseConditions($where, $qb);
		}
		if (array_key_exists('$sortBy', $jql) && count($jql['$sortBy']) > 0) {
			foreach ($jql['$sortBy'] as $field => $direction) {
				$this->addOrderCondition($select->getAlias(), $field, $direction, $qb);
			}
		}

		return $qb;
	}


	//TODO: Write tests
	private function addOrderCondition(string $alias, string $field, string $direction, \Doctrine\ORM\QueryBuilder $qb)
	{
		if (strpos($field, '.')) {
			$associations = $alias . '_' . implode('_', explode('.', $field, -1)) . substr($field, strripos($field, '.'));
			$qb->addOrderBy($associations, $direction);
		} else {
			$qb->addOrderBy("{$alias}.{$field}", $direction);
		}
	}


	private function traverseSelects(Node $node, \Doctrine\ORM\QueryBuilder $qb, $path = null, $entityFieldDefinitions = null)
	{
		$fields = ['id'];

		//TODO this needs to be implemented way smarter (check if entity has versionalbe trait, if so add revision field automaticaly)
		$entityFieldDefinitions = $entityFieldDefinitions ?? $this->fieldDefinitions[$node->getAlias()];

		// @FIXME: temporary reverting old implementation due failing tests - classMetadata needs to be mocked
//		$meta = $this->entityManager->getClassMetadata($entityFieldDefinitions['className']);
//		try {
//			$primaryKey = $meta->getSingleIdentifierFieldName();
//			$fields[] = $primaryKey;
//		} catch(MappingException $error) {
//			$fields = $meta->getIdentifierFieldNames();
//		}

		if (array_key_exists('revision', $entityFieldDefinitions['fields'])) {
			$fields[] = 'revision';
		}

		$path = $path ?? $node->getAlias();

		foreach ($node->getChildren() as $childNode) {
			/** @var Node $childNode */
			if ($fieldDefinition = $entityFieldDefinitions['fields'][$childNode->getAlias()] ?? false) {
				if ($childNode->isLeaf()) {
					if ($this->isPrimitive($fieldDefinition)) {
						//primitive type
						$fields[] = $childNode->getAlias();
					} else {
						//association - whole entity
						$this->joinAndSelect($qb, $path, $childNode, $fieldDefinition, false);
					}
				} else {
					//association - traverse tree
					$this->joinAndSelect($qb, $path, $childNode, $fieldDefinition, true);
				}
			}
		}
		$fieldsString = implode(', ', $fields);
		$qb->addSelect("PARTIAL {$path}.{{$fieldsString}}");
	}


	private function joinAndSelect(\Doctrine\ORM\QueryBuilder $qb, $path, $childNode, $fieldDefinition, bool $traverse)
	{
		$nextPath = "{$path}_{$childNode->getAlias()}";
		$qb->leftJoin("{$path}.{$childNode->getAlias()}", $nextPath);

		if ($traverse) {
			$nextEntityDefinition = $this->fieldDefinitions[$fieldDefinition['ormAssociation']['targetEntity']];
			$this->traverseSelects($childNode, $qb, $nextPath, $nextEntityDefinition);
		} else {
			$qb->addSelect($nextPath);
		}
	}


	private function traverseConditions(Node $node, \Doctrine\ORM\QueryBuilder $qb, Query\Expr\Composite $expContainer = null)
	{
		$path = $this->getPath($node);
		$exp = false;

		//lazy join - join only when requesting field of the queried entity
		if ($node instanceof PathNode || ($node instanceof ComparisonOperatorNode && $node->getAlias() !== "")) {
			$allAliases = $qb->getAllAliases();
			if ($path && (!$allAliases || !in_array($path, $allAliases))) {
				$closestPathNode = $node->getClosestParentOfType(PathNode::class);
				$closestPathNodePath = $this->getPath($closestPathNode);
				$qb->leftJoin("{$closestPathNodePath}.{$closestPathNode->getAlias()}", $path);
			}
		}

		if ($node instanceof PathNode) {
			$this->traverseConditions($node->getSingleChild(), $qb, $expContainer);
		} elseif ($node instanceof ComparisonOperatorNode) {
			if ($node->getAlias()) {
				if ($this->isNestedPath($node->getAlias())) {
					$leftSide = $this->buildNestedPathExpression($node, $qb);
				} else {
					$leftSide = "{$path}.{$node->getAlias()}";
				}
			} else {
				$closestPathNode = $node->getClosestParentOfType(PathNode::class);
				$closestPathNodePath = $this->getPath($closestPathNode);
				$leftSide = "{$closestPathNodePath}.{$closestPathNode->getAlias()}";
			}
			$param = $this->createUniqueParam($qb, $node->getAlias() ? "{$path}_{$node->getAlias()}" : $path);

			$exp = $this->createExpression($node, $qb, $leftSide, $param);
		} elseif ($node instanceof LogicalOperatorNode) {
			$exp = $this->createLogicalContainer($node, $qb);
			foreach ($node->getChildren() as $child) {
				$this->traverseConditions($child, $qb, $exp);
			}
		}
		if ($exp) {
			if ($expContainer) {
				$expContainer->add($exp);
			} else {
				$qb->add('where', $exp);
			}
		}
	}


	private function isPrimitive($fieldDefinition)
	{
		//todo: improve implementation based on doctrine metadata or upgrade fields definitions
		return !in_array($fieldDefinition['type'], ['codelist', 'reference', 'collection']);
	}


	/**
	 * @param Node $node
	 * @param \Doctrine\ORM\QueryBuilder $qb
	 * @param $leftSide
	 * @param $param
	 * @return Query\Expr\Comparison|Query\Expr\Func
	 * @throws \Exception
	 */
	private function createExpression(Node $node, \Doctrine\ORM\QueryBuilder $qb, $leftSide, $param)
	{
		switch ($node->getType()) {
			case ComparisonOperatorNode::TYPE_IN:
				$exp = $qb->expr()->in($leftSide, ":$param");
				break;
			case ComparisonOperatorNode::TYPE_NOT_IN:
				$exp = $qb->expr()->notIn($leftSide, ":$param");
				break;
			case ComparisonOperatorNode::TYPE_GT:
				$exp = $qb->expr()->gt($leftSide, ":$param");
				break;
			case ComparisonOperatorNode::TYPE_GTE:
				$exp = $qb->expr()->gte($leftSide, ":$param");
				break;
			case ComparisonOperatorNode::TYPE_LT:
				$exp = $qb->expr()->lt($leftSide, ":$param");
				break;
			case ComparisonOperatorNode::TYPE_LTE:
				$exp = $qb->expr()->lte($leftSide, ":$param");
				break;
			case ComparisonOperatorNode::TYPE_EQUALS:
				$exp = $qb->expr()->eq($leftSide, ":$param");
				break;
			case ComparisonOperatorNode::TYPE_LIKE:
				$exp = $qb->expr()->like($leftSide, ":$param");
				break;
			case ComparisonOperatorNode::TYPE_MEMBER_OF:
				$exp = $qb->expr()->isMemberOf(":$param", $leftSide);
				break;
			case ComparisonOperatorNode::TYPE_IS_NULL:
				$exp = $qb->expr()->isNull($leftSide);
				$param = null;
				break;
			case ComparisonOperatorNode::TYPE_IS_NOT_NULL:
				$exp = $qb->expr()->isNotNull($leftSide);
				$param = null;
				break;
			default:
				throw new \Exception("Operator not supported");
		}

		if ($param) {
			$qb->setParameter($param, $node->getValue());
		}

		return $exp;
	}


	/**
	 * @param Node $node
	 * @param \Doctrine\ORM\QueryBuilder $qb
	 * @return Query\Expr\Andx|Query\Expr\Orx
	 * @throws \Exception
	 */
	private function createLogicalContainer(Node $node, \Doctrine\ORM\QueryBuilder $qb)
	{
		switch ($node->getType()) {
			case LogicalOperatorNode::TYPE_AND:
				$exp = $qb->expr()->andX();
				break;
			case LogicalOperatorNode::TYPE_OR:
				$exp = $qb->expr()->orX();
				break;
			default:
				throw new \Exception("Invalid operator type");
		}

		return $exp;
	}


	private function getPath(Node $node): string
	{
		$result = [];
		foreach ($node->getPath() as $item) {
			if ($item !== "" && !Strings::startsWith($item, "%")) {
				$result[] = $item;
			}
		}

		return implode('_', array_reverse($result));
	}


	/**
	 * @param \Doctrine\ORM\QueryBuilder $qb
	 * @param $param
	 * @return string
	 */
	private function createUniqueParam(\Doctrine\ORM\QueryBuilder $qb, $param): string
	{
		$i = 1;
		$testParam = Strings::replace($param, '/\./', '_');
		while ($qb->getParameter($testParam)) {
			$testParam = "{$param}_{$i}";
			$i++;
		}

		return $testParam;
	}


	private function isNestedPath(string $alias): bool
	{
		return count(explode('.', $alias)) > 1;
	}


	private function buildNestedPathExpression(Node $node, \Doctrine\Orm\QueryBuilder $queryBuilder): string
	{
		$path = explode('.', $node->getAlias());

		$leaf = array_pop($path);

		$allAliases = $queryBuilder->getAllAliases();

		$root = $this->getPath($node);
		$alias = '';

		foreach ($path as $join) {
			$previous = $alias;
			$alias .= "_{$join}";
			if (!in_array("{$root}{$alias}", $allAliases, true)) {
				$queryBuilder->leftJoin("{$root}{$previous}.{$join}", "{$root}{$alias}");
			}
		}

		return "{$root}{$alias}.{$leaf}";
	}
}
