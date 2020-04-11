<?php declare(strict_types=1);

namespace JqlBundle\Hooks;

/**
 * Interface JqlHookInterface
 *
 * @package JqlBundle\Utils
 */
interface JqlHookInterface
{

	/**
	 * Returns an array of event names this hooks wants to listen to.
	 *
	 * The array keys are event names and the value can is the method name to call.
	 *
	 * For instance:
	 *
	 *  * ['eventName' => 'methodName']
	 *
	 * @return array The event names to listen to
	 */
	public function getSubscribedEvents(): array;


	/**
	 * Use the name as hook identification. Should be unique across all registered hooks.
	 *
	 * @return string
	 */
	public function getName(): string;

}
