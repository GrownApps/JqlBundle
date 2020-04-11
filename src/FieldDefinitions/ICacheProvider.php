<?php

namespace GrownApps\JqlBundle\FieldDefinitions;

/**
 * Interface IFieldsDefinitionProvider
 *
 * @package AppBundle\Services\Acl
 */
interface ICacheProvider
{

	/**
	 * Get field definitions from field annotations
	 *
	 * @return array
	 */
	public function getFieldsDefinitions();

	/**
	 * Use this method to invalidate cache if any is used.
	 *
	 * @return mixed
	 */
	public function invalidate();


	/**
	 * Use this method to check if cache is valid
	 *
	 * @return mixed
	 */
	public function isValid();


	public function setFieldDefinitions($data);


}
