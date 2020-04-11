<?php


namespace GrownApps\JqlBundle\Utils;


class PathNode extends Node
{

	public function __construct(string $alias, Node $child)
	{
		parent::__construct($alias, [$child]);
	}


	public function getSingleChild(): Node
	{
		return $this->getChildren()[0];
	}


}
