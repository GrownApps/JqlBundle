<?php

namespace GrownApps\JqlBundle;

use GrownApps\JqlBundle\DependencyInjection\JqlExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JqlBundle extends Bundle
{
	public function getContainerExtension()
	{
		return new JqlExtension();
	}

}
