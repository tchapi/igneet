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
     * @var date $created_at
     * 
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;  
    
    /**
     * @var date $deleted_at
     * 
     * @ORM\Column(name="deleted_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $deleted_at;

    public function __construct() {
        
        $this->guest = false;
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
}