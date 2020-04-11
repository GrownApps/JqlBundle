<?php

namespace GrownApps\JqlBundle\Services;

use GrownApps\JqlBundle\Services\ConditionParser;
use GrownApps\JqlBundle\Utils\ComparisonOperatorNode;
use GrownApps\JqlBundle\Utils\LogicalOperatorNode;
use GrownApps\JqlBundle\Utils\Node;
use GrownApps\JqlBundle\Utils\PathNode;
use PHPUnit\Framework\TestCase;

class ConditionParserTest extends TestCase
{

	public function testSingleElement()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', ['b' => 1]);

		$this->isPath($result, 'a');
		$this->isComparison($result->getSingleChild(), ComparisonOperatorNode::TYPE_EQUALS, 1);
	}


	public function testMultipleElement()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', ['b' => 1, 'c' => 2]);

		$this->isPath($result, 'a');
		$this->isLogical($result->getSingleChild(), LogicalOperatorNode::TYPE_AND, ['b', 'c']);
		$this->isComparison($result->getSingleChild()->getChild('b'), ComparisonOperatorNode::TYPE_EQUALS, 1);
		$this->isComparison($result->getSingleChild()->getChild('c'), ComparisonOperatorNode::TYPE_EQUALS, 2);
	}


	public function testNestedElement()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', ['b' => ['c' => 1]]);

		$this->isPath($result, 'a');
		$this->isPath($result->getSingleChild(), 'b');
		$this->isComparison($result->getSingleChild()->getChild('c'), ComparisonOperatorNode::TYPE_EQUALS, 1);
	}


	public function testMultpipleElementSecondLevel()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', ['b' => ['c' => 1, 'd' => 2]]);

		$this->isPath($result, 'a');
		$this->isPath($result->getSingleChild(), 'b');
		$this->isLogical($result->getSingleChild()->getSingleChild(), LogicalOperatorNode::TYPE_AND, ['c', 'd']);
		$this->isComparison($result->getSingleChild()->getSingleChild()->getChild('c'), ComparisonOperatorNode::TYPE_EQUALS, 1);
		$this->isComparison($result->getSingleChild()->getSingleChild()->getChild('d'), ComparisonOperatorNode::TYPE_EQUALS, 2);
	}


	public function testComplexTree()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', [
			'b' => [
				'c' => 1,
				'd' => ['e' => 2],
			],
		]);

		$this->isPath($result, 'a');
		$this->isPath($result->getSingleChild(), 'b');
		$this->isLogical($result->getSingleChild()->getSingleChild(), LogicalOperatorNode::TYPE_AND, ['c', 'd']);
		$this->isComparison($result->getSingleChild()->getSingleChild()->getChild('c'), ComparisonOperatorNode::TYPE_EQUALS, 1);

		$this->isPath($result->getSingleChild()->getSingleChild()->getChild('d'), 'd');
		$this->isComparison($result->getSingleChild()->getSingleChild()->getChild('d')->getSingleChild(), ComparisonOperatorNode::TYPE_EQUALS, 2);
	}


	public function testMultpipleElementsOr()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', ['%or' => ['b' => 1, 'c' => 2]]);

		$this->isPath($result, 'a');
		$this->isLogical($result->getSingleChild(), LogicalOperatorNode::TYPE_OR, ['b', 'c']);
		$this->isComparison($result->getSingleChild()->getChild('b'), ComparisonOperatorNode::TYPE_EQUALS, 1);
		$this->isComparison($result->getSingleChild()->getChild('c'), ComparisonOperatorNode::TYPE_EQUALS, 2);
	}


	public function testNestedElementsOr()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', [
			'b' => [
				'c' => 1,
				'%or' => [
					'd' => 2,
					'e' => 3,
				],
			],
		]);

		$this->isPath($result, 'a');
		$this->isPath($result->getSingleChild(), 'b');
		$this->isLogical($result->getSingleChild()->getSingleChild(), LogicalOperatorNode::TYPE_AND, ['c', '%or']);
		$this->isComparison($result->getSingleChild()->getSingleChild()->getChild('c'), ComparisonOperatorNode::TYPE_EQUALS, 1);

		$or = $result->getSingleChild()->getSingleChild()->getChild('%or');
		$this->isLogical($or, LogicalOperatorNode::TYPE_OR, ['d', 'e']);
		$this->isComparison($or->getChild('d'), ComparisonOperatorNode::TYPE_EQUALS, 2);
		$this->isComparison($or->getChild('e'), ComparisonOperatorNode::TYPE_EQUALS, 3);
	}


	public function testWhereInShorthand()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', ['b' => [1, 2, 3]]);

		$this->isPath($result, 'a');
		$this->isComparison($result->getSingleChild(), ComparisonOperatorNode::TYPE_IN, [1, 2, 3]);
	}


	public function testWhereInShorthandComplex()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', ['b' => 1, 'c' => [1, 2, 3]]);

		$this->isPath($result, 'a');
		$this->isLogical($result->getSingleChild(), LogicalOperatorNode::TYPE_AND, ['b', 'c']);
		$this->isComparison($result->getSingleChild()->getChild('b'), ComparisonOperatorNode::TYPE_EQUALS, 1);
		$this->isComparison($result->getSingleChild()->getChild('c'), ComparisonOperatorNode::TYPE_IN, [1, 2, 3]);
	}


	public function testOperators()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', [
			'b' => ["%in" => [1, 2, 3]],
			'c' => ["%eq" => 1],
			'd' => ["%gt" => 2],
			'e' => ["%gte" => 3],
			'f' => ["%lt" => 4],
			'g' => ["%lte" => 5],
			'h' => ["%member_of" => 6],
			'i' => ['%not_in' => [1, 2, 3]]
		]);

		$this->isPath($result, 'a');
		$this->isLogical($result->getSingleChild(), LogicalOperatorNode::TYPE_AND, ['b', 'c', 'd', 'e', 'f', 'g', 'h']);
		$this->isComparison($result->getSingleChild()->getChild('b'), ComparisonOperatorNode::TYPE_IN, [1, 2, 3]);
		$this->isComparison($result->getSingleChild()->getChild('c'), ComparisonOperatorNode::TYPE_EQUALS, 1);
		$this->isComparison($result->getSingleChild()->getChild('d'), ComparisonOperatorNode::TYPE_GT, 2);
		$this->isComparison($result->getSingleChild()->getChild('e'), ComparisonOperatorNode::TYPE_GTE, 3);
		$this->isComparison($result->getSingleChild()->getChild('f'), ComparisonOperatorNode::TYPE_LT, 4);
		$this->isComparison($result->getSingleChild()->getChild('g'), ComparisonOperatorNode::TYPE_LTE, 5);
		$this->isComparison($result->getSingleChild()->getChild('h'), ComparisonOperatorNode::TYPE_MEMBER_OF, 6);
		$this->isComparison($result->getSingleChild()->getChild('i'), ComparisonOperatorNode::TYPE_NOT_IN, [1, 2, 3]);
	}


	public function testMultipleOperatorsOnSameField()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', [
			'b' => ["%gt" => 2, "%lt" => 10],
		]);

		$this->isPath($result, 'a');
		$this->isPath($result->getSingleChild(), 'b');
		$this->isLogical($result->getSingleChild()->getSingleChild(), LogicalOperatorNode::TYPE_AND, []);
		$this->isComparison($result->getSingleChild()->getSingleChild()->getChildren()[0], ComparisonOperatorNode::TYPE_GT, 2);
		$this->isComparison($result->getSingleChild()->getSingleChild()->getChildren()[1], ComparisonOperatorNode::TYPE_LT, 10);
	}


	public function testMultipleOperatorsOnSameFieldInsideOr()
	{
		$builder = new ConditionParser();

		$result = $builder->build('a', [
			'b' => ["%or" => ["%gt" => 2, "%lt" => 10]],
		]);

		$this->isPath($result, 'a');
		$this->isPath($result->getSingleChild(), 'b');
		$this->isLogical($result->getSingleChild()->getSingleChild(), LogicalOperatorNode::TYPE_OR, []);
		$this->isComparison($result->getSingleChild()->getSingleChild()->getChildren()[0], ComparisonOperatorNode::TYPE_GT, 2);
		$this->isComparison($result->getSingleChild()->getSingleChild()->getChildren()[1], ComparisonOperatorNode::TYPE_LT, 10);
	}


	protected function isLogical(Node $node, $type, array $children)
	{
		$this->assertInstanceOf(LogicalOperatorNode::class, $node);
		foreach ($children as $childAlias) {
			$this->assertTrue($node->hasChild($childAlias), "node \"{$node->getAlias()}\" does not have child named $childAlias");
		}
		$this->assertEquals($type, $node->getType(), "node \"{$node->getAlias()}\" is not of type \"$type\"");
	}


	protected function isComparison(Node $node, $type, $value)
	{
		$this->assertInstanceOf(ComparisonOperatorNode::class, $node);
		$this->assertEquals($value, $node->getValue());
		$this->assertEquals($type, $node->getType());
	}


	protected function isPath(Node $node, $alias)
	{
		$this->assertInstanceOf(PathNode::class, $node);
		$this->assertEquals($alias, $node->getAlias());
	}

}
