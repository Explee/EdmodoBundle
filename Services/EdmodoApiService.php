<?php

namespace Explee\EdmodoBundle\Services;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class EdmodoApiService implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    private $apiKey;
    private $version;
    private $apiUrl;
    private $session;
    private $userManager;
    private $em;
    private $userProvider;

    public function __construct($apiKey,$version, $apiUrl, $session, $userManager, $em, $userProvider)
    {
        $this->apiKey  = $apiKey;
        $this->version = $version;
        $this->apiUrl  = $apiUrl;
        $this->session  = $session;
        $this->userManager  = $userManager;
        $this->em  = $em;
        $this->userProvider  = $userProvider;
    }

    /**
     * GET call
     * @var $type       String : the name of the API route (e.g "assignmentStatus", "users")
     * @var $parameters Array  : array of parameters
     * @return array
     */
    public function get($type, $parameters)
    {
        $url = $this->urlBuilder($type, $parameters);
        return $this->apiCall($url);
    }

    /**
     *  GET single member by token
     */
    public function getUser($token)
    {
        $response = $this->get("users" ,  array("user_tokens"=>array($token) ) );
        return $response[0];
    }

    /**
     *  GET members by token
     */
    public function getUsers($token)
    {
        return $response = $this->get("users" ,  array("user_tokens"=>$token ) );
    }

    public function launchRequests($launchKey, $apiName)
    {
        $url = $this->apiUrl;
        return $this->get("launchRequests", array(  "launch_key" => $launchKey, "api_key" => $this->apiKey[$apiName] ));
    }


    public function apiCall($url, $method = "get")
    {
        if($method == "get")
        {
            $unparsed_json = @file_get_contents($url);
            if($unparsed_json === FALSE){
                throw new AuthenticationException("Error during Edmodo api call. Please retry.");
            }
            $json_object = json_decode($unparsed_json);
        }
        
        return $json_object;
    }


    public function createEdmodoUser($ApiData)
    {
        $user = $this->userProvider->loadUserByUsername($ApiData);
        
        return $user;
    }



    /**
     * @String $type    //name of the route, please check -> https://XXXX.edmodobox.com/home#/developer/api
     */
    private function urlBuilder($type, $params)
    {
        //initialize the host
        $url = $this->apiUrl;
        $url .= "/".$type;

        //build parameters
        $key =  ($this->session->get("ed_api_key")) ? $this->apiKey[$this->session->get("ed_api_key")] : "";
        $parameters = array( "api_key" => $key);
        
        //get access_token
        if($access_token = $this->session->get("ed_access_token")) $parameters["access_token"] = $access_token;
        //it allows the override of the default values if needed.
        $parameters = array_merge($parameters, $params);
        //clean params
        $parameters = $this->parseParams($parameters);

        //build query

        $queryString = http_build_query($parameters);

        return $url."?".$queryString;
    }

    /**
     * SERIALIZE arrays for the structure : ["value","value","value"]
     * /!\ for group_id, if is array, just implode like : value1,value2,valu3
     */
    private function parseParams($params)
    {
        //TODO: attachment and recipients not developed.
        $parse = array("user_tokens","group_ids","group_id");
        
        foreach($parse as $p)
        {
            if(isset($params[$p]) && gettype($params[$p]) === "array")
            {
                if($p == "group_id")
                {
                    $params[$p] = implode(',' , $params[$p]);
                }else{
                    $params[$p] = '["'.implode('","' , $params[$p]).'"]';
                }

            }
        }
        return $params;
    }

}