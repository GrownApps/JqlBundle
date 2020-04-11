<?php


namespace GrownApps\JqlBundle\Services;


class EntityFactory
{

	public function createEntity($entityClass)
	{
		return new $entityClass;
	}
}
