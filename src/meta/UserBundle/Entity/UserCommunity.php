<?php

namespace meta\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\UserBundle\Entity\UserCommunity
 *
 * @ORM\Table(name="User_in_community",uniqueConstraints={@ORM\UniqueConstraint(name="unique_idx", columns={"user_id", "community_id"})}))
 * @ORM\Entity()
 */
class UserCommunity
{

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
      * @ORM\ManyToOne(targetEntity="meta\UserBundle\Entity\User", inversedBy="userCommunities")
      */
    private $user;

    /**
      * @ORM\ManyToOne(targetEntity="meta\GeneralBundle\Entity\Community\Community", inversedBy="userCommunities")
      */
    private $community;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true, nullable=true)
     * @Assert\Email()
     */
    private $email;

    /**
     * @var boolean $guest
     *
     * @ORM\Column(name="guest", type="boolean", nullable=true)
     */
    private $guest;

    /**
     * @var boolean $manager
     *
     * @ORM\Column(name="manager", type="boolean", nullable=true)
     */
    private $manager;

    /**
     * @var date $created_at
     * 
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;  
    
    /**
     * @var \DateTime $deleted_at
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $deleted_at;

    public function __construct() {
        
        $this->guest = false;
        $this->manager = false;
        
        $this->email = null;
        $this->created_at = new \DateTime('now');
        $this->deleted_at = null;

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
     * Set email
     *
     * @param string $email
     * @return UserInCommunity
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set guest
     *
     * @param boolean $guest
     * @return UserInCommunity
     */
    public function setGuest($guest)
    {
        $this->guest = $guest;
    
        return $this;
    }

    /**
     * Get guest
     *
     * @return boolean 
     */
    public function getGuest()
    {
        return $this->guest;
    }

    /**
     * Is guest
     *
     * @return boolean 
     */
    public function isGuest()
    {
        return ($this->guest === true);
    }

    /**
     * Set manager
     *
     * @param boolean $manager
     * @return UserInCommunity
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    
        return $this;
    }

    /**
     * Get manager
     *
     * @return boolean 
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Is manager
     *
     * @return boolean 
     */
    public function isManager()
    {
        return ($this->manager === true);
    }

    /**
     * Set user
     *
     * @param \meta\UserBundle\Entity\User $user
     * @return UserInCommunity
     */
    public function setUser(\meta\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \meta\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set community
     *
     * @param \meta\GeneralBundle\Entity\Community\Community $community
     * @return UserInCommunity
     */
    public function setCommunity(\meta\GeneralBundle\Entity\Community\Community $community = null)
    {
        $this->community = $community;
    
        return $this;
    }

    /**
     * Get community
     *
     * @return \meta\GeneralBundle\Entity\Community\Community 
     */
    public function getCommunity()
    {
        return $this->community;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return User
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set deleted_at
     *
     * @param \DateTime $deletedAt
     * @return User
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deleted_at = $deletedAt;
    
        return $this;
    }

    /**
     * Get deleted_at
     *
     * @return \DateTime 
     */
    public function getDeletedAt()
    {
        return $this->deleted_at;
    }

    /**
     * Is deleted
     *
     * @return boolean 
     */
    public function isDeleted()
    {
        return !($this->deleted_at === NULL);
    }

    /**
     * Deletes
     *
     * @return User 
     */
    public function delete()
    {
        $this->deleted_at = new \DateTime('now');
        return $this;
    }

}