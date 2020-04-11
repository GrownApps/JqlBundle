<?php declare(strict_types=1);

namespace GrownApps\JqlBundle\Hooks\ValueObjects;

use GrownApps\JqlBundle\Hooks\JqlHookInterface;

/**
 * Class HooksTreeNode
 *
 * @package GrownApps\JqlBundle\Hooks
 */
class HooksTreeNode
{
	/** @var JqlHookInterface $hook */
	private $hook;

	/** @var string $method */
	private $method;

	/** @var array $metadata */
	private $metadata;

	/**
	 * HooksTreeNode constructor.
	 *
	 * @param JqlHookInterface $hook
	 * @param string $method
	 * @param array $metadata
	 */
	private function __construct(JqlHookInterface $hook, string $method, array $metadata)
	{
		$this->hook = $hook;
		$this->method = $method;
		$this->metadata = $metadata;
	}

	/**
	 * @param JqlHookInterface $hook
	 * @param string $method
	 * @param array $metadata
	 * @return HooksTreeNode
	 */
	public static function create(JqlHookInterface $hook, string $method, array $metadata): HooksTreeNode
	{
		return new self($hook, $method, $metadata);
	}

	/**
	 * @return array
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
	}

	/**
	 * @return array
	 */
	public function getCallback(): array
	{
		return [$this->hook, $this->method];
	}
}
