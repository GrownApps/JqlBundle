<?php

namespace GrownApps\JqlBundle\Utils;

use PHPUnit\Framework\TestCase;

class JqlHelperTest extends TestCase
{

	/**
	 * @dataProvider extractFieldlistFromRequestDataProvider
	 */
	public function testExtractFieldListFromRequest($request, $expected)
	{

		$result = JqlHelper::extractFieldListFromRequest($request);

		$this->assertEquals($expected, $result);
	}


	public function extractFieldlistFromRequestDataProvider()
	{
		return [
			[['name' => '123'], ['name']],
			[['name' => '123', 'foo' => '123'], ['name', 'foo']],
			[['name' => '123', 'foo' => ['name' => '123']], ['name', 'foo', 'foo.name']],
			[['foo' => ['bar' => ['name' => '123'],]], ['foo', 'foo.bar', 'foo.bar.name']],
			[['name' => '123', 'foo' => [['name' => '123'], ['name' => '456']]], ['name', 'foo', 'foo.name']],
			[['foo.name' => '123'], ['foo.name']],
			[['foo.bar' => ['a' => '123', 'b' => '123']], ['foo.bar', 'foo.bar.a', 'foo.bar.b']],
		];
	}
}
