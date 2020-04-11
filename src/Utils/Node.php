<?php


namespace GrownApps\JqlBundle\Utils;


use GrownApps\JqlBundle\Exceptions\NodeIsNotListException;

class Node
{

	private $alias;

	private $children;

	private $parent;


	/**
	 * SelectNode constructor.
	 *
	 * @param $alias
	 * @param array $nodes
	 * @param $modifiers
	 */
	public function __construct(string $alias, array $nodes = [])
	{
		$this->alias = $alias;
		$this->children = $nodes;
		foreach ($this->children as $childNode) {
			$childNode->setParent($this);
		}
	}


	public function isLeaf()
	{
		return count($this->children) === 0;
	}


	public function isLinkedList()
	{
		$node = $this;
		while (count($node->children) === 1) {
			$node = $node->children[0];
		}

		return $node->isLeaf();
	}


	public function getLastNode()
	{
		if (!$this->isLinkedList()) {
			throw new NodeIsNotListException();
		}
		$node = $this;
		while (count($node->children) === 1) {
			$node = $node->children[0];
		}

		return $node;
	}


	public function hasChild(string $alias)
	{
		foreach ($this->children as $node) {
			if ($node->alias == $alias) {
				return true;
			}
		}

		return false;
	}


	public function getChild(string $alias)
	{
		foreach ($this->children as $node) {
			if ($node->alias == $alias) {
				return $node;
			}
		}

		return false;
	}


	public function hasExactlyOneChild(string $alias)
	{
		$match = 0;
		foreach ($this->children as $node) {
			if ($node->alias == $alias) {
				$match++;
			}
		}

		return $match === 1;
	}


	/**
	 * @return string
	 */
	public function getAlias(): string
	{
		return $this->alias;
	}


	/**
	 * @return array
	 */
	public function getChildren(): array
	{
		return $this->children;
	}


	/**
	 * @param array $children
	 */
	public function setChildren(array $children): void
	{
		$this->children = $children;

		foreach ($this->children as $childNode) {
			$childNode->setParent($this);
		}
	}


	/**
	 * @return null
	 */
	public function getModifiers()
	{
		return $this->modifiers;
	}


	/**
	 * @param null $modifiers
	 */
	public function setModifiers($modifiers): void
	{
		$this->modifiers = $modifiers;
	}


	/**
	 * @return Node
	 */
	public function getParent(): ?Node
	{
		return $this->parent;
	}


	/**
	 * @param mixed $parent
	 */
	public function setParent($parent): void
	{
		$this->parent = $parent;
	}


	public function getPath()
	{
		$path = [];
		$parent = $this;
		while ($parent = $parent->getParent()) {
			$path[] = $parent->getAlias();
		}

		return $path;
	}


	public function getClosestParentOfType($type): ?Node
	{
		$item = $this;
		while ($item = $item->getParent()) {
			if (is_a($item, $type, true)) {
				return $item;
			}
		}

		return null;
	}

}
