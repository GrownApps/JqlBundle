<?php declare(strict_types=1);

namespace JqlBundle\Hooks\ValueObjects;

/**
 * Class HookContext
 *
 * @package JqlBundle\Hooks
 */
class HookContext
{
	/** @var string $name */
	private $name;

	/** @var array $metadata */
	private $metadata;

	/**
	 * HookContext constructor.
	 *
	 * @param string $name
	 * @param array $metadata
	 */
	private function __construct(string $name, array $metadata)
	{
		$this->name = $name;
		$this->metadata = $metadata;
	}

	/**
	 * @param string $name
	 * @param array $metadata
	 * @return HookContext
	 */
	public static function create(string $name, array $metadata = []): HookContext
	{
		return new self($name, $metadata);
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
	}
}
