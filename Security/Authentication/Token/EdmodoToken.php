<?php

namespace Explee\EdmodoBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class EdmodoToken extends AbstractToken
{
    public $created;
    public $digest;
    public $nonce;
    private $launch_key;
    private $api_name;

    public function __construct($launch_key,$api_name, array $roles = array(), $uid = '')
    {
        parent::__construct($roles);
        $this->launch_key = $launch_key;
        $this->api_name = $api_name;
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

    public function getApiName()
    {
        return $this->api_name;
    }
}