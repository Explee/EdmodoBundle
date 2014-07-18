<?php

namespace Explee\EdmodoBundle\Security\Firewall;

use Explee\EdmodoBundle\Security\Authentication\Token\EdmodoToken;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\HttpFoundation\Request;

class EdmodoListener extends AbstractAuthenticationListener
{
    protected function attemptAuthentication(Request $request)
    {
        $launch_key = $request->get('launch_key');
        if($launch_key === null){
            throw new AuthenticationException('The authentication failed.');
        }
        return $this->authenticationManager->authenticate(new EdmodoToken($launch_key,array()));
    }
}