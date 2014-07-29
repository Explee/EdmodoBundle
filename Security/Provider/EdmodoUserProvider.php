<?php

namespace Explee\EdmodoBundle\Security\Provider;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class EdmodoUserProvider implements UserProviderInterface
{
    protected $userManager;
    protected $em;
    protected $session;

    public function __construct($userManager, $em, $session)
    {
        
        $this->userManager = $userManager;
        $this->em = $em;
        $this->session = $session;
    }

    public function supportsClass($class)
    {
        return $this->userManager->supportsClass($class);
    }

    public function findUserEdId($edId)
    {
        return $this->userManager->findUserBy(array('edId' => $edId));
    }

    public function loadUserByUsername($userData)
    {
        $user = $this->findUserEdId($userData->user_token);

        if (empty($user)) {
            $user = $this->userManager->createUser();
            $user = $this->constructUser($user, $userData);
            
        }else if(!$user->hasRole('ROLE_EDMODO_'.strtoupper($this->slugify($userData->apiKey)))){
            $user->addRole('ROLE_EDMODO_'.strtoupper($this->slugify($userData->apiKey)));
            $this->em->getManager()->persist($user);
        }

        if(isset($userData->access_token) && $userData->access_token)
        {
            $this->session->set("ed_access_token", $userData->access_token);
        }
        if(isset($userData->apiKey) && $userData->apiKey){
            $this->session->set("ed_api_key", $userData->apiKey);
        }
        
        $this->userManager->updateUser($user);

        //link user to his groups
        foreach($userData->groups as $group)
        {
            $groupEnt = $this->em->getRepository('EdmodoBundle:EdGroup')->findOneByEdId($group->group_id);
            
            if(!$groupEnt || in_array($user, $groupEnt->getUsers()->toArray())) continue;
            $groupEnt->addUser($user);
            $this->em->getManager()->persist($groupEnt);
            if($group->is_owner == 1)
            {
                $groupEnt->setOwner($user);
            }
        }
        $this->em->getManager()->flush();

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user)) || !$user->getEdId()) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getEdId());
    }

    private function constructUser($user,$userData)
    {
        $user->setEdId($userData->user_token);
        $user->setPassword('');
        $user->setEmail($userData->last_name."-".$userData->user_token."@edmodo-auto.com");
        $user->setUsername($userData->last_name."-".$userData->user_token);
        $user->addRole('ROLE_EDMODO_'.strtoupper($this->slugify($userData->apiKey)));
        $user->setEnabled(true);
        return $user;
    }


    private function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
     
        // trim
        $text = trim($text, '-');
     
        // transliterate
        if (function_exists('iconv'))
        {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }
     
        // lowercase
        $text = strtolower($text);
     
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
     
        if (empty($text))
        {
            return 'n-a';
        }
     
        return $text;
    }

}