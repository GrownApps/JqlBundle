<?php


namespace GrownApps\JqlBundle\Exceptions;


class EntityNotFoundException extends \RuntimeException
{

	public static function entityNotFound($className, $id)
	{
		throw new self("Entity: {$className} with id: {$id} was not found");
	}


	public static function someOfTheIdsWereNotFound($className, array $value)
	{
		$value = implode(", ", $value);
		throw new self("Entity: {$className}: some of the IDs {$value} ware not found");

	}

}
