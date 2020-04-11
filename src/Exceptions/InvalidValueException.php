<?php declare(strict_types=1);

namespace GrownApps\JqlBundle\Exceptions;

/**
 * Class InvalidValueException
 *
 * @package GrownApps\JqlBundle\Exceptions
 */
class InvalidValueException extends \RuntimeException
{

	public static function expectedArray($value, $fieldName, $className): InvalidValueException
	{
		return new self("Expecting array when setting - {$className}:{$fieldName} - got {$value}");
	}
}
