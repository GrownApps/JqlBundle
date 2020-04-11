<?php

namespace GrownApps\JqlBundle\FieldDefinitions\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Class ConditionalCodelist
 * @Annotation
 * @package AppBundle\Annotation
 */
 class ConditionalCodelist extends ConfigurationAnnotation
{

	/**
	 * Returns the alias name for an annotated configuration.
	 *
	 * @return string
	 */
	public function getAliasName()
	{
		return 'ConditionalCodelist';
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
