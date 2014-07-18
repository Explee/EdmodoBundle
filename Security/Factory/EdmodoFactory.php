<?php

namespace Explee\EdmodoBundle\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;

class EdmodoFactory extends AbstractFactory
{


    protected function getListenerId()
    {
        return 'edmodo.security.authentication.listener';
    }

    protected function getAuthProviderId()
    {
        return 'edmodo.security.authentication.provider';
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'edmodo';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {

        $providerId = $this->getAuthProviderId() . '.' . $id;
        $definition = $container
            ->setDefinition($providerId, new DefinitionDecorator($this->getAuthProviderId()))
            ->replaceArgument(0, new Reference($userProviderId));

        return $providerId;
    }
}