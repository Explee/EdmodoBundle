<?php

namespace Explee\EdmodoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EdGroup
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Explee\EdmodoBundle\Entity\EdGroupRepository")
 */
class EdGroup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="edId", type="string", length=255)
     */
    private $edId;

    /**
     * @var string
     *
     * @ORM\Column(name="licenseCode", type="string", length=255, nullable=true)
     */
    private $licenseCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creationDate", type="datetime")
     */
    private $creationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expirationDate", type="datetime", nullable=true)
     */
    private $expirationDate;


    /**
     * @ORM\ManyToOne(targetEntity="Explee\EdmodoBundle\Model\EdmodoUserInterface", cascade={"persist"})
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     */
    private $owner;

     /**
     * @ORM\ManyToMany(targetEntity="Explee\EdmodoBundle\Model\EdmodoUserInterface", inversedBy="edGroups", cascade={"persist"})
     * @ORM\JoinTable(name="users_edgroups",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="edgroup_id", referencedColumnName="id", unique=true)}
     *      )
     **/
    private $users;

    public function __construct()
    {
        $this->creationDate = new \DateTime("now");
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set edId
     *
     * @param string $edId
     * @return EdGroup
     */
    public function setEdId($edId)
    {
        $this->edId = $edId;

        return $this;
    }

    /**
     * Get edId
     *
     * @return string 
     */
    public function getEdId()
    {
        return $this->edId;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     * @return EdGroup
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime 
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set expirationDate
     *
     * @param \DateTime $expirationDate
     * @return EdGroup
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Get expirationDate
     *
     * @return \DateTime 
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Set licenseCode
     *
     * @param string $licenseCode
     * @return EdGroup
     */
    public function setLicenseCode($licenseCode)
    {
        $this->licenseCode = $licenseCode;

        return $this;
    }

    /**
     * Get licenseCode
     *
     * @return string 
     */
    public function getLicenseCode()
    {
        return $this->licenseCode;
    }

    /**
     * Set owner
     *
     * @param \Explee\EdmodoBundle\Model\EdmodoUserInterface $owner
     * @return EdGroup
     */
    public function setOwner(\Explee\EdmodoBundle\Model\EdmodoUserInterface $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \Explee\EdmodoBundle\Model\EdmodoUserInterface 
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Add users
     *
     * @param \Explee\EdmodoBundle\Model\EdmodoUserInterface $users
     * @return EdGroup
     */
    public function addUser(\Explee\EdmodoBundle\Model\EdmodoUserInterface $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \Explee\EdmodoBundle\Model\EdmodoUserInterface $users
     */
    public function removeUser(\Explee\EdmodoBundle\Model\EdmodoUserInterface $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }
}
