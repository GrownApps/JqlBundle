<?php

namespace JqlBundle;

use Doctrine\Common\Collections\ArrayCollection;

class Bar
{

	private $id;

	private $a;

	private $b;

	private $foo;

	private $foos;

	private $secondFoo;


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
	 * Bar constructor.
	 */
	public function __construct()
	{
		$this->foos = new ArrayCollection();
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


	/**
	 * @return mixed
	 */
	public function getFoo()
	{
		return $this->foo;
	}


	/**
	 * @param mixed $foo
	 */
	public function setFoo($foo): void
	{
		$this->foo = $foo;
	}


	public function getFoos(): ArrayCollection
	{
		return $this->foos;
	}


	public function addFoo(Foo $foo)
	{
		$this->foos->add($foo);
	}


	public function removeFoo(Foo $foo)
	{
		$this->foos->removeElement($foo);
	}


	/**
	 * @return mixed
	 */
	public function getSecondFoo()
	{
		return $this->secondFoo;
	}


	/**
	 * @param mixed $secondFoo
	 */
	public function setSecondFoo($secondFoo): void
	{
		$this->secondFoo = $secondFoo;
	}


}
