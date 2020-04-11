<?php

namespace JqlBundle\Services;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use JqlBundle\Exceptions\EntityNotFoundException;
use JqlBundle\Exceptions\FieldDefinitionException;
use JqlBundle\FieldDefinitions\FieldDefinitionsProvider;
use JqlBundle\FieldDefinitions\ICacheProvider;
use JqlBundle\Hooks\HooksDispatcher;
use JqlBundle\Services\EntityFactory;
use JqlBundle\Services\UpdateHandler;
use PHPUnit\Framework\TestCase;
use JqlBundle\Bar;
use JqlBundle\Baz;
use JqlBundle\Foo;

class UpdateHandlerTest extends TestCase
{

	private $em;

	private $fieldsDefinitionProvider;

	private $updateHandler;

	const GA_BUNDLE_ENTITY_FOO = "JqlBundle\\Entity\\Foo";

	const GA_BUNDLE_ENTITY_BAR = "JqlBundle\\Entity\\Bar";

	const GA_BUNDLE_ENTITY_BAZ = "JqlBundle\\Entity\\Baz";

	private $fooRepository;

	private $entityFactory;

	private $barRepository;

	/** @var HooksDispatcher */
	private $hooksDispatcher;


	private $map = [
		self::GA_BUNDLE_ENTITY_FOO => [],
		self::GA_BUNDLE_ENTITY_BAR => [],
		self::GA_BUNDLE_ENTITY_BAZ => [],
	];

	private $toBeMap = [
		self::GA_BUNDLE_ENTITY_FOO => [],
		self::GA_BUNDLE_ENTITY_BAR => [],
		self::GA_BUNDLE_ENTITY_BAZ => [],
	];


	public function setup(): void
	{
		$this->em = $this->createMock(EntityManager::class);
		//TODO field definition provider should not be responsible for creating definitions and for providing them - split it into 2 classes
		$cacheProvider = $this->createMock(ICacheProvider::class);
		$this->fieldsDefinitionProvider = $this->getMockBuilder(FieldDefinitionsProvider::class)
			->enableProxyingToOriginalMethods()
			->setConstructorArgs([
				$this->createMock(Reader::class),
				$cacheProvider,
				$this->createMock(EntityManager::class),
				'',
				[],
			])
			->getMock();
		$this->fooRepository = $this->createMock(EntityRepository::class);
		$this->barRepository = $this->createMock(EntityRepository::class);
		$this->entityFactory = $this->createMock(EntityFactory::class);
		$this->hooksDispatcher = $this->createMock(HooksDispatcher::class);

		$this->em->method('getRepository')->will($this->returnValueMap([
			[self::GA_BUNDLE_ENTITY_FOO, $this->fooRepository],
			[self::GA_BUNDLE_ENTITY_BAR, $this->barRepository],
		]));

		$this->entityFactory->method('createEntity')->will($this->returnCallback(function ($entity) {
			return array_shift($this->toBeMap[$entity]);
		}));

		$this->em->method('find')->will($this->returnCallback(function ($entityClass, $id) {
			if (array_key_exists($id, $this->map[$entityClass])) {
				return $this->map[$entityClass][$id];
			}

			return false;
		}));

		$this->fooRepository->method('findBy')->will($this->returnCallback(function ($condition) {
			$result = [];
			foreach ($condition['id'] as $id) {
				if (array_key_exists($id, $this->map[self::GA_BUNDLE_ENTITY_FOO])) {
					$result[] = $this->map[self::GA_BUNDLE_ENTITY_FOO][$id];
				}
			}

			return $result;
		}));

		$this->barRepository->method('findBy')->will($this->returnCallback(function ($condition) {
			$result = [];
			foreach ($condition['id'] as $id) {
				if (array_key_exists($id, $this->map[self::GA_BUNDLE_ENTITY_BAR])) {
					$result[] = $this->map[self::GA_BUNDLE_ENTITY_BAR][$id];
				}
			}

			return $result;
		}));

		$cacheProvider->method('isValid')->willReturn(true);
		$cacheProvider->method('getFieldsDefinitions')->willReturn([
			'foo' => [
				"className" => self::GA_BUNDLE_ENTITY_FOO,
				"fields" => [
					"a" => ['type' => 'string', 'ormType' => 'string'],
					"b" => ['type' => 'string', 'ormType' => 'string'],
					"bar" => ['type' => 'reference', 'ormType' => 'association', 'ormAssociation' => ['type' => FieldDefinitionsProvider::ASSOC_ONE_TO_ONE, 'targetEntity' => 'bar', 'targetEntityClassName' => self::GA_BUNDLE_ENTITY_BAR]],
					"baz" => ['type' => 'reference', 'ormType' => 'association', 'ormAssociation' => ['type' => FieldDefinitionsProvider::ASSOC_ONE_TO_ONE, 'targetEntity' => 'baz', 'targetEntityClassName' => self::GA_BUNDLE_ENTITY_BAZ]],
					"bars" => ['type' => 'reference', 'ormType' => 'association', 'ormAssociation' => ['type' => FieldDefinitionsProvider::ASSOC_ONE_TO_MANY, 'targetEntity' => 'bar', 'targetEntityClassName' => self::GA_BUNDLE_ENTITY_BAR]],
					"secondBars" => [
						'type' => 'reference',
						'ormType' => 'association',
						'ormAssociation' => ['type' => FieldDefinitionsProvider::ASSOC_ONE_TO_MANY, 'targetEntity' => 'bar', 'targetEntityClassName' => self::GA_BUNDLE_ENTITY_BAR, 'mappedBy' => 'secondFoo'],
					],

				],
			],
			'bar' => [
				"className" => self::GA_BUNDLE_ENTITY_BAR,
				"fields" => [
					"a" => ['type' => 'string', 'ormType' => 'string'],
					"b" => ['type' => 'string', 'ormType' => 'string'],
					"foo" => ['type' => 'reference', 'ormType' => 'association', 'ormAssociation' => ['type' => FieldDefinitionsProvider::ASSOC_MANY_TO_ONE, 'targetEntity' => 'foo', 'targetEntityClassName' => self::GA_BUNDLE_ENTITY_FOO]],
					"foos" => ['type' => 'reference', 'ormType' => 'association', 'ormAssociation' => ['type' => FieldDefinitionsProvider::ASSOC_MANY_TO_MANY, 'targetEntity' => 'foo', 'targetEntityClassName' => self::GA_BUNDLE_ENTITY_FOO]],
				],
			],
		]);

		$this->updateHandler = new UpdateHandler($this->fieldsDefinitionProvider, $this->em, $this->entityFactory, $this->hooksDispatcher);
	}


	public function testSimpleUpdate()
	{
		$fooMock = $this->createFooMock(1);

		$this->em->expects($this->once())->method('find')->with(self::GA_BUNDLE_ENTITY_FOO, 1);
		$this->em->expects($this->once())->method('flush');
		$fooMock->expects($this->once())->method("setA")->with('test');

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['a' => "test"]);
	}


	public function testMultipleProperties()
	{
		$fooMock = $this->createFooMock(1);

		$this->em->expects($this->once())->method('find')->with(self::GA_BUNDLE_ENTITY_FOO, 1);
		$this->em->expects($this->once())->method('flush');
		$fooMock->expects($this->once())->method("setA")->with('test');
		$fooMock->expects($this->once())->method("setB")->with('test_b');

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['a' => "test", 'b' => "test_b"]);
	}


	public function testEntityDoesNotHaveFieldDefinition()
	{
		$this->expectException(FieldDefinitionException::class);

		$this->updateHandler->findAndUpdateEntity('non-sense', 1, ['a' => "test"]);
	}


	public function testFieldDoesNotHaveFieldDefinition()
	{
		$this->expectException(FieldDefinitionException::class);
		$this->createFooMock(1);

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['non-sense' => "test"]);
	}


	public function testEntityNotFound()
	{
		$this->expectException(EntityNotFoundException::class);

		$this->updateHandler->findAndUpdateEntity('foo', 99, ['a' => "test"]);
	}


	public function testToOneReference_validId()
	{
		$fooMock = $this->createFooMock(1);
		$barMock = $this->createBarMock(1);

		$this->em->expects($this->exactly(2))->method('find')->withConsecutive(
			[self::GA_BUNDLE_ENTITY_FOO, 1],
			[self::GA_BUNDLE_ENTITY_BAR, 1]
		);

		$this->em->expects($this->once())->method('flush');

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['bar' => 1]);

		$this->assertSame($barMock, $fooMock->getBar());
	}


	public function testToOneReference_null()
	{
		$fooMock = $this->createFooMock(1);

		$this->em->expects($this->exactly(1))->method('find')->withConsecutive(
			[self::GA_BUNDLE_ENTITY_FOO, 1]
		);

		$this->em->expects($this->once())->method('flush');

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['bar' => null]);

		$this->assertSame(null, $fooMock->getBar());
	}


	public function testToOneReference_validIdAsObject()
	{
		$fooMock = $this->createFooMock(1);
		$barMock = $this->createBarMock(1);

		$this->em->expects($this->exactly(2))->method('find')->withConsecutive(
			[self::GA_BUNDLE_ENTITY_FOO, 1],
			[self::GA_BUNDLE_ENTITY_BAR, 1]
		);

		$this->em->expects($this->once())->method('flush');

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['bar' => ['id' => 1]]);

		$this->assertSame($barMock, $fooMock->getBar());
	}


	public function testToOneReference_outsiceFieldDefScope_id()
	{
		$fooMock = $this->createFooMock(1);
		$bazMock = $this->createBazMock(1);

		$this->em->expects($this->exactly(2))->method('find')->withConsecutive(
			[self::GA_BUNDLE_ENTITY_FOO, 1],
			[self::GA_BUNDLE_ENTITY_BAZ, 1]
		);

		$this->em->expects($this->once())->method('flush');

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['baz' => 1]);

		$this->assertSame($bazMock, $fooMock->getBaz());
	}


	public function testToOneReference_outsiceFieldDefScope_nested()
	{
		$fooMock = $this->createFooMock(1);
		$bazMock = $this->createBazMock(1);

		$this->em->expects($this->exactly(2))->method('find')->withConsecutive(
			[self::GA_BUNDLE_ENTITY_FOO, 1],
			[self::GA_BUNDLE_ENTITY_BAZ, 1]
		);

		$this->em->expects($this->once())->method('flush');

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['baz' => ['id' => 1]]);

		$this->assertSame($bazMock, $fooMock->getBaz());
	}


	public function testToOneReference_invalidId()
	{
		$this->expectException(EntityNotFoundException::class);

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['bar' => 99]);

	}


	public function testToManyReference_simpleAddExisting()
	{
		$fooMock = $this->createFooMock(1);
		$barMock = $this->createBarMock(1);

		$this->em->expects($this->once())->method('find')->with(self::GA_BUNDLE_ENTITY_BAR, 1);
		$this->fooRepository->expects($this->once())->method('findBy')->with(['id' => [1]]);
		$barMock->expects($this->once())->method('addFoo')->with($fooMock);
		$this->em->expects($this->once())->method('flush');

		$this->updateHandler->findAndUpdateEntity('bar', 1, ['foos' => [1]]);
	}


	public function testToManyReference_addMultipleExisting()
	{
		$fooMock1 = $this->createFooMock(1);
		$fooMock2 = $this->createFooMock(2);
		$barMock = $this->createBarMock(1);

		$this->em->expects($this->once())->method('find')->with(self::GA_BUNDLE_ENTITY_BAR, 1);
		$this->fooRepository->expects($this->once())->method('findBy')->with(['id' => [1, 2]]);
		$barMock->expects($this->exactly(2))->method('addFoo')->withConsecutive(
			[$fooMock1],
			[$fooMock2]
		);
		$this->em->expects($this->once())->method('flush');

		$this->updateHandler->findAndUpdateEntity('bar', 1, ['foos' => [1, 2]]);

		$this->assertTrue($barMock->getFoos()->contains($fooMock1));
		$this->assertTrue($barMock->getFoos()->contains($fooMock2));
	}


	public function testToManyReference_simpleRemoveExisting()
	{
		$fooMock = $this->createFooMock(1);
		$barMock = $this->createBarMock(1);
		$barMock->addFoo($fooMock);

		$this->em->expects($this->once())->method('find')->with(self::GA_BUNDLE_ENTITY_BAR, 1);
		$this->fooRepository->expects($this->once())->method('findBy')->with(['id' => []]);
		$barMock->expects($this->once())->method('removeFoo')->with($fooMock);
		$this->em->expects($this->once())->method('flush');

		$this->updateHandler->findAndUpdateEntity('bar', 1, ['foos' => []]);
	}


	public function testToManyReference_collectionNonExistingId()
	{
		$this->expectException(EntityNotFoundException::class);

		$this->updateHandler->findAndUpdateEntity('bar', 1, ['foos' => [99]]);
	}


	public function testToOneReference_nested()
	{
		$fooMock = $this->createFooMock();
		$barMock = $this->createBarMock(1);

		$this->em->expects($this->once())->method('find')->with(self::GA_BUNDLE_ENTITY_BAR, 1);
		$this->em->expects($this->once())->method('persist')->with($fooMock);
		$barMock->expects($this->once())->method('setFoo')->with($fooMock);

		$this->updateHandler->findAndUpdateEntity('bar', 1, ['foo' => ['a' => 1, 'b' => 2]]);

		$this->assertSame($fooMock, $barMock->getFoo());
		$this->assertEquals(1, $fooMock->getA());
		$this->assertEquals(2, $fooMock->getB());

	}


	public function testToManyReference_nested()
	{
		$fooMockA = $this->createFooMock();
		$fooMockB = $this->createFooMock();
		$barMock = $this->createBarMock(1);

		$this->em->expects($this->once())->method('find')->with(self::GA_BUNDLE_ENTITY_BAR, 1);
		$this->em->expects($this->exactly(2))->method('persist')->withConsecutive(
			[$fooMockA],
			[$fooMockB]
		);
		$barMock->expects($this->exactly(2))->method('addFoo')->withConsecutive(
			[$fooMockA],
			[$fooMockB]
		);

		$this->updateHandler->findAndUpdateEntity('bar', 1, [
			'foos' => [
				['a' => 1, 'b' => 2],
				['a' => 3, 'b' => 4],
			],
		]);

		$this->assertTrue($barMock->getFoos()->contains($fooMockA));
		$this->assertTrue($barMock->getFoos()->contains($fooMockB));
		$this->assertEquals(1, $fooMockA->getA());
		$this->assertEquals(2, $fooMockA->getB());
		$this->assertEquals(3, $fooMockB->getA());
		$this->assertEquals(4, $fooMockB->getB());

	}


	public function testToManyReference_nested_and_existing()
	{
		$fooMockA = $this->createFooMock();
		$fooMockB = $this->createFooMock(1);
		$barMock = $this->createBarMock(1);

		$this->em->expects($this->exactly(2))->method('find')->withConsecutive(
			[self::GA_BUNDLE_ENTITY_BAR, 1],
			[self::GA_BUNDLE_ENTITY_FOO, 1]
		);
		$this->em->expects($this->exactly(1))->method('persist')->withConsecutive(
			[$fooMockA]
		);
		$barMock->expects($this->exactly(2))->method('addFoo')->withConsecutive(
			[$fooMockA],
			[$fooMockB]
		);

		$this->updateHandler->findAndUpdateEntity('bar', 1, [
			'foos' => [
				['a' => 1, 'b' => 2],
				['id' => 1, 'a' => 3, 'b' => 4],
			],
		]);

		$this->assertTrue($barMock->getFoos()->contains($fooMockA));
		$this->assertTrue($barMock->getFoos()->contains($fooMockB));
		$this->assertEquals(1, $fooMockA->getA());
		$this->assertEquals(2, $fooMockA->getB());
		$this->assertEquals(3, $fooMockB->getA());
		$this->assertEquals(4, $fooMockB->getB());
		$this->assertEquals(1, $fooMockB->getId());

	}


	public function testToOneReference_nested_three_levels_mixed()
	{
		$fooMock = $this->createFooMock();
		$barMock = $this->createBarMock(1);
		$newBarMock = $this->createBarMock();

		$this->em->expects($this->once())->method('find')->with(self::GA_BUNDLE_ENTITY_BAR, 1);
		$this->em->expects($this->exactly(2))->method('persist')->withConsecutive(
			[$fooMock],
			[$newBarMock]
		);
		$barMock->expects($this->once())->method('setFoo')->with($fooMock);
		$fooMock->expects($this->once())->method('setBar')->with($newBarMock);

		$this->updateHandler->findAndUpdateEntity('bar', 1, ['foo' => ['a' => 1, 'b' => 2, 'bar' => ['a' => 3]]]);

		$this->assertSame($fooMock, $barMock->getFoo());
		$this->assertEquals(1, $fooMock->getA());
		$this->assertEquals(2, $fooMock->getB());
		$this->assertEquals($newBarMock, $fooMock->getBar());

	}


	public function testToManyReference_addOnly_existing()
	{
		$fooMock = $this->createFooMock(1);
		$barMock = $this->createBarMock(1);
		$barMock2 = $this->createBarMock(2);

		$fooMock->addBar($barMock);

		$fooMock->expects($this->once())->method('addBar')->with($barMock2);

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['bars' => ['%add' => [2]]]);

		$this->assertEquals(2, $fooMock->getBars()->count());

	}

	public function testToManyReference_addOnly_new()
	{
		$fooMock = $this->createFooMock(1);
		$barMock = $this->createBarMock(1);
		$barMock2 = $this->createBarMock();

		$fooMock->addBar($barMock);

		$fooMock->expects($this->once())->method('addBar')->with($barMock2);
		$this->em->expects($this->once())->method('persist')->with($barMock2);

		$this->updateHandler->findAndUpdateEntity('foo', 1, ['bars' => ['%add' => [["a" => 1, "b" => 2]]]]);

		$this->assertEquals(2, $fooMock->getBars()->count());
		$this->assertEquals(1, $fooMock->getBars()->last()->getA());
		$this->assertEquals(2, $fooMock->getBars()->last()->getB());

	}


	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|Foo
	 */
	private function createFooMock($id = false)
	{
		$foo = $this->getMockBuilder(Foo::class)->enableProxyingToOriginalMethods()->getMock();
		if ($id) {
			$this->map[self::GA_BUNDLE_ENTITY_FOO][$id] = $foo;
			$foo->setId($id);
		} else {
			$this->toBeMap[self::GA_BUNDLE_ENTITY_FOO][] = $foo;
		}

		return $foo;
	}


	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|Bar
	 */
	private function createBarMock($id = false)
	{
		$bar = $this->getMockBuilder(Bar::class)->enableProxyingToOriginalMethods()->getMock();
		if ($id) {
			$this->map[self::GA_BUNDLE_ENTITY_BAR][$id] = $bar;
			$bar->setId($id);
		} else {
			$this->toBeMap[self::GA_BUNDLE_ENTITY_BAR][] = $bar;
		}

		return $bar;
	}


	/**
	 * @param bool $id
	 * @return \PHPUnit\Framework\MockObject\MockObject|Baz
	 */
	private function createBazMock($id = false)
	{
		$baz = $this->getMockBuilder(Baz::class)->enableProxyingToOriginalMethods()->getMock();
		if ($id) {
			$this->map[self::GA_BUNDLE_ENTITY_BAZ][$id] = $baz;
			$baz->setId($id);
		} else {
			$this->toBeMap[self::GA_BUNDLE_ENTITY_BAZ][] = $baz;
		}

		return $baz;
	}

}
