<?php declare(strict_types=1);

namespace JqlBundle\Utils;

/**
 * Class JqlHelper
 *
 * @package JqlBundle\Utils
 */
class JqlHelper
{
	public static function extractFieldListFromRequest($dataObject): array
	{
		$result = [];

		foreach ($dataObject as $key => $value) {
			self::visitNode($value, $key, $result);
		}

		return $result;
	}


	private static function visitNode($node, $path, &$acc): void
	{
		if (!in_array($path, $acc)) {
			$acc[] = $path;
		}
		if (is_array($node)) {
			foreach ($node as $key => $value) {
				self::visitNode($value, self::isAssoc($node) ? $path . "." . $key : $path, $acc);
			}
		}
	}


	public static function isAssoc(array $arr): bool
	{
		if ([] === $arr) {
			return false;
		}

		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}
