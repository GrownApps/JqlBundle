<?php

namespace GrownApps\JqlBundle\Services;

use AppBundle\Services\Acl\FieldsPermissionLoader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use GrownApps\JqlBundle\FieldDefinitions\FieldDefinitionsProvider;
use GrownApps\JqlBundle\Services\ConditionParser;
use GrownApps\JqlBundle\Services\QueryBuilder;
use GrownApps\JqlBundle\Services\SelectParser;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{

	/**
	 * @return array
	 */
	private $expressionBuilderMock;

	private $entityManagerMock;

	private $queryBuilderMock;

	private $queryBuilder;


	protected function setup(): void
	{
		$this->expressionBuilderMock = $this->getMockBuilder(Query\Expr::class)->enableProxyingToOriginalMethods()->getMock();
		$this->entityManagerMock = $this->createMock(EntityManager::class);
		$this->entityManagerMock->method('getExpressionBuilder')->willReturn($this->expressionBuilderMock);

		$this->queryBuilderMock = $this->getMockBuilder(\Doctrine\ORM\QueryBuilder::class)
			->setConstructorArgs([$this->entityManagerMock])
			->enableProxyingToOriginalMethods()->getMock();

		$this->queryBuilderMock->method('expr')->willReturn($this->expressionBuilderMock);
		$this->entityManagerMock->method('createQueryBuilder')->willReturn($this->queryBuilderMock);

		$fieldsDef = $this->createMock(FieldDefinitionsProvider::class);

		$selectBuilder = new SelectParser();
		$whereBuilder = new ConditionParser();

		$fieldsDef->method('getFieldsDefinitions')->willReturn([
			"foo" => [
				"className" => "GrownApps\\JqlBundle\\Entity\\Foo",
				"fields" => [
					"a" => ['type' => 'string', 'ormType' => 'string'],
					"b" => ['type' => 'string', 'ormType' => 'string'],
					"bar" => ['type' => 'reference', 'ormType' => 'association', 'ormAssociation' => ['type' => ClassMetadataInfo::MANY_TO_ONE, 'targetEntity' => 'bar']],
					"rab" => ['type' => 'reference', 'ormType' => 'association', 'ormAssociation' => ['type' => ClassMetadataInfo::ONE_TO_MANY, 'targetEntity' => 'bar']],
				],
			],
			"bar" => [
				"className" => "GrownApps\\JqlBundle\\Entity\\Bar",
				"fields" => [
					"a" => ['type' => 'string', 'ormType' => 'string'],
					"b" => ['type' => 'string', 'ormType' => 'string'],
					"foo" => ['type' => 'reference', 'ormType' => 'association', 'ormAssociation' => ['type' => ClassMetadataInfo::ONE_TO_MANY, 'targetEntity' => 'foo']],
					"oof" => ['type' => 'reference', 'ormType' => 'association', 'ormAssociation' => ['type' => ClassMetadataInfo::MANY_TO_ONE, 'targetEntity' => 'foo']],
				],
			],
		]);


		$this->queryBuilder = new QueryBuilder($fieldsDef, $this->entityManagerMock, $selectBuilder, $whereBuilder);
	}


	public function testCreateQuery_simpleQuery()
	{

		$this->queryBuilderMock->expects($this->once())->method('from');
		$this->queryBuilderMock->expects($this->once())->method('addSelect')->with($this->equalTo('PARTIAL foo.{id, a, b}'));

		$query = ['foo' => ['a', 'b']];


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_simpleJoin()
	{
		$query = ['foo' => ['a', 'b', ['bar' => ['a', 'b']]]];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo');
		$this->queryBuilderMock->expects($this->exactly(2))->method('addSelect')->withConsecutive(
			[$this->equalTo('PARTIAL foo_bar.{id, a, b}')],
			[$this->equalTo('PARTIAL foo.{id, a, b}')]
		);
		$this->queryBuilderMock->expects($this->once())->method('leftJoin')->with($this->equalTo('foo.bar'), $this->equalTo('foo_bar'));


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_nestedJoin()
	{
		$query = ['foo' => ['bar.foo.a']];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo');
		$this->queryBuilderMock->expects($this->exactly(3))->method('addSelect')->withConsecutive(
			[$this->equalTo('PARTIAL foo_bar_foo.{id, a}')],
			[$this->equalTo('PARTIAL foo_bar.{id}')],
			[$this->equalTo('PARTIAL foo.{id}')]
		);
		$this->queryBuilderMock->expects($this->exactly(2))->method('leftJoin')->withConsecutive(
			[$this->equalTo('foo.bar'), $this->equalTo('foo_bar')],
			[$this->equalTo('foo_bar.foo'), $this->equalTo('foo_bar_foo')]
		);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_multipleJoins()
	{
		$query = ['bar' => ['foo.a', ['oof' => ['a', 'b']]]];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Bar');
		$this->queryBuilderMock->expects($this->exactly(3))->method('addSelect')->withConsecutive(
			[$this->equalTo('PARTIAL bar_foo.{id, a}')],
			[$this->equalTo('PARTIAL bar_oof.{id, a, b}')],
			[$this->equalTo('PARTIAL bar.{id}')]
		);
		$this->queryBuilderMock->expects($this->exactly(2))->method('leftJoin')->withConsecutive(
			[$this->equalTo('bar.foo'), $this->equalTo('bar_foo')],
			[$this->equalTo('bar.oof'), $this->equalTo('bar_oof')]
		);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_fullEntityJoins()
	{
		$query = ['bar' => ['foo', 'oof']];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Bar');
		$this->queryBuilderMock->expects($this->exactly(3))->method('addSelect')->withConsecutive(
			[$this->equalTo('bar_foo')],
			[$this->equalTo('bar_oof')],
			[$this->equalTo('PARTIAL bar.{id}')]
		);
		$this->queryBuilderMock->expects($this->exactly(2))->method('leftJoin')->withConsecutive(
			[$this->equalTo('bar.foo'), $this->equalTo('bar_foo')],
			[$this->equalTo('bar.oof'), $this->equalTo('bar_oof')]
		);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_simpleWhere()
	{
		$query = [
			'foo' => ['a', 'b'],
			'$conditions' => [
				'a' => 2,
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');

		$this->expressionBuilderMock->expects($this->once())->method('eq')->with('foo.a', ':foo_a');
		$this->queryBuilderMock->expects($this->once())->method('setParameter')->with('foo_a', 2);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_multipleWhere()
	{
		$query = [
			'foo' => ['a', 'b'],
			'$conditions' => [
				'a' => 2,
				'b' => [1, 2],
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');

		//todo test AND
		$this->expressionBuilderMock->expects($this->once())->method('eq')->with('foo.a', ':foo_a');
		$this->expressionBuilderMock->expects($this->once())->method('in')->with('foo.b', ':foo_b');
		$this->queryBuilderMock->expects($this->exactly(2))->method('setParameter')->withConsecutive(
			['foo_a', 2],
			['foo_b', [1, 2]]
		);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_simpleNested()
	{
		$query = [
			'foo' => ['a', 'b'],
			'$conditions' => [
				'bar' => ['a' => 1],
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo', 'foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');
		$this->queryBuilderMock->expects($this->once())->method('leftJoin')->with('foo.bar', 'foo_bar');

		$this->expressionBuilderMock->expects($this->once())->method('eq')->with('foo_bar.a', ':foo_bar_a');
		$this->queryBuilderMock->expects($this->once())->method('setParameter')->withConsecutive(
			['foo_bar_a', 1]
		);


		$this->queryBuilder->createQuery($query);
	}

	public function testCreateQuery_simpleNestedInline()
	{
		$query = [
			'foo' => ['a', 'b'],
			'$conditions' => [
				'bar.a' => 1,
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo', 'foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');
		$this->queryBuilderMock->expects($this->once())->method('leftJoin')->with('foo.bar', 'foo_bar');

		$this->expressionBuilderMock->expects($this->once())->method('eq')->with('foo_bar.a', ':foo_bar_a');
		$this->queryBuilderMock->expects($this->once())->method('setParameter')->withConsecutive(
			['foo_bar_a', 1]
		);

		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_simpleNested_dontJoinTwice()
	{
		$query = [
			'foo' => ['a', 'b', 'bar.a'],
			'$conditions' => [
				'bar' => ['a' => 1],
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo', 'foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');
		$this->queryBuilderMock->expects($this->once())->method('leftJoin')->with('foo.bar', 'foo_bar');

		$this->expressionBuilderMock->expects($this->once())->method('eq')->with('foo_bar.a', ':foo_bar_a');
		$this->queryBuilderMock->expects($this->once())->method('setParameter')->withConsecutive(
			['foo_bar_a', 1]
		);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_simpleNestedInline_dontJoinTwice()
	{
		$query = [
			'foo' => ['a', 'b', 'bar.a'],
			'$conditions' => [
				'bar.a' => 1,
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo', 'foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');
		$this->queryBuilderMock->expects($this->once())->method('leftJoin')->with('foo.bar', 'foo_bar');

		$this->expressionBuilderMock->expects($this->once())->method('eq')->with('foo_bar.a', ':foo_bar_a');
		$this->queryBuilderMock->expects($this->once())->method('setParameter')->withConsecutive(
			['foo_bar_a', 1]
		);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_multipleNested()
	{
		$query = [
			'foo' => ['a', 'b', 'bar.a'],
			'$conditions' => [
				'bar' => ['a' => 1],
				'rab' => ['b' => 2],
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo', 'foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');
		$this->queryBuilderMock->expects($this->exactly(2))->method('leftJoin')->withConsecutive(
			['foo.bar', 'foo_bar'],
			['foo.rab', 'foo_rab']
		);

		$this->expressionBuilderMock->expects($this->exactly(2))->method('eq')->withConsecutive(
			['foo_bar.a', ':foo_bar_a'],
			['foo_rab.b', ':foo_rab_b']
		);
		$this->queryBuilderMock->expects($this->exactly(2))->method('setParameter')->withConsecutive(
			['foo_bar_a', 1],
			['foo_rab_b', 2]
		);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_multipleNestedInlined()
	{
		$query = [
			'foo' => ['a', 'b', 'bar.a'],
			'$conditions' => [
				'bar.a' => 1,
				'rab.b' => 2,
			],
		];

		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo', 'foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');
		$this->queryBuilderMock->expects($this->exactly(2))->method('leftJoin')->withConsecutive(
			['foo.bar', 'foo_bar'],
			['foo.rab', 'foo_rab']
		);

		$this->expressionBuilderMock->expects($this->exactly(2))->method('eq')->withConsecutive(
			['foo_bar.a', ':foo_bar_a'],
			['foo_rab.b', ':foo_rab_b']
		);
		$this->queryBuilderMock->expects($this->exactly(2))->method('setParameter')->withConsecutive(
			['foo_bar_a', 1],
			['foo_rab_b', 2]
		);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_nestedMultipleLevels()
	{
		$query = [
			'foo' => ['a', 'b', 'bar.a'],
			'$conditions' => [
				'bar' => ['oof' => ['bar' => ['a' => 1]]],
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo', 'foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');
		$this->queryBuilderMock->expects($this->exactly(3))->method('leftJoin')->withConsecutive(
			['foo.bar', 'foo_bar'],
			['foo_bar.oof', 'foo_bar_oof'],
			['foo_bar_oof.bar', 'foo_bar_oof_bar']
		);

		$this->expressionBuilderMock->expects($this->exactly(1))->method('eq')->withConsecutive(
			['foo_bar_oof_bar.a', ':foo_bar_oof_bar_a']
		);
		$this->queryBuilderMock->expects($this->exactly(1))->method('setParameter')->withConsecutive(
			['foo_bar_oof_bar_a', 1]
		);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_nestedMultipleLevelsInlined()
	{
		$query = [
			'foo' => ['a', 'b', 'bar.a'],
			'$conditions' => [
				'bar.oof.bar.a' => 1,
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo', 'foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');
		$this->queryBuilderMock->expects($this->exactly(3))->method('leftJoin')->withConsecutive(
			['foo.bar', 'foo_bar'],
			['foo_bar.oof', 'foo_bar_oof'],
			['foo_bar_oof.bar', 'foo_bar_oof_bar']
		);

		$this->expressionBuilderMock->expects($this->exactly(1))->method('eq')->withConsecutive(
			['foo_bar_oof_bar.a', ':foo_bar_oof_bar_a']
		);
		$this->queryBuilderMock->expects($this->exactly(1))->method('setParameter')->withConsecutive(
			['foo_bar_oof_bar_a', 1]
		);


		$this->queryBuilder->createQuery($query);
	}


	public function testCreateQuery_nestedMultipleLevelsInlined_DontJoinTwice()
	{
		$query = [
			'foo' => ['a', 'b', 'bar.oof.a'],
			'$conditions' => [
				'bar.oof.bar.a' => 1,
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo', 'foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');
		$this->queryBuilderMock->expects($this->exactly(3))->method('leftJoin')->withConsecutive(
			['foo.bar', 'foo_bar'],
			['foo_bar.oof', 'foo_bar_oof'],
			['foo_bar_oof.bar', 'foo_bar_oof_bar']
		);

		$this->expressionBuilderMock->expects($this->exactly(1))->method('eq')->withConsecutive(
			['foo_bar_oof_bar.a', ':foo_bar_oof_bar_a']
		);
		$this->queryBuilderMock->expects($this->exactly(1))->method('setParameter')->withConsecutive(
			['foo_bar_oof_bar_a', 1]
		);


		$this->queryBuilder->createQuery($query);
	}

	public function testCreateQuery_simpleOr()
	{
		$query = [
			'foo' => ['a', 'b'],
			'$conditions' => [
				'%or' => [
					'a' => 1,
					'b' => 2,
				],
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');

		$this->expressionBuilderMock->expects($this->exactly(1))->method('orX');
		$this->expressionBuilderMock->expects($this->exactly(2))->method('eq')->withConsecutive(
			['foo.a', ':foo_a'],
			['foo.b', ':foo_b']
		);
		$this->queryBuilderMock->expects($this->exactly(2))->method('setParameter')->withConsecutive(
			['foo_a', 1],
			['foo_b', 2]
		);


		$qb = $this->queryBuilder->createQuery($query);
		$this->assertEquals("SELECT PARTIAL foo.{id, a, b} FROM GrownApps\JqlBundle\Entity\Foo foo WHERE foo.a = :foo_a OR foo.b = :foo_b", $qb->getDQL());
	}


	public function testCreateQuery_complex()
	{
		$query = [
			'foo' => ['a', 'b'],
			'$conditions' => [
				'rab' => [
					"%or" => [
						'%eq' => 1,
						'foo' => 2,
						'oof' => ['a' => 3, 'b' => 4],
					],
				],
				'a' => ['%gt' => 1, '%lt' => 10],
				'b' => ['%gte' => 2, '%lte' => 20],
				'bar' => [1, 2, 3],
				'c' => ['%member_of' => 6]
			],
		];


		$this->queryBuilderMock->expects($this->once())->method('from')->with('GrownApps\\JqlBundle\\Entity\\Foo');
		$this->queryBuilderMock->expects($this->once())->method('add')->with('where');

		$this->expressionBuilderMock->expects($this->exactly(1))->method('orX');
		$this->expressionBuilderMock->expects($this->exactly(4))->method('andX');

		$this->expressionBuilderMock->expects($this->exactly(1))->method('gt')->withConsecutive(
			['foo.a', ':foo_a']
		);
		$this->expressionBuilderMock->expects($this->exactly(1))->method('lt')->withConsecutive(
			['foo.a', ':foo_a_1']
		);
		$this->expressionBuilderMock->expects($this->exactly(1))->method('gte')->withConsecutive(
			['foo.b', ':foo_b']
		);
		$this->expressionBuilderMock->expects($this->exactly(1))->method('lte')->withConsecutive(
			['foo.b', ':foo_b_1']
		);
		$this->expressionBuilderMock->expects($this->exactly(1))->method('in')->withConsecutive(
			['foo.bar', ':foo_bar']
		);
		$this->expressionBuilderMock->expects($this->exactly(1))->method('isMemberOf')->withConsecutive(
			[':foo_c', 'foo.c']
		);
		$this->expressionBuilderMock->expects($this->exactly(4))->method('eq')->withConsecutive(
			['foo.rab', ':foo_rab'],
			['foo_rab.foo', ':foo_rab_foo'],
			['foo_rab_oof.a', ':foo_rab_oof_a'],
			['foo_rab_oof.b', ':foo_rab_oof_b']
		);
		$this->queryBuilderMock->expects($this->exactly(10))->method('setParameter')->withConsecutive(
			['foo_rab', 1],
			['foo_rab_foo', 2],
			['foo_rab_oof_a', 3],
			['foo_rab_oof_b', 4],
			['foo_a', 1],
			['foo_a_1', 10],
			['foo_b', 2],
			['foo_b_1', 20],
			['foo_bar', [1, 2, 3]],
			['foo_c', 6]
		);

		$this->queryBuilderMock->expects($this->exactly(2))->method('leftJoin')->withConsecutive(
			['foo.rab', 'foo_rab'],
			['foo_rab.oof', 'foo_rab_oof']
		);


		$qb = $this->queryBuilder->createQuery($query);
		$this->assertEquals("SELECT PARTIAL foo.{id, a, b} FROM GrownApps\JqlBundle\Entity\Foo foo LEFT JOIN foo.rab foo_rab LEFT JOIN foo_rab.oof foo_rab_oof WHERE (foo.rab = :foo_rab OR foo_rab.foo = :foo_rab_foo OR (foo_rab_oof.a = :foo_rab_oof_a AND foo_rab_oof.b = :foo_rab_oof_b)) AND (foo.a > :foo_a AND foo.a < :foo_a_1) AND (foo.b >= :foo_b AND foo.b <= :foo_b_1) AND foo.bar IN(:foo_bar) AND :foo_c MEMBER OF foo.c",
			$qb->getDQL());
	}
}
