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
        $apiName   = $request->query->get("api_name");

        $this->container->get("logger")->info("Edmodo install : ".$request->request->get("install")." API name : ".$request->query->get("api_name"));
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
        $this->container->get("session")->set("ed_api_key",$apiName);
         

        $count = array();
        foreach($json->groups as $groupId)
        {
            $theGroup = $this->addGroupIfNotExist($groupId,$apiName);
            $count[] = $theGroup;
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
     * @Route("/test/", name="edmodo_test")
     * @Template()
     */
    public function testAction()
    {
        $container = $this->container;
        $request   = $container->get("request");
        $em        = $container->get('doctrine')->getManager();
        $isEdGroup = $em->getRepository('EdmodoBundle:EdGroup')->findOneByEdId("583043");
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
        $apiName   = $request->query->get("api_name");
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
                $this->appUninstall($json,$apiName);
                break;
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


    private function addGroupIfNotExist($id,$apiName)
    {
        $container = $this->container;
        $em        = $container->get('doctrine')->getManager();
        $isEdGroup = $em->getRepository('EdmodoBundle:EdGroup')->findOneByEdId($id);
        if(!$isEdGroup)
        {
            $edGroup = new EdGroup();
            $edGroup->setEdId($id);
            $edGroup->addApi($this->slugify($apiName));
            $em->persist($edGroup);
            $em->flush();
            return $edGroup;
        }else{
            $isEdGroup->addApi($apiName);
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
                $this->removeRoleOfUserGroup($group, $apiName);
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
                // Do your own logic
                //...
            }
        }
    }

    private function appUninstall($json,$apiName)
    {
        //uninstalled_groups
        $container = $this->container;
        $em        = $container->get('doctrine')->getManager();

        foreach($json->uninstalled_groups as $un)
        {
            $group = $em->getRepository('EdmodoBundle:EdGroup')->findOneByIdAndLicense($un->group_id, $un->license_code);
    
            if(count($group) == 1){
                $group = $group[0];
                $this->removeRoleOfUserGroup($group, $apiName);
                //$apiName
                $group->removeApi($this->slugify($apiName));
                if(count($group->getApi()) == 0)
                {
                    $em->remove($group);
                }
            } 
        }

        $em->flush();

    }

    private function removeRoleOfUserGroup($paramGroup, $apiName)
    {
        $container = $this->container;
        $em        = $container->get('doctrine')->getManager();

        $seekedRole = "ROLE_EDMODO_".strtoupper($this->slugify($apiName));
        foreach($paramGroup->getUsers() as $user)
        {
            $stillRole = false;
            foreach($user->getEdGroups() as $userGroup)
            {
                if($paramGroup === $userGroup)continue;
                if($userGroup->hasApi($this->slugify($apiName))){
                    $stillRole = true;
                    break;
                }
            }
            if(!$stillRole){
                $user->removeRole($seekedRole);
                $em->persist($user);
            } 
            
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