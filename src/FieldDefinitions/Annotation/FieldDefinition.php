<?php

namespace JqlBundle\FieldDefinitions\Annotation;

use AppBundle\Services\Acl\Constants\Sections;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Class FieldDefinition
 *
 * @Annotation
 * @package AppBundle\Annotation
 */
final class FieldDefinition extends ConfigurationAnnotation
{

	/** @var string */
	private $name;

	/** @var string */
	private $title = '';

	/** @var string */
	private $type;

	/** @var string */
	private $section = Sections::SECTION_DEFAULT;

	/** @var string */
	private $description = '';

	/** @var bool */
	private $skipProcessing = false;

	/** @var bool */
	private $hideInUI = false;

	/** @var  string */
	private $toString;

	/** @var array */
	private $dependencies = [];

	/** @var array<Constraint> */
	private $constraints = [];


	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}


	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}


	/**
	 * @return string
	 */
	public function getSection()
	{
		return $this->section;
	}


	/**
	 * @param string $section
	 */
	public function setSection($section)
	{
		$this->section = $section;
	}


	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}


	/**
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}


	/**
	 * @return boolean
	 */
	public function isSkippedProcessing()
	{
		return $this->skipProcessing;
	}


	/**
	 * @param boolean $skipProcessing
	 */
	public function setSkipProcessing($skipProcessing)
	{
		$this->skipProcessing = (bool)$skipProcessing;
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}


	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}


	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}


	/**
	 * @return string
	 */
	public function getDependencies()
	{
		return $this->dependencies;
	}


	/**
	 * @param string $dependencies
	 */
	public function setDependencies($dependencies)
	{
		$this->dependencies = $dependencies;
	}


	/**
	 * @return string
	 */
	public function getToString()
	{
		return $this->toString;
	}


	/**
	 * @param string $toString
	 */
	public function setToString($toString)
	{
		$this->toString = $toString;
	}


	/**
	 * Returns the alias name for an annotated configuration.
	 *
	 * @return string
	 */
	public function getAliasName()
	{
		return 'FieldDefinition';
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


	/**
	 * @return boolean
	 */
	public function isHideInUI()
	{
		return $this->hideInUI;
	}


	/**
	 * @param boolean $hideInUI
	 */
	public function setHideInUI($hideInUI)
	{
		$this->hideInUI = $hideInUI;
	}


	/**
	 * @return array
	 */
	public function getConstraints(): array
	{
		return $this->constraints;
	}


	/**
	 * @param array $constraints
	 */
	public function setConstraints(array $constraints): void
	{
		$this->constraints = $constraints;
	}

}
