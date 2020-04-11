<?php


namespace JqlBundle\FieldDefinitions;


class InMemoryCacheProvider implements ICacheProvider
{
	private $valid = false;

	private $data;


	public function getFieldsDefinitions()
	{
		return $this->valid ? $this->data : null;
	}


	public function invalidate()
	{
		$this->valid = false;
	}


	public function isValid()
	{
		return $this->valid;
	}


	public function setFieldDefinitions($data)
	{
		$this->data = $data;
		$this->valid = true;
	}


}
