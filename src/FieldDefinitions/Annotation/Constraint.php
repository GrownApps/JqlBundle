<?php

namespace JqlBundle\FieldDefinitions\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Class Constraint
 *
 * @Annotation
 * @package AppBundle\Annotation
 */
final class Constraint extends ConfigurationAnnotation
{

	/** @var string */
	private $property;

	/** @var string */
	private $value = '';


	public function getAliasName()
	{
		return "constraint";
	}


	public function allowArray()
	{
		return true;
	}


	/**
	 * @return string
	 */
	public function getProperty(): string
	{
		return $this->property;
	}


	/**
	 * @param string $property
	 */
	public function setProperty(string $property): void
	{
		$this->property = $property;
	}


	/**
	 * @return string
	 */
	public function getValue(): string
	{
		return $this->value;
	}


	/**
	 * @param string $value
	 */
	public function setValue(string $value): void
	{
		$this->value = $value;
	}




}
