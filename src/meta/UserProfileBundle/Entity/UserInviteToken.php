<?php

namespace meta\UserProfileBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * UserInviteToken
 *
 * @ORM\Table(name="UserInviteToken")
 * @ORM\Entity
 */
class UserInviteToken
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
     * @ORM\Column(name="token", type="string", length=255)
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="used_at", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $used_at;

    /**
     *  The referal user (the one who invites)
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="createdTokens")
     */
    private $referalUser;

    /**
     *  The resulting user
     *
     * @ORM\OneToOne(targetEntity="User", inversedBy="originatingToken")
     */
    private $resultingUser;

    public function __construct($referalUser, $mail = null)
    {

        $this->created_at = new \DateTime('now');
        $this->used_at = null;

        $this->email = $mail;
        $this->referalUser = $referalUser;

        $this->token = md5(uniqid(null, true));

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
     * Set token
     *
     * @param string $token
     * @return UserInviteToken
     */
    public function setToken($token)
    {
        $this->token = $token;
    
        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return UserInviteToken
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return UserInviteToken
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
     * Set used_at
     *
     * @param \DateTime $usedAt
     * @return UserInviteToken
     */
    public function setUsedAt($usedAt)
    {
        $this->used_at = $usedAt;
    
        return $this;
    }

    public function isUsed()
    {
        return ($this->used_at !== null);
    }

    /**
     * Get used_at
     *
     * @return \DateTime 
     */
    public function getUsedAt()
    {
        return $this->used_at;
    }

    /**
     * Set resultingUser
     *
     * @param \meta\UserProfileBundle\Entity\User $resultingUser
     * @return UserInviteToken
     */
    public function setResultingUser(\meta\UserProfileBundle\Entity\User $resultingUser = null)
    {
        
        if ($this->resultingUser){
            $this->resultingUser->setOriginatingToken(null);
        }
        if ($resultingUser !== null){
            $resultingUser->setOriginatingToken($this);
        }
        $this->resultingUser = $resultingUser;

        $this->used_at = new \DateTime('now');
    
        return $this;
    }

    /**
     * Get resultingUser
     *
     * @return \meta\UserProfileBundle\Entity\User 
     */
    public function getResultingUser()
    {
        return $this->resultingUser;
    }

    /**
     * Set referalUser
     *
     * @param \meta\UserProfileBundle\Entity\User $referalUser
     * @return UserInviteToken
     */
    public function setReferalUser(\meta\UserProfileBundle\Entity\User $referalUser = null)
    {
        $this->referalUser = $referalUser;
    
        return $this;
    }

    /**
     * Get referalUser
     *
     * @return \meta\UserProfileBundle\Entity\User 
     */
    public function getReferalUser()
    {
        return $this->referalUser;
    }
}