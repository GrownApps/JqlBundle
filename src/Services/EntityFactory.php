<?php


namespace JqlBundle\Services;


class EntityFactory
{

	public function createEntity($entityClass)
	{
		return new $entityClass;
	}
}
