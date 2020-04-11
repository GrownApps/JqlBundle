<?php

namespace GrownApps\JqlBundle\Services;

use GrownApps\JqlBundle\Exceptions\NodesShouldBeArray;
use GrownApps\JqlBundle\Services\SelectParser;
use PHPUnit\Framework\TestCase;

class SelectParserTest extends TestCase
{

	public function testSingleElement()
	{
		$selectBuilder = new SelectParser();

		$results = [];
		$results[] = $selectBuilder->buildSelect('a');
		$results[] = $selectBuilder->buildSelect(json_decode('{"a": []}', true));

		foreach ($results as $i => $result) {
			$this->assertEquals('a', $result->getAlias(), $i);
			$this->assertEquals([], $result->getChildren(), $i);
		}
	}


	public function testSingleChild()
	{
		$selectBuilder = new SelectParser();

		$results = [];
		$results[] = $selectBuilder->buildSelect('a.b');
		$results[] = $selectBuilder->buildSelect(json_decode('{"a": ["b"]}', true));
		$results["Composite key"] = $selectBuilder->buildSelect(json_decode('{"a.b": []}', true));

		foreach ($results as $i => $result) {
			$this->assertEquals('a', $result->getAlias(), $i);
			$this->assertTrue($result->hasChild('b'), $i);
		}
	}


	public function testTwoChildren()
	{
		$selectBuilder = new SelectParser();

		$results = [];
		$results[] = $selectBuilder->buildSelect(json_decode('{"a": ["b", "c"]}', true));

		foreach ($results as $i => $node) {
			$this->assertEquals('a', $node->getAlias(), $i);
			$this->assertTrue($node->hasChild('b'), $i);
			$this->assertTrue($node->hasChild('c'), $i);
		}
	}


	public function testTwoLevel()
	{
		$selectBuilder = new SelectParser();

		$results = [];
		$results[] = $selectBuilder->buildSelect('a.b.c');
		$results[] = $selectBuilder->buildSelect(json_decode('{"a": [{"b": ["c"]}]}', true));
		$results["Composite: Path inside object"] = $selectBuilder->buildSelect(json_decode('{"a": ["b.c"]}', true));
		$results["Composite: Path as key"] = $selectBuilder->buildSelect(json_decode('{"a.b": ["c"]}', true));

		foreach ($results as $i => $node) {
			$this->assertEquals('a', $node->getAlias(), $i);
			$this->assertTrue($node->hasChild('b'), $i);
			$this->assertTrue($node->getChild('b')->hasChild('c'), $i);
		}
	}


	/*
	 * A - AA - AAA
	 *        - AAB
	 *   - AB - ABA
	 *        - ABB
	 */
	public function testComplexTree()
	{
		$selectBuilder = new SelectParser();
		$results = [];

		$results[] = $selectBuilder->buildSelect(json_decode('{"A": [{"AA": ["AAA", "AAB"]}, {"AB": ["ABA", "ABB"]}]}', true));

		foreach ($results as $i => $node) {
			$this->assertEquals('A', $node->getAlias(), $i);
			$this->assertTrue($node->hasChild('AA'), $i);
			$this->assertTrue($node->hasChild('AB'), $i);
			$this->assertTrue($node->getChild('AA')->hasChild('AAA'), $i);
			$this->assertTrue($node->getChild('AA')->hasChild('AAB'), $i);
			$this->assertTrue($node->getChild('AB')->hasChild('ABA'), $i);
			$this->assertTrue($node->getChild('AB')->hasChild('ABB'), $i);
		}
	}


	public function testSameNodeSimple()
	{
		$selectBuilder = new SelectParser();

		$results = [];
		$results[] = $selectBuilder->buildSelect(["a", "a"]);
		$results[] = $selectBuilder->buildSelect(["a", "a", "a"]);

		foreach ($results as $i => $result) {
			$this->assertEquals('a', $result[0]->getAlias(), $i);
			$this->assertEquals(1, count($result), $i);
		}
	}


	public function testSameNodeSecondLevel()
	{
		$selectBuilder = new SelectParser();

		$results = [];
		$results[] = $selectBuilder->buildSelect(["a" => ["b", "b"]]);
		$results[] = $selectBuilder->buildSelect(["a" => ["b.c", "b.d"]]);

		foreach ($results as $i => $result) {
			$this->assertEquals('a', $result->getAlias(), $i);
			$this->assertTrue($result->hasExactlyOneChild('b'), $i);
			$this->assertEquals(1, count($result->getChildren()), $i);
		}
	}


	/*
	 * a - b - c - d

	 */
	public function testSameNodeThirdLevel()
	{
		$selectBuilder = new SelectParser();

		$results = [];
		$results[] = $selectBuilder->buildSelect([
			"a" => [
				"b.c.d",
				['b.c' => ['d']],
				['b' => ['c.d']],
				["b" => [["c" => ['d']]]],
			],
		]);

		foreach ($results as $i => $result) {
			$this->assertEquals('a', $result->getAlias(), $i);
			$this->assertTrue($result->hasExactlyOneChild('b'), $i);
			$this->assertTrue($result->getChild('b')->hasExactlyOneChild('c'), $i);
			$this->assertTrue($result->getChild('b')->getChild('c')->hasExactlyOneChild('d'), $i);
			$this->assertEquals(1, count($result->getChildren()), $i);
			$this->assertEquals(1, count($result->getChild('b')->getChildren()), $i);
			$this->assertEquals(1, count($result->getChild('b')->getChild('c')->getChildren()), $i);
		}
	}


	public function testEmptyInput_fromPath()
	{
		//TODO test if exception indicates where is the error

		$this->expectException(NodesShouldBeArray::class);

		$selectBuilder = new SelectParser();
		$selectBuilder->buildSelect("");
	}


	public function testEmptyInput_fromFromObject()
	{
		//TODO test if exception indicates where is the error

		$this->expectException(NodesShouldBeArray::class);

		$selectBuilder = new SelectParser();
		$json = json_decode('{"a": "b"}', true);
		$selectBuilder->buildSelect($json);
	}

}
