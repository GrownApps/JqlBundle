<?php


namespace GrownApps\JqlBundle\Security;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

interface ISecurityProvider
{
	/**
	 * @param QueryBuilder $qb
	 * @throws AccessDeniedException
	 * @return mixed
	 */
	public function applySecurity(QueryBuilder $qb);

}
