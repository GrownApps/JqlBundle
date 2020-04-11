<?php declare(strict_types=1);

namespace GrownApps\JqlBundle\Hooks\Events;

use Symfony\Component\EventDispatcher\Event;

class PostDeleteEvent extends Event
{

	/** @var mixed */
	private $entity;

	/** @var array */
	private $data;


	public function __construct($entity, array $data)
	{
		$this->entity = $entity;
		$this->data = $data;
	}


	/**
	 * @return mixed
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}
}
