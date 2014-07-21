# Edmodo Bundle

Edmodo Bundle provides a basic gestion of Edmodo connection for a Symfony app.  
It provides :  

* GET API call management to the Edmodo API (POST API call not supported yet)
* authenticate the user from the Edmodo Store
* Create a User in data base
* Create an entry for Edmodo groups

## Prerequisites

* Symfony 2.2 at least
* FosUserBundle
* MySQL doctrine (propel and MongoDB not supported yet)

## Installation

### Step 1 : Composer

Add to your composer.json :  
``` json
{
     "require": {
          "explee/edmodobundle": "1.*@dev",
    }
}
```
!!! this bundle has a depedency on FOSUserBundle 2.0@dev, check your minimum-stability configuration but until a stable 2.0 is released, you probably need to add it to your composer.json too.

Then update your vendors.

### Step 2 : Configure the Bundle

#### Enable the Bundle

``` php
    <?php
    // app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Explee\EdmodoBundle\EdmodoBundle(),
        );
    }
```
#### Declare parameters

Declare your configuration giving your API key, and the namespace of your User class.

    // app/config/parameters.yml
``` yml
    parameters:
        edmodo.key:             <YOUR_API_KEY>
        edmodo.version:         v1.1                                            #the version of the API
        edmodo.url:             https://appsapi.edmodobox.com/%edmodo.version%  #the url of the edmodo API
        edmodo.user_target :    path\to\your\UserClass
```
#### Configure the Bundle    

This lines link your custom User class to the EdGroup class of the bundle, which manages the Edmodo Groups, its owners and its students.

    // app/config/config.yml
    doctrine:
        orm:
            resolve_target_entities:
                Explee\EdmodoBundle\Model\EdmodoUserInterface: %edmodo.user_target%


#### Routing

Now, you need to declare the 2 routes used by the EdmodoBundle. The first declares the generic routing for all the EdmodoBundle. The second one configure the login_check url.
``` yml
    // routing.yml
    edmodo:
        resource: "@EdmodoBundle/Controller/"
        type:     annotation
        prefix:   /ed

    security_check_edmodo:
        pattern: /login_check/ed
```
#### Configure the Provider
``` yml
    // app/config/security.yml
    security:
        providers:
            my_edmodo_provider:
                id: edmodo.user.provider

        firewalls:
            public :
                edmodo:
                    provider:                       my_edmodo_provider
                    login_path:                     <YOUR_LOGIN_PATH>
                    check_path:                     security_check_edmodo
                    default_target_path:            /

        role_hierarchy:
            ROLE_EDMODO:    ROLE_USER
```
#### Create relation between User and EdGroup

Open your custom User class. You need to implement the EdmodoUserInterface and add 2 variables :
``` php
    <?php
    // Acme/Bundle/Entity/User.php

    use Doctrine\Common\Collections\ArrayCollection;
    use Explee\EdmodoBundle\Model\EdmodoUserInterface;

    class User implements EdmodoUserInterface
    {
        /**
         * @ORM\Column(type="string", length=60, nullable=true)
         */
        protected $edId;

        /**
         * @ORM\ManyToMany(targetEntity="Explee\EdmodoBundle\Entity\EdGroup", mappedBy="users")
         **/
        private $edGroups;

        /**
         * @ORM\OneToMany(targetEntity="Explee\EdmodoBundle\Entity\EdGroup", mappedBy="owner")
         **/
        private $ownEdGroups;

        public function __construct()
        {
            $this->edGroups = new ArrayCollection();
            $this->ownEdGroups = new ArrayCollection();
        }
    }
```

#### Update your database

    $ php app/console doctrine:generate:entites AcmeBundle
    $ php app/console doctrine:schema:update --force

### Configure Edmodo

Go on your Edmodo dashboard (https://XXXX.edmodobox.com). Edit you app and fill in fields with this informations :

* **Install URL** :      https://domain.tld/ed/install/
* **App URL** :          https://domain.tld/login_check/ed
* **Updates Hook URL** : https://domain.tld/ed/hook/

## How to use API call with EdmodoApiService ?

The EdmodoBundle provides a service managing all your GET API calls in an easy way :
``` php
    <?php

    // get the service
    $myService = $this->container->get("edmodo.api");
    
    /**
     * generic get call
     * @var $type       String : the name of the API route (e.g "assignmentStatus", "users")
     * @var $parameters Array  : array of parameters
     * @return array
     */
    $myResponse = $myService->get($type, $parameters);

    // some shortcut are provided

    //get one user with his user_token
    $myService->getUser(string $user_token)

    //get multiple users with their user_token
    $myService->getUser(array $user_token_array)

    //create an Edmodo User with data array of the user
    $myService->createEdmodoUser($data_array)
```

## Override

You can override the Edmodo user creation to custom his email pattern, his role or add more values. Override Explee\EdmodoBundle\Security\Provider\EdmodoUserProvider::constructUser

## Miscellaneous
The EdmodoBundle logs each Install action and Hook Action in this way :  
Edmodo  {install/hook} : {if hook : action} {object}  
  
