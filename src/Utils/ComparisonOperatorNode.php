<?php


namespace GrownApps\JqlBundle\Utils;


class ComparisonOperatorNode extends Node
{

	const TYPE_EQUALS = "%eq";
	const TYPE_IN = "%in";
	const TYPE_NOT_IN = "%not_in";
	const TYPE_LT = "%lt";
	const TYPE_LTE = "%lte";
	const TYPE_GT = "%gt";
	const TYPE_GTE = "%gte";
	const TYPE_LIKE = "%like";
	const TYPE_MEMBER_OF = "%member_of";
	const TYPE_IS_NULL = "%is_null";
	const TYPE_IS_NOT_NULL = "%is_not_null";

	private $type;

	private $value;


	public function __construct(string $alias, $value, $type = self::TYPE_EQUALS)
	{
		parent::__construct($alias);
		$this->type = $type;
		$this->value = $value;
	}


	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}


	/**
	 * @param mixed $type
	 */
	public function setType($type): void
	{
		$this->type = $type;
	}


	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}


	/**
	 * @param mixed $value
	 */
	public function setValue($value): void
	{
		$this->value = $value;
	}


}
