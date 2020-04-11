<?php

namespace GrownApps\JqlBundle;

use Doctrine\Common\Collections\ArrayCollection;

class Foo
{

	private $id;

	private $a;

	private $b;

	private $bar;

	private $bars;

	private $baz;

	private $secondBars;



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
	 * Foo constructor.
	 */
	public function __construct()
	{
		$this->bars = new ArrayCollection();
		$this->secondBars = new ArrayCollection();
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
	public function getBar()
	{
		return $this->bar;
	}


	/**
	 * @param mixed $bar
	 */
	public function setBar($bar): void
	{
		$this->bar = $bar;
	}


	/**
	 * @return mixed
	 */
	public function getRab()
	{
		return $this->rab;
	}


	/**
	 * @return ArrayCollection
	 */
	public function getBars(): ArrayCollection
	{
		return $this->bars;
	}


	/**
	 * @return ArrayCollection
	 */
	public function getSecondBars(): ArrayCollection
	{
		return $this->secondBars;
	}


	/**
	 * @param mixed $rab
	 */
	public function setRab($rab): void
	{
		$this->rab = $rab;
	}


	public function addBar(Bar $bar)
	{
		$this->bars->add($bar);
	}


	public function removeBar(Bar $bar)
	{
		$this->bars->removeElement($bar);
	}

	public function addSecondBar(Bar $bar)
	{
		$this->secondBars->add($bar);
	}


	public function removeSecondBar(Bar $bar)
	{
		$this->secondBars->removeElement($bar);
	}


	/**
	 * @return mixed
	 */
	public function getBaz(): Baz
	{
		return $this->baz;
	}


	/**
	 * @param mixed $baz
	 */
	public function setBaz(Baz $baz): void
	{
		$this->baz = $baz;
	}




}
