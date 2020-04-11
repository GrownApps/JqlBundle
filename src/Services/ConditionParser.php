<?php


namespace GrownApps\JqlBundle\Services;


use GrownApps\JqlBundle\Exceptions\NodesShouldBeArray;
use GrownApps\JqlBundle\Utils\ComparisonOperatorNode;
use GrownApps\JqlBundle\Utils\JqlHelper;
use GrownApps\JqlBundle\Utils\LogicalOperatorNode;
use GrownApps\JqlBundle\Utils\Node;
use GrownApps\JqlBundle\Utils\PathNode;

class ConditionParser
{


	public function build($alias, array $input): Node
	{
		if (count($input) === 1) {
			$value = reset($input);
			$key = key($input);
			if ($this->isLogical($key)) {
				$node = new PathNode($alias, $this->createLogical($value, $key));
			} elseif ($this->isComparison($key)) {
				$node = $this->createComparsion($alias, $value, $key);
			} else {
				$node = new PathNode($alias, $this->buildSingleChild($input));
			}
		} else {
			$node = new PathNode($alias, $this->createLogical($input));
		}

		return $node;

	}


	private function buildSingleChild(array $singleChild)
	{
		$value = reset($singleChild);
		$key = key($singleChild);

		if (is_array($value)) {
			if (JqlHelper::isAssoc($value)) {
				return $this->build($key, $value);
			} else {
				//special shortcut for creating WHERE IN condition (non assoc array can be passed as value directly)
				return $this->createComparsion($key, $value, ComparisonOperatorNode::TYPE_IN);
			}
		} else {
			return $this->createComparsion($key, $value);
		}
	}


	private function createLogical(array $input, $type = LogicalOperatorNode::TYPE_AND)
	{
		$children = [];
		foreach ($input as $key => $value) {
			//decide type of children
			if (is_array($value) && JqlHelper::isAssoc($value)) {
				if ($this->isLogical($key)) {
					$children[] = $this->createLogical($value, $key);
				} else {
					$children[] = $this->build($key, $value);
				}
			} else {
				if ($this->isComparison($key)) {
					$children[] = $this->createComparsion("", $value, $key);
				} else {
					$children[] = $this->createComparsion($key, $value, is_array($value) ? ComparisonOperatorNode::TYPE_IN : ComparisonOperatorNode::TYPE_EQUALS);
				}
			}

		}

		return new LogicalOperatorNode($children, $type);
	}


	private function createComparsion($alias, $value, $type = ComparisonOperatorNode::TYPE_EQUALS): Node
	{
		return new ComparisonOperatorNode($alias, $value, $type);

	}


	/**
	 * @param $key
	 * @return bool
	 */
	private function isLogical($key): bool
	{
		return in_array($key, [LogicalOperatorNode::TYPE_OR, LogicalOperatorNode::TYPE_AND]);
	}


	/**
	 * @param $key
	 * @return bool
	 */
	private function isComparison($key): bool
	{
		return in_array($key, [
			ComparisonOperatorNode::TYPE_EQUALS,
			ComparisonOperatorNode::TYPE_LIKE,
			ComparisonOperatorNode::TYPE_IN,
			ComparisonOperatorNode::TYPE_NOT_IN,
			ComparisonOperatorNode::TYPE_GT,
			ComparisonOperatorNode::TYPE_GTE,
			ComparisonOperatorNode::TYPE_LT,
			ComparisonOperatorNode::TYPE_LTE,
			ComparisonOperatorNode::TYPE_MEMBER_OF,
			ComparisonOperatorNode::TYPE_IS_NULL,
			ComparisonOperatorNode::TYPE_IS_NOT_NULL,
		]);
	}



}
