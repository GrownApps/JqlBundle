<?php


namespace JqlBundle\Utils;


class LogicalOperatorNode extends Node
{

	const TYPE_OR = "%or";
	const TYPE_AND = "%and";

	private $type;


	public function __construct(array $nodes = [], $type = self::TYPE_AND)
	{
		parent::__construct($type, $nodes);
		$this->type = $type;
	}


	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}


	/**
	 * @param string $type
	 */
	public function setType(string $type): void
	{
		$this->type = $type;
	}


}
