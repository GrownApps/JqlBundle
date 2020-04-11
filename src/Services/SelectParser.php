<?php


namespace GrownApps\JqlBundle\Services;


use GrownApps\JqlBundle\Exceptions\NodesShouldBeArray;
use GrownApps\JqlBundle\Utils\Node;

class SelectParser
{

	public function buildSelect($input)
	{
		$result = null;
		if (is_string($input)) {
			$result = $this->parsePath($input);
		} elseif (is_array($input)) {
			if ($this->isAssoc($input)) {
				$result = $this->parseObject($input);
			} else {
				return $this->normalizeCollection($this->parseCollection($input));
			}
		}

		return $this->normalize($result);
	}


	private function parseObject($object): Node
	{
		$nodes = reset($object);
		$alias = key($object);
		if (!is_array($nodes)) {
			throw new NodesShouldBeArray();
		}
		if (strpos($alias, '.') === false) {
			$node = new Node($alias);
			$node->setChildren($this->parseCollection($nodes));

			return $node;
		} else {
			$path = $this->parsePath($alias);
			$path->getLastNode()->setChildren($this->parseCollection($nodes));

			return $path;
		}
	}


	private function parsePath($path): Node
	{
		if (!$path) {
			throw new NodesShouldBeArray("Empty path given: {$path}");
		}
		//optimize explode->implode
		$arrayPath = explode('.', $path);
		$alias = array_shift($arrayPath);
		$node = new Node($alias);
		if (count($arrayPath) > 0) {
			$node->setChildren([$this->parsePath(implode('.', $arrayPath))]);
		}

		return $node;
	}


	private function parseCollection(array $rawNodes): array
	{
		$result = [];
		foreach ($rawNodes as $rawNode) {
			if (is_string($rawNode)) {
				$result[] = $this->parsePath($rawNode);
			} elseif (is_array($rawNode)) {
				$result[] = $this->parseObject($rawNode);
			}
		}

		return $result;
	}


	private function normalizeCollection(array $input)
	{
		$result = [];
		//merge
		foreach ($input as $node) /** @var $node Node */ {
			if (array_key_exists($node->getAlias(), $result)) {
				$result[$node->getAlias()]->setChildren(array_merge($result[$node->getAlias()]->getChildren(), $node->getChildren()));
			} else {
				$result[$node->getAlias()] = $node;
			}
		}

		foreach ($result as $node) {
			$this->normalize($node);
		}

		return array_values($result);
	}


	private function normalize(Node $input)
	{
		$input->setChildren($this->normalizeCollection($input->getChildren()));

		return $input;
	}


	private function isAssoc(array $arr)
	{
		if ([] === $arr) {
			return false;
		}

		return array_keys($arr) !== range(0, count($arr) - 1);
	}


}
