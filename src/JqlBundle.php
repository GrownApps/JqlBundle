<?php

namespace GrownApps\JqlBundle;

use GrownApps\JqlBundle\DependencyInjection\JqlExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JqlBundle extends Bundle
{
	protected function getContainerExtensionClass()
	{
		return new JqlExtension();
	}

}
