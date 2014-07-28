<?php

namespace Explee\EdmodoBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Explee\EdmodoBundle\Security\Authentication\Token\EdmodoToken;

class EdmodoProvider implements AuthenticationProviderInterface
{
    private $userProvider;
    private $cacheDir;
    private $edmodoApi;

    public function __construct(UserProviderInterface $userProvider, $cacheDir, $edmodoApi)
    {
        $this->userProvider = $userProvider;
        $this->cacheDir     = $cacheDir;
        $this->edmodoApi     = $edmodoApi;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }
        //API
        $userData = $this->edmodoApi->launchRequests($token->getLaunchKey(),$token->getApiName());   

        try{
            $token = new EdmodoToken($userData->access_token, $token->getApiName(), array(),$userData->user_token);
        }catch(\Exception $e){
            throw new AuthenticationException('The Edmodo authentication failed.');
        }

        $user = $token->getUser();

        //if is user
        if ($user instanceof UserInterface) {
            $newToken = new EdmodoToken($token->access_token, $token->getApiName(), $user->getRoles(),$user);
            return $newToken;
        }

        //if only string
        if ($user) {
            $authenticatedToken = $this->createAuthenticatedToken($userData, $token->getApiName());

            return $authenticatedToken;
        }

        throw new AuthenticationException('The Edmodo authentication failed.');
    }



    public function supports(TokenInterface $token)
    {
        return $token instanceof EdmodoToken;
    }

    protected function createAuthenticatedToken($userData, $apiName)
    {
        $userData->apiKey =  $apiName;
        $user = $this->userProvider->loadUserByUsername($userData);

        return new EdmodoToken($userData->access_token, $apiName, $user->getRoles(), $user);
    } 
}