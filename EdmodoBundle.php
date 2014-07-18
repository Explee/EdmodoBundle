<?php

namespace Explee\EdmodoBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Explee\EdmodoBundle\Security\Factory\EdmodoFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EdmodoBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new EdmodoFactory());
    }
}
