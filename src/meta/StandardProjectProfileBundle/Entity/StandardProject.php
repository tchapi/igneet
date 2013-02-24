<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity,
    Symfony\Component\Validator\Constraints as Assert;

use meta\GeneralBundle\Entity\Behaviour\Taggable;

/**
 * meta\StandardProjectProfileBundle\Entity\StandardProject
 *
 * @ORM\Table(name="StandardProject")
 * @ORM\Entity(repositoryClass="meta\StandardProjectProfileBundle\Entity\StandardProjectRepository")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity("slug")
 * @ORM\MappedSuperclass
 */
class StandardProject extends Taggable
{
    /**
     * @var integer $id
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
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3")
     * @Assert\Regex(pattern="/^[a-zA-Z0-9\-]+$/")
     */
    private $slug;

    /**
     * @var string $headline
     *
     * @ORM\Column(name="headline", type="string", length=255, nullable=true)
     */
    private $headline;

    /** Original Idea
     * @ORM\ManyToOne(targetEntity="meta\IdeaProfileBundle\Entity\Idea", inversedBy="resultingProject")
     **/
    private $originalIdea;

    /**
     * @var string $picture
     *
     * @ORM\Column(name="picture", type="string", length=255, nullable=true)
     */
    private $picture;

    /**
     * @Assert\File(maxSize="6000000")
     */
    protected $file;

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
     * @var string $about
     *
     * @ORM\Column(name="about", type="text", nullable=true)
     */
    private $about;

    /**
     * Skills I would need as a project (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\Skill", inversedBy="skilledStandardProjects")
     * @ORM\JoinTable(name="StandardProject_need_Skill",
     *      joinColumns={@ORM\JoinColumn(name="standard_project_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="skill_id", referencedColumnName="id")}
     *      )
     **/
    private $neededSkills;

    /**
     * Users owning me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", mappedBy="projectsOwned")
     **/
    private $owners;

    /**
     * Users participating in me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", mappedBy="projectsParticipatedIn")
     **/
    private $participants;

    /**
     * Users watching me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", mappedBy="projectsWatched")
     **/
    private $watchers;

    /**
     * Meta this project is linked to
     * @ORM\ManyToOne(targetEntity="MetaProject", inversedBy="projects")
     **/
    private $meta;

    /**
     * Comments on this project (OWNING SIDE)
     * @ORM\OneToMany(targetEntity="meta\StandardProjectProfileBundle\Entity\Comment\StandardProjectComment", mappedBy="standardProject", cascade="remove")
     * @ORM\OrderBy({"created_at" = "DESC"})
     **/
    private $comments;

/* ********** */

    /**
     * Common Lists
     * @ORM\OneToMany(targetEntity="CommonList", mappedBy="project", cascade="remove")
     **/
    private $commonLists;

    /**
     * Resources
     * @ORM\OneToMany(targetEntity="Resource", mappedBy="project", cascade="remove")
     **/
    private $resources;

    /**
     * Wiki (OWNING SIDE)
     * @ORM\OneToOne(targetEntity="Wiki", inversedBy="project")
     **/
    private $wiki;

/* ********** */

    /**
     * Log entries about this project
     * @ORM\OneToMany(targetEntity="meta\GeneralBundle\Entity\Log\StandardProjectLogEntry", mappedBy="standardProject")
     **/
    private $logEntries;

    public function __construct()
    {
        $this->created_at = $this->updated_at = new \DateTime('now');

        $this->neededSkills = new ArrayCollection();
        $this->owners = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->watchers = new ArrayCollection();

        $this->comments = new ArrayCollection();

        $this->commonLists = new ArrayCollection();
        $this->resources = new ArrayCollection();

    }

    public function getLogName()
    {
        return $this->name;
    }
    public function getLogArgs(){
        return array( 'slug' => $this->slug );
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
     * Set name
     *
     * @param string $name
     * @return StandardProject
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
     * Set headline
     *
     * @param string $headline
     * @return StandardProject
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
            return "/bundles/metageneral/img/defaults/standardProject.png";
        else
            return $this->getPictureWebPath();
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
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
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
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePicturePath()) {
            unlink($file);
        }
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return StandardProject
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
     * @return StandardProject
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
     * Add neededSkills
     *
     * @param meta\UserProfileBundle\Entity\Skill $neededSkill
     * @return StandardProject
     */
    public function addNeededSkill(\meta\UserProfileBundle\Entity\Skill $neededSkill)
    {
        $neededSkill->addSkilledStandardProject($this);
        $this->neededSkills[] = $neededSkill;
    
        return $this;
    }

    /**
     * Remove neededSkills
     *
     * @param meta\UserProfileBundle\Entity\Skill $neededSkill
     */
    public function removeNeededSkill(\meta\UserProfileBundle\Entity\Skill $neededSkill)
    {
        $this->neededSkills->removeElement($neededSkill);
        $neededSkill->removeSkilledStandardProject($this);
    }

    /**
     * Get neededSkills
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getNeededSkills()
    {
        return $this->neededSkills;
    }

    /**
     * Set neededSkills
     *
     * @param Array $neededSkills
     * @return StandardProject
     */
    public function setNeededSkills($neededSkills)
    {
        $this->neededSkills = $neededSkills;
        
        return $this;
    }

    /**
     * Add owners
     *
     * @param meta\UserProfileBundle\Entity\User $owners
     * @return StandardProject
     */
    public function addOwner(\meta\UserProfileBundle\Entity\User $owners)
    {
        $this->owners[] = $owners;
    
        return $this;
    }

    /**
     * Remove owners
     *
     * @param meta\UserProfileBundle\Entity\User $owners
     */
    public function removeOwner(\meta\UserProfileBundle\Entity\User $owners)
    {
        $this->owners->removeElement($owners);
    }

    /**
     * Get owners
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getOwners()
    {
        return $this->owners;
    }

    /**
     * Add participants
     *
     * @param meta\UserProfileBundle\Entity\User $participants
     * @return StandardProject
     */
    public function addParticipant(\meta\UserProfileBundle\Entity\User $participants)
    {
        $this->participants[] = $participants;
    
        return $this;
    }

    /**
     * Remove participants
     *
     * @param meta\UserProfileBundle\Entity\User $participants
     */
    public function removeParticipant(\meta\UserProfileBundle\Entity\User $participants)
    {
        $this->participants->removeElement($participants);
    }

    /**
     * Get participants
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Get N random owners
     *
     * @return Doctrine\Common\Collections\Collections
     */
    public function getRandomOwners($limit)
    {
        $sub_array = $this->owners->slice(0,max(0,$limit));
        shuffle($sub_array);

        return $sub_array;

    }

    /**
     * Get N random participants
     *
     * @return Doctrine\Common\Collections\Collections
     */
    public function getRandomParticipants($limit)
    {
        $sub_array = $this->participants->slice(0,max(0,$limit));
        shuffle($sub_array);

        return $sub_array;

    }

     /**
     * Get N random participants or owners
     *
     * @return array
     */
    public function getRandomParticipantsAndOwners($limit)
    {
        $array = array_merge($this->getRandomParticipants($limit), $this->getRandomOwners($limit));
        shuffle($array);

        return array_slice($array, 0, max(0,$limit));
    }

    /**
     * Add watchers
     *
     * @param meta\UserProfileBundle\Entity\User $watchers
     * @return StandardProject
     */
    public function addWatcher(\meta\UserProfileBundle\Entity\User $watchers)
    {
        $this->watchers[] = $watchers;
    
        return $this;
    }

    /**
     * Remove watchers
     *
     * @param meta\UserProfileBundle\Entity\User $watchers
     */
    public function removeWatcher(\meta\UserProfileBundle\Entity\User $watchers)
    {
        $this->watchers->removeElement($watchers);
    }

    /**
     * Set watchers
     *
     * @return StandardProject 
     */
    public function setWatchers(\Doctrine\Common\Collections\Collection $watchers)
    {
        $this->watchers = $watchers;
        return $this;
    }

    /**
     * Get watchers
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getWatchers()
    {
        return $this->watchers;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return StandardProject
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    
        return $this;
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Add CommonList
     *
     * @param meta\StandardProjectProfileBundle\Entity\CommonList $commonList
     * @return StandardProject
     */
    public function addCommonList(\meta\StandardProjectProfileBundle\Entity\CommonList $commonList)
    {
        $commonList->setProject($this);
        $this->commonLists[] = $commonList;

        return $this;
    }

    /**
     * Remove CommonList
     *
     * @param meta\StandardProjectProfileBundle\Entity\CommonList $commonList
     */
    public function removeCommonList(\meta\StandardProjectProfileBundle\Entity\CommonList $commonList)
    {
        $this->commonLists->removeElement($commonList);
    }

    /**
     * Get CommonLists
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getCommonLists()
    {
        return $this->commonLists;
    }

    /**
     * Has commonList
     *
     * @return boolean
     */
    public function hasCommonList(\meta\StandardProjectProfileBundle\Entity\CommonList $commonList)
    {
        return $this->commonLists->contains($commonList);
    }

    /**
     * Set wiki
     *
     * @param \meta\StandardProjectProfileBundle\Entity\Wiki $wiki
     * @return StandardProject
     */
    public function setWiki(\meta\StandardProjectProfileBundle\Entity\Wiki $wiki = null)
    {
        if ($wiki) $wiki->setProject($this);
        $this->wiki = $wiki;
    
        return $this;
    }

    /**
     * Get wiki
     *
     * @return \meta\StandardProjectProfileBundle\Entity\Wiki 
     */
    public function getWiki()
    {
        return $this->wiki;
    }

    /**
     * Set meta
     *
     * @param \meta\StandardProjectProfileBundle\Entity\MetaProject $meta
     * @return StandardProject
     */
    public function setMeta(\meta\StandardProjectProfileBundle\Entity\MetaProject $meta = null)
    {
        $this->meta = $meta;
    
        return $this;
    }

    /**
     * Get meta
     *
     * @return \meta\StandardProjectProfileBundle\Entity\MetaProject 
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Set originalIdea
     *
     * @param \meta\IdeaProfileBundle\Entity\Idea $originalIdea
     * @return StandardProject
     */
    public function setOriginalIdea(\meta\IdeaProfileBundle\Entity\Idea $originalIdea = null)
    {
        if ($originalIdea){
            $originalIdea->setResultingProject($this);
        } else {
            $this->originalIdea->setResultingProject(null);
        }
        $this->originalIdea = $originalIdea;
    
        return $this;
    }

    /**
     * Get originalIdea
     *
     * @return \meta\IdeaProfileBundle\Entity\Idea 
     */
    public function getOriginalIdea()
    {
        return $this->originalIdea;
    }

    /**
     * Add comment
     *
     * @param \meta\StandardProjectProfileBundle\Entity\Comment\StandardProjectComment $comment
     * @return StandardProject
     */
    public function addComment(\meta\StandardProjectProfileBundle\Entity\Comment\StandardProjectComment $comment)
    {
        $comment->setStandardProject($this);
        $this->comments[] = $comment;
    
        return $this;
    }

    /**
     * Remove comments
     *
     * @param \meta\StandardProjectProfileBundle\Entity\Comment\StandardProjectComment $comment
     */
    public function removeComment(\meta\StandardProjectProfileBundle\Entity\Comment\StandardProjectComment $comment)
    {
        $comment->setStandardProject(null);
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
     * Add logEntries
     *
     * @param \meta\GeneralBundle\Entity\Log\StandardProjectLogEntry $logEntries
     * @return StandardProject
     */
    public function addLogEntrie(\meta\GeneralBundle\Entity\Log\StandardProjectLogEntry $logEntries)
    {
        $this->logEntries[] = $logEntries;
    
        return $this;
    }

    /**
     * Remove logEntries
     *
     * @param \meta\GeneralBundle\Entity\Log\StandardProjectLogEntry $logEntries
     */
    public function removeLogEntrie(\meta\GeneralBundle\Entity\Log\StandardProjectLogEntry $logEntries)
    {
        $this->logEntries->removeElement($logEntries);
    }

    /**
     * Get logEntries
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLogEntries()
    {
        return $this->logEntries;
    }

    /**
     * Add resources
     *
     * @param \meta\StandardProjectProfileBundle\Entity\Resource $resource
     * @return StandardProject
     */
    public function addResource(\meta\StandardProjectProfileBundle\Entity\Resource $resource)
    {
        $resource->setProject($this);
        $this->resources[] = $resource;
    
        return $this;
    }

    /**
     * Remove resources
     *
     * @param \meta\StandardProjectProfileBundle\Entity\Resource $resource
     */
    public function removeResource(\meta\StandardProjectProfileBundle\Entity\Resource $resource)
    {
        $this->resources->removeElement($resource);
        $resource->setProject(null);
    }

    /**
     * Get resources
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getResources()
    {
        return $this->resources;
    }
}