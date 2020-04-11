<?php

namespace GrownApps\JqlBundle;

class Baz
{

	private $id;

	private $a;

	private $b;


	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}


	/**
	 * @param mixed $id
	 */
	public function setId($id): void
	{
		$this->id = $id;
	}



	/**
	 * @return mixed
	 */
	public function getA()
	{
		return $this->a;
	}


	/**
	 * @param mixed $a
	 */
	public function setA($a): void
	{
		$this->a = $a;
	}


	/**
	 * @return mixed
	 */
	public function getB()
	{
		return $this->b;
	}


	/**
	 * @param mixed $b
	 */
	public function setB($b): void
	{
		$this->b = $b;
	}






}
