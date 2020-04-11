<?php

namespace GrownApps\JqlBundle\Utils;

use GrownApps\JqlBundle\Exceptions\NodeIsNotListException;
use PHPUnit\Framework\TestCase;

class NodeTest extends TestCase
{

	public function testIsLinkedList()
	{
		$single = new Node('a', []);
		$this->assertTrue($single->isLinkedList());

		$simpleList = new Node('b', [$single]);
		$this->assertTrue($simpleList->isLinkedList());

		$notList = new Node('b', [$single, $single]);
		$this->assertFalse($notList->isLinkedList());

	}


	public function testIsLeaf()
	{
		$single = new Node('a', []);
		$this->assertTrue($single->isLeaf());

		$simpleList = new Node('b', [$single]);
		$this->assertFalse($simpleList->isLeaf());
	}


	public function testGetLastNode_isList()
	{
		$single = new Node('a', []);
		$this->assertSame($single, $single->getLastNode());

		$simpleList = new Node('b', [$single]);
		$this->assertSame($single, $simpleList->getLastNode());
	}


	public function testGetLastNode_isNotList()
	{
		$this->expectException(NodeIsNotListException::class);
		$single = new Node('a', []);
		$notList = new Node('b', [$single, $single]);

		$notList->getLastNode();
	}


	public function testHasChild()
	{
		$node = new Node('a', [new Node('b')]);
		$this->assertTrue($node->hasChild('b'));
		$this->assertFalse($node->hasChild('c'));
	}


	public function testGetChild()
	{
		$b = new Node('b');
		$node = new Node('a', [$b]);
		$this->assertSame($b, $node->getChild('b'));
		$this->assertFalse($node->getChild('c'));
	}


	public function testHasExactlyOneChild()
	{
		$node = new Node('a', [new Node('b'), new Node('c'), new Node('c')]);
		$this->assertTrue($node->hasExactlyOneChild('b'));
		$this->assertFalse($node->hasExactlyOneChild('c'));
		$this->assertFalse($node->hasExactlyOneChild('d'));
	}


	public function testHasParent_constructor()
	{
		$child = new Node('b');
		$parent = new Node('a', [$child]);

		$this->assertSame($parent, $child->getParent());
	}


	public function testHasParent_setChildren()
	{
		$child = new Node('b');
		$parent = new Node('a');

		$parent->setChildren([$child]);

		$this->assertSame($parent, $child->getParent());
	}


	public function testGetPath()
	{
		$c = new Node('c');
		$b = new Node('b', [$c]);
		$a = new Node('a', [$b]);

		$this->assertSame(['b', 'a'], $c->getPath());
	}


	public function testFindParentOfType()
	{
		$c = new Node('c');
		$b = new Node('b', [$c]);
		$a = new PathNode('a', $b);

		$this->assertSame($a, $c->getClosestParentOfType(PathNode::class));
	}


	public function testFindParentOfType_null()
	{
		$c = new Node('c');
		$b = new Node('b', [$c]);
		$a = new LogicalOperatorNode([$b]);

		$this->assertSame(null, $c->getClosestParentOfType(PathNode::class));
	}


	public function testFindParentOfType_self()
	{
		$c = new LogicalOperatorNode([]);
		$b = new LogicalOperatorNode([$c]);
		$a = new LogicalOperatorNode([$b]);

		$this->assertSame($b, $c->getClosestParentOfType(LogicalOperatorNode::class));
	}
}
