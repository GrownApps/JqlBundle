<?php declare(strict_types=1);

namespace GrownApps\JqlBundle\Exceptions;

/**
 * Class HookException
 *
 * @package GrownApps\JqlBundle\Exceptions
 */
class HookException extends \RuntimeException
{

	public static function invalidClassProvided(string $expected, $actual): HookException
	{
		$actualType = is_object($actual) ? get_class($actual) : gettype($actual);
		$message = sprintf('Expected object of class %s, %s given', $expected, $actualType);

		return new self($message);
	}


	public static function conflict(string $hookName): HookException
	{
		return new self("The hook with same name '{$hookName}' is already registered");
	}


	public static function notFound(string $name): HookException
	{
		return new self("The hook with name '{$name}' not found. Did you register the hook as a service?");
	}

	public static function fieldNotPresent(string $fieldName): HookException
	{
		return new self("The hook data field with name '{$fieldName}' not found.");
	}
}
