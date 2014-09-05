<?php

namespace meta\GeneralBundle\Entity\Community;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Community
 * @ORM\Table(name="Community")
 * @ORM\Entity(repositoryClass="meta\GeneralBundle\Entity\Community\CommunityRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Community
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=30)
     */
    private $type;
    // demo, association, entreprise

    /**
     * @var string $picture
     *
     * @ORM\Column(name="picture", type="string", length=255, nullable=true)
     */
    private $picture;

    /**
     * @Assert\File(maxSize="10000000")
     */
    protected $file;

    /**
     * @var string $headline
     *
     * @ORM\Column(name="headline", type="string", length=255, nullable=true)
     */
    private $headline;
    
    /**
     * @var string $about
     *
     * @ORM\Column(name="about", type="text", nullable=true)
     */
    private $about;

    /**
     * @var \DateTime $created_at
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;

    /**
     * @var \DateTime $updated_at
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $updated_at;

    /**
     * @var \DateTime $valid_until
     *
     * @ORM\Column(name="valid_until", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $valid_until;

    /**
     * Projects in this community
     * @ORM\OneToMany(targetEntity="meta\ProjectBundle\Entity\StandardProject", mappedBy="community")
     **/
    private $projects;
    
    /**
     * Ideas in this community
     * @ORM\OneToMany(targetEntity="meta\IdeaBundle\Entity\Idea", mappedBy="community")
     **/
    private $ideas;
    
    /**
     * Users in this community
     * @ORM\OneToMany(targetEntity="meta\UserBundle\Entity\UserCommunity", mappedBy="community")
     **/
    private $userCommunities;

    /**
     * Comments on this project (OWNING SIDE)
     * @ORM\OneToMany(targetEntity="meta\GeneralBundle\Entity\Comment\CommunityComment", mappedBy="community", cascade="remove")
     * @ORM\OrderBy({"created_at" = "DESC"})
     **/
    private $comments;

    // ::::::: PAYPAL :::::::
    /**
     * @var string $billing_plan
     *
     * @ORM\Column(name="billing_plan", type="text", nullable=true)
     */
    private $billing_plan;

    /**
     * @var string $billing_agreement
     *
     * @ORM\Column(name="billing_agreement", type="text", nullable=true)
     */
    private $billing_agreement;

    /**
     * Constructor
     */
    public function __construct($span = '1 month')
    {
        $this->created_at = $this->updated_at = new \DateTime('now');

        $this->projects = new ArrayCollection();
        $this->ideas = new ArrayCollection();
        $this->usersCommunities = new ArrayCollection();

        $this->comments = new ArrayCollection();

        // BILLING
        $this->type = "demo"; // By default, all communities are not _yet_ paid for
        $this->valid_until = new \DateTime('now + ' . $span); // Default validity for a demo

        $this->billing_plan = $this->billing_agreement = null;

    }
    
    public function getLogName()
    {
        return $this->name;
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
     * Add project
     *
     * @param \meta\ProjectBundle\Entity\StandardProject $project
     * @return Community
     */
    public function addProject(\meta\ProjectBundle\Entity\StandardProject $project)
    {
        if (!is_null($project)){
            $project->setCommunity($this);
            $this->projects[] = $project;
        }
    
        return $this;
    }

    /**
     * Remove project
     *
     * @param \meta\ProjectBundle\Entity\StandardProject $project
     */
    public function removeProject(\meta\ProjectBundle\Entity\StandardProject $project)
    {
        if (!is_null($project)){
            $project->setCommunity(null);
            $this->projects->removeElement($project);
        }
    }

    /**
     * Get projects
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * Add idea
     *
     * @param \meta\IdeaBundle\Entity\Idea $idea
     * @return Community
     */
    public function addIdea(\meta\IdeaBundle\Entity\Idea $idea)
    {
        if (!is_null($idea)){
            $idea->setCommunity($this);
            $this->ideas[] = $idea;
        }
    
        return $this;
    }

    /**
     * Remove idea
     *
     * @param \meta\IdeaBundle\Entity\Idea $idea
     */
    public function removeIdea(\meta\IdeaBundle\Entity\Idea $idea)
    {
        if (!is_null($idea)){
            $idea->setCommunity(null);
            $this->ideas->removeElement($idea);
        }
    }

    /**
     * Get ideas
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getIdeas()
    {
        return $this->ideas;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Community
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

  /**
     * Set type
     *
     * @param string $type
     * @return Community
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set picture
     *
     * @param string $picture
     * @return StandardProject
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;
        return $this;
    }

    /**
     * Get picture
     *
     * @return string 
     */
    public function getPicture()
    {
        if ($this->picture === null)
            return "/bundles/metageneral/img/defaults/community.png";
        else
            return $this->getPictureWebPath();
    }

    public function getRawPicture()
    {
        return $this->picture;
    }

    public function getAbsolutePicturePath()
    {
        return null === $this->picture
            ? null
            : $this->getUploadRootDir().'/'.$this->picture;
    }

    public function getPictureWebPath()
    {
        return null === $this->picture
            ? null
            : '/'.$this->getUploadDir().'/'.$this->picture;
    }

    private function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }

    private function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/pictures';
    }


    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->file) {
            // Generate a unique name

            $filename = sha1(uniqid(mt_rand(), true));
            $this->picture = $filename.'.'.$this->file->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->file->move($this->getUploadRootDir(), $this->picture);

        unset($this->file);
    }


    /**
     * Set headline
     *
     * @param string $headline
     * @return Community
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;
    
        return $this;
    }

    /**
     * Get headline
     *
     * @return string 
     */
    public function getHeadline()
    {
        return $this->headline;
    }
    
    /**
     * Set about
     *
     * @param string $about
     * @return StandardProject
     */
    public function setAbout($about)
    {
        $this->about = $about;
        return $this;
    }

    /**
     * Get about
     *
     * @return string 
     */
    public function getAbout()
    {
        return $this->about;
    }
    
    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Community
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
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return Community
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;
        return $this;
    }

    /**
     * Get updated_at
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function update()
    {
        $this->updated_at = new \DateTime('now');
    }

    /**
     * Set valid_until
     *
     * @param \DateTime $validUntil
     * @return Community
     */
    public function setValidUntil($validUntil)
    {
        if ($validUntil !== null) {
            $this->valid_until = clone $validUntil;
        }
        return $this;
    }

    /**
     * Is valid if valid_until > now()
     */
    public function isValid()
    {
        return ($this->valid_until > new \DateTime('now') );
    }

    /**
     * Get valid_until
     *
     * @return \DateTime 
     */
    public function getValidUntil()
    {
        return clone $this->valid_until;
    }

    /**
     * Extend valid_until
     *
     * @param string
     * @return Community
     */
    public function extendValidityBy($span)
    {
        $this->setValidUntil($this->valid_until->modify('+ ' . $span));
        return $this;
    }

    /**
     * Add userCommunity
     *
     * @param \meta\UserBundle\Entity\UserCommunity $userCommunity
     * @return Community
     */
    public function addUserCommunity(\meta\UserBundle\Entity\UserCommunity $userCommunity)
    {
        $this->userCommunities[] = $userCommunity;
    
        return $this;
    }

    /**
     * Remove userCommunity
     *
     * @param \meta\UserBundle\Entity\UserCommunity $userCommunity
     */
    public function removeUserCommunity(\meta\UserBundle\Entity\UserCommunity $userCommunity)
    {
        $this->userCommunities->removeElement($userCommunity);
    }

    /**
     * Get userCommunities
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserCommunities()
    {
        return $this->userCommunities;
    }

    /**
     * Add comment
     *
     * @param \meta\GeneralBundle\Entity\Comment\CommunityComment $comment
     * @return Community
     */
    public function addComment(\meta\GeneralBundle\Entity\Comment\CommunityComment $comment)
    {
        if (!is_null($comment)){
            $comment->setCommunity($this);
        }
        $this->comments[] = $comment;
    
        return $this;
    }

    /**
     * Remove comment
     *
     * @param \meta\GeneralBundle\Entity\Comment\CommunityComment $comment
     */
    public function removeComment(\meta\GeneralBundle\Entity\Comment\CommunityComment $comment)
    {
        if (!is_null($comment)){
            $comment->setCommunity(null);
        }
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set billing plan
     *
     * @param string $plan
     * @return Community
     */
    public function setBillingPlan($billing_plan)
    {
        $this->billing_plan = $billing_plan;
    
        return $this;
    }

    /**
     * Get billing_plan
     *
     * @return string 
     */
    public function getBillingPlan()
    {
        return $this->billing_plan;
    }

    /**
     * Set billing agreement
     *
     * @param string $agreement
     * @return Community
     */
    public function setBillingAgreement($billing_agreement)
    {
        $this->billing_agreement = $billing_agreement;
    
        return $this;
    }

    /**
     * Get billing_agreement
     *
     * @return string 
     */
    public function getBillingAgreement()
    {
        return $this->billing_agreement;
    }
}
