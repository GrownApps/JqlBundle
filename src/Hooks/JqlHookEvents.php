<?php declare(strict_types=1);

namespace JqlBundle\Hooks;

/**
 * Class JqlHookEvents
 *
 * @package JqlBundle\Hooks
 */
final class JqlHookEvents
{

	public const PRE_FETCH = 'jql.events.pre-fetch';
	public const PRE_FLUSH = 'jql.events.pre-flush';

	public const POST_FLUSH = 'jql.events.post-flush';
	public const POST_DELETE = 'jql.events.post-delete';
}
