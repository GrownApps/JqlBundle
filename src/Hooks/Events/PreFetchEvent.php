<?php declare(strict_types=1);

namespace GrownApps\JqlBundle\Hooks\Events;


use Symfony\Contracts\EventDispatcher\Event;

class PreFetchEvent extends Event
{

	/** @var string */
	private $shortName;

	/** @var int */
	private $id;

	/** @var array */
	private $data;


	public function __construct(string $shortName, int $id, array $data)
	{
		$this->shortName = $shortName;
		$this->id = $id;
		$this->data = $data;
	}


	/**
	 * @return string
	 */
	public function getShortName(): string
	{
		return $this->shortName;
	}


	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}


	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}
}
