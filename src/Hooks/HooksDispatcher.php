<?php declare(strict_types=1);

namespace GrownApps\JqlBundle\Hooks;

use GrownApps\JqlBundle\Exceptions\HookException;
use GrownApps\JqlBundle\Hooks\ValueObjects\HookContext;
use GrownApps\JqlBundle\Hooks\ValueObjects\HooksTreeNode;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class HooksDispatcher
 *
 * @package GrownApps\JqlBundle\Services
 */
class HooksDispatcher
{

	/** @var JqlHookInterface[] */
	private $hooks = [];

	/** @var array $eventHooksTree */
	private $eventHooksTree = [];


	/**
	 * HooksDispatcher constructor.
	 *
	 * @param array $hooks
	 * @param RequestStack $requestStack
	 */
	public function __construct(iterable $hooks, RequestStack $requestStack)
	{
		foreach ($hooks as $hook) {
			if (!($hook instanceof JqlHookInterface)) {
				throw HookException::invalidClassProvided(JqlHookInterface::class, $hook);
			}

			if (array_key_exists($hook->getName(), $this->hooks)) {
				throw HookException::conflict($hook->getName());
			}

			$this->hooks[$hook->getName()] = $hook;
		}

		$request = $requestStack->getCurrentRequest();
		if ($request !== null) {
			$currentRequestHooks = $this->getCurrentRequestHooks($request);
			$this->buildEventHooksTree($currentRequestHooks);
		}
	}


	/**
	 * Dispatches an event to all registered hooks in current request.
	 *
	 * @param string $eventName
	 * @param Event $event
	 */
	public function dispatch(string $eventName, Event $event): void
	{
		if (!array_key_exists($eventName, $this->eventHooksTree)) {
			return;
		}

		foreach ($this->eventHooksTree[$eventName] as $hookNode) {
			/** @var HooksTreeNode $hookNode */
			$callback = $hookNode->getCallback();
			$metadata = $hookNode->getMetadata();

			$callback($event, $metadata, $eventName, $this);
		}
	}

	/**
	 * @param array $currentRequestHooks
	 */
	private function buildEventHooksTree(array $currentRequestHooks): void
	{
		foreach ($currentRequestHooks as $currentRequestHook) {
			/** @var HookContext $currentRequestHook */
			$hook = $this->getHook($currentRequestHook->getName());
			foreach ($hook->getSubscribedEvents() as $eventName => $method) {
				$hooksTreeNode = HooksTreeNode::create($hook, $method, $currentRequestHook->getMetadata());
				$this->addHook($eventName, $hooksTreeNode);
			}
		}
	}

	/**
	 * @param string $name
	 * @return JqlHookInterface
	 */
	private function getHook(string $name): JqlHookInterface
	{
		if (!array_key_exists($name, $this->hooks)) {
			throw HookException::notFound($name);
		}

		return $this->hooks[$name];
	}

	/**
	 * @param string $eventName
	 * @param HooksTreeNode $hooksTreeNode
	 */
	private function addHook(string $eventName, HooksTreeNode $hooksTreeNode): void
	{
		$this->eventHooksTree[$eventName][] = $hooksTreeNode;
	}

	/**
	 * @param Request $request
	 * @return HookContext[]
	 */
	private function getCurrentRequestHooks(Request $request): array
	{
		$hooks = $request->get('hooks', []);
		$hookObjects = [];
		foreach ($hooks as $hook) {
			$hookObjects[] = HookContext::create(
				is_array($hook) ? $this->getHookDataField('name', $hook) : $hook,
				is_array($hook) ? $this->getHookDataField('meta', $hook) : []
			);
		}

		return $hookObjects;
	}

	/**
	 * @param string $fieldName
	 * @param array $hookData
	 * @return mixed
	 */
	private function getHookDataField(string $fieldName, array $hookData)
	{
		if (!array_key_exists($fieldName, $hookData)) {
			throw HookException::fieldNotPresent($fieldName);
		}

		return $hookData[$fieldName];
	}
}
