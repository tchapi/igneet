<?php

namespace meta\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * UserInviteToken
 *
 * @ORM\Table(name="UserInviteToken")
 * @ORM\Entity
 * @UniqueEntity("token")
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
     *  The invited community, if any
     *
     * @ORM\ManyToOne(targetEntity="meta\GeneralBundle\Entity\Community\Community")
     */
    private $community;

    /**
     *  The type of invitation(user, guest) for the community, if any
     * @var boolean $community_type
     *
     * @ORM\Column(name="community_type", type="string", length=255, nullable=true)
     */
    private $community_type;

    /**
     *  The invited project, if any
     *
     * @ORM\ManyToOne(targetEntity="meta\ProjectBundle\Entity\StandardProject")
     */
    private $project;

    /**
     *  The type of invitation(owner, participant) for the project, if any
     * @var boolean $project_type
     *
     * @ORM\Column(name="project_type", type="string", length=255, nullable=true)
     */
    private $project_type;

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

    public function __construct($referalUser, $mail, $community = null, $community_type = 'guest', $project = null, $project_type = 'participant')
    {

        $this->created_at = new \DateTime('now');
        $this->used_at = null;

        $this->email = $mail;
        $this->setReferalUser($referalUser);

        $this->community = $community;
        $this->community_type = $community_type;
        $this->project = $project;
        $this->project_type = $project_type;
        
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
     * @param \meta\UserBundle\Entity\User $resultingUser
     * @return UserInviteToken
     */
    public function setResultingUser(\meta\UserBundle\Entity\User $resultingUser = null)
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
     * @return \meta\UserBundle\Entity\User 
     */
    public function getResultingUser()
    {
        return $this->resultingUser;
    }

    /**
     * Set referalUser
     *
     * @param \meta\UserBundle\Entity\User $referalUser
     * @return UserInviteToken
     */
    public function setReferalUser(\meta\UserBundle\Entity\User $referalUser = null)
    {
        if (!is_null($referalUser)){
            $referalUser->addCreatedToken($this);
        } elseif (!is_null($this->referalUser)){
            $this->referalUser->removeCreatedToken($this);
        }
        $this->referalUser = $referalUser;
        return $this;
    }

    /**
     * Get referalUser
     *
     * @return \meta\UserBundle\Entity\User 
     */
    public function getReferalUser()
    {
        return $this->referalUser;
    }

    /**
     * Set community
     *
     * @param \meta\GeneralBundle\Entity\Community\Community $community
     * @return UserInviteToken
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
     * Set project
     *
     * @param \meta\ProjectBundle\Entity\StandardProject $project
     * @return UserInviteToken
     */
    public function setProject(\meta\ProjectBundle\Entity\StandardProject $project = null)
    {
        $this->project = $project;
    
        return $this;
    }

    /**
     * Get project
     *
     * @return \meta\ProjectBundle\Entity\StandardProject 
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set community_type
     *
     * @param string $communityType
     * @return UserInviteToken
     */
    public function setCommunityType($communityType)
    {
        $this->community_type = $communityType;
    
        return $this;
    }

    /**
     * Get community_type
     *
     * @return string 
     */
    public function getCommunityType()
    {
        return $this->community_type;
    }

    /**
     * Set project_type
     *
     * @param string $projectType
     * @return UserInviteToken
     */
    public function setProjectType($projectType)
    {
        $this->project_type = $projectType;
    
        return $this;
    }

    /**
     * Get project_type
     *
     * @return string 
     */
    public function getProjectType()
    {
        return $this->project_type;
    }
}
