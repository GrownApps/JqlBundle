<?php


namespace GrownApps\JqlBundle\Security;


use Doctrine\ORM\QueryBuilder;

class DummySecurityProvider implements ISecurityProvider
{
	public function applySecurity(QueryBuilder $qb)
	{
		return $qb;
	}

}
