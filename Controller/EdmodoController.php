<?php

namespace Explee\EdmodoBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Explee\EdmodoBundle\Entity\EdGroup;

class EdmodoController extends ContainerAware
{
    
    /**
     * @Route("/", name="edmodo_index")
     * @Template()
     */
    public function indexAction()
    {
        exit();
    }

    /**
     * The Edmodo Install url
     * @Route("/install/", name="edmodo_install")
     * @Template()
     */
    public function installAction()
    {
        $container = $this->container;
        $request   = $container->get("request");
        $em        = $container->get('doctrine')->getManager();
        $json      = $request->request->get("install");
        $edmodoApi = $container->get("edmodo.api");

        $this->container->get("logger")->info("Edmodo install : ".$request->request->get("install"));
        if(!$json)
        {
            $response = new JsonResponse();
            return $response->setData(array(
                "status"        => "failed",
                "error_types"   => array("user_token_error"),
                "error_message" => "Installation failed : no data sent to the application"
            ));
        }
        
        $json = json_decode($json); 
        $this->container->get("session")->set("ed_access_token", $json->access_token);
         
        //create user
        $user = $em->getRepository($this->container->getParameter('edmodo.user_target'))->findOneByEdId($json->user_token);
        if(!$user)
        {
            
            $ApiData = $edmodoApi->getUser("83eb9eed4");
            $user = $edmodoApi->createEdmodoUser($ApiData);

            $em->persist($user);
            
        }



        $count = array();
        foreach($json->groups as $groupId)
        {
            $theGroup = $this->addGroupIfNotExist($groupId);
            $count[] = $theGroup;
            $theGroup->addUser($user);
            $theGroup->setOwner($user);
            $em->persist($theGroup);
        }
        $em->flush();
        if(count($count) > 0)
        {
            $response = new JsonResponse();
            return $response->setData(array(
                'status' => "success"
            ));
        }else{
            $response = new JsonResponse();
            return $response->setData(array(
                "status"        => "failed",
                "error_types"   => array("group_id_error"),
                "error_message" => "Installation failed : no group_id given"
            ));
        }
       
    }

    /**
     * The Edmodo hook url
     * @Route("/hook/", name="edmodo_hook")
     * @Template()
     */
    public function hookAction()
    {
        $request   = $this->container->get("request");
        $json      = $request->get("update_data");
        $json      = json_decode($json);
        $errorType = $json->update_type;

        $this->container->get("logger")->info("Edmodo hook : ".$errorType);
        $this->container->get("logger")->info($request->request->get("update_data"));

        switch($errorType)
        {
            case "transaction_receipt":
                $this->transactionReceipt($json);
                break;
            case "app_uninstall":
                $this->appUninstall($json);
            case "user_data_updated":
                $this->userDataUpdated($json);
                break;
            case "group_deleted":
                $this->groupDeleted($json);
                break;
            case "group_member_created":
                $this->groupMemberCreated($json);
            case "removed_group_members":
                $this->groupMemberDeleted($json);
                break;
            default:

                break;
        }

        $response = new JsonResponse();
        return $response->setData(array(
                    'status' => "success"
        ));
    }


    /*
        PRIVATE FUNCTIONS
    */

    private function groupMemberDeleted($json)
    {
        $container = $this->container;
        $em        = $container->get('doctrine')->getManager();

        foreach($json->removed_group_members as $rem)
        {
            $group = $em->getRepository('EdmodoBundle:EdGroup')->findOneByEdId($rem->group_id);  
            if($group)
            {
                foreach($rem->user_tokens as $userToken)
                {
                    $user = $em->getRepository($this->container->getParameter('edmodo.user_target'))->findOneByEdId($userToken);
                    if($user)
                    {
                        $group->remove($user);
                        $em->persist($group);
                    }
                }
            }
        }
        $em->flush();
   }


    private function groupMemberCreated($json)
    {
        $container = $this->container;
        $em        = $container->get('doctrine')->getManager();
        foreach($json->new_group_members as $newGroupMember)
        {
            $group = $em->getRepository('EdmodoBundle:EdGroup')->findOneByEdId($newGroupMember->group_id);
            if($group)
            {
                foreach($newGroupMember->members as $member)
                {
                    $user = $edmodoApi->createEdmodoUser($member);
                    $group->addUser($user);
                    $em->persist($group);
                }
            }
        }
        $em->flush();
    }


    private function addGroupIfNotExist($id)
    {
        $container = $this->container;
        $em        = $container->get('doctrine')->getManager();
        $isEdGroup = $em->getRepository('EdmodoBundle:EdGroup')->findOneByEdId($id);

        if(!$isEdGroup)
        {
            $edGroup = new EdGroup();
            $edGroup->setEdId($id);
            $em->persist($edGroup);
            $em->flush();
            return $edGroup;
        }else{
            return $isEdGroup;
        }
    }

    private function groupDeleted($json)
    {
        $container = $this->container;
        $em        = $container->get('doctrine')->getManager();

        foreach($json->group_ids as $groupId)
        {
            $group = $em->getRepository('EdmodoBundle:EdGroup')->findOneByEdId($groupId);
            if($group)
            {
                $em->remove($group);
            }
        }
        $em->flush();
    }

    private function userDataUpdated($json)
    {
        $container = $this->container;
        $em        = $container->get('doctrine')->getManager();
        foreach($json->updated_users as $jsonUser)
        {
            $user = $em->getRepository($this->container->getParameter('edmodo.user_target'))->findOneByEdId($jsonUser->user_token);
            if($user)
            {
                $user->setFirstname($jsonUser->first_name);
                $user->setLastname($jsonUser->last_name);
                $em->persist($user);
            }
        }
        $em->flush();
    }

    private function appUninstall($json)
    {
        //uninstalled_groups
        $container = $this->container;
        $em        = $container->get('doctrine')->getManager();

        foreach($json->uninstalled_groups as $un)
        {
            $group = $em->getRepository('EdmodoBundle:EdGroup')->findOneByIdAndLicense($un->group_id, $un->license_code);
            if(count($group) == 1) $em->remove($group[0]);
        }

        $em->flush();

    }

    //Callback of installation
    private function transactionReceipt($json)
    {
        $container = $this->container;
        $em        = $container->get('doctrine')->getManager();

        foreach($json->group_licenses as $license)
        {
            $group = $em->getRepository('EdmodoBundle:EdGroup')->findOneByEdId($license->group_id);
            if(!$group) continue;
            $group->setlicenseCode($license->license_code);
            $group->setExpirationDate(new \DateTime($license->expiration_date));
            $em->persist($group);
        }
        $em->flush();
    }


}