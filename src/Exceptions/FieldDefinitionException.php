<?php


namespace GrownApps\JqlBundle\Exceptions;


class FieldDefinitionException extends \RuntimeException
{

	public static function entityNotFound($className)
	{
		throw new self("Entity - {$className} - does not have field definition");
	}


	public static function fieldNotFound($property)
	{
		throw new self("Field - {$property} - does not have field definition");
	}


	public static function fieldIsNotAssociation($className, $property)
	{
		throw new self("Field - {$className}::{$property} is not an association");
	}

	public static function fieldIsNotNullable($className, $property) {
		throw new self("Field - {$className}::{$property} cannot be NULL");
	}
}
