<?php

namespace Explee\EdmodoBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class EdmodoToken extends AbstractToken
{
    public $created;
    public $digest;
    public $nonce;
    private $launch_key;

    public function __construct($launch_key, array $roles = array(), $uid = '')
    {
        parent::__construct($roles);
        $this->launch_key = $launch_key;
        $this->setUser($uid);
        // Si l'utilisateur a des rôles, on le considère comme authentifié
        $this->setAuthenticated(count($roles) > 0);
    }

    public function getCredentials()
    {
        return '';
    }

    public function getLaunchKey()
    {
        return $this->launch_key;
    }
}