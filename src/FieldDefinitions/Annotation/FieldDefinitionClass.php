<?php

namespace GrownApps\JqlBundle\FieldDefinitions\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Class FieldDefinitionClass
 * @Annotation
 * @package AppBundle\Annotation
 *
 * @todo use doctrine annotations directly
 */
final class FieldDefinitionClass extends ConfigurationAnnotation
{

	/** @var string */
	private $name;

	private $toStringTemplate;


	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @param mixed $name
	 */
	public function setName($name): void
	{
		$this->name = $name;
	}


	/**
	 * @return mixed
	 */
	public function getToStringTemplate()
	{
		return $this->toStringTemplate;
	}


	/**
	 * @param mixed $toStringTemplate
	 */
	public function setToStringTemplate($toStringTemplate): void
	{
		$this->toStringTemplate = $toStringTemplate;
	}




	/**
	 * Returns the alias name for an annotated configuration.
	 *
	 * @return string
	 */
	public function getAliasName()
	{
		return 'FieldDefinitionClass';
	}

	/**
	 * Returns whether multiple annotations of this type are allowed.
	 *
	 * @return bool
	 */
	public function allowArray()
	{
		return false;
	}
}
