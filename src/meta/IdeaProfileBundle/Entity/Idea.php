<?php

namespace meta\IdeaProfileBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

use meta\GeneralBundle\Entity\Behaviour\Taggable;

/**
 * Idea
 *
 * @ORM\Table(name="Idea")
 * @ORM\Entity(repositoryClass="meta\IdeaProfileBundle\Entity\IdeaRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Idea extends Taggable
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    private $slug;
    
    /**
     * @var string
     *
     * @ORM\Column(name="headline", type="string", length=255, nullable=true)
     */
    private $headline;

    /**
     * @var string $picture
     *
     * @ORM\Column(name="picture", type="string", length=255, nullable=true)
     */
    private $picture;
    
    /**
     * @Assert\File(maxSize="6000000")
     */
    private $file;

    /**
     * @var text $about
     *
     * @ORM\Column(name="about", type="text", nullable=true)
     */
    private $about;
    
    /**
     * @var string
     *
     * @ORM\Column(name="concept_text", type="text", nullable=true)
     */
    private $concept_text;

    /**
     * @var string
     *
     * @ORM\Column(name="knowledge_text", type="text", nullable=true)
     */
    private $knowledge_text;

    /** Project that resulted
     * @ORM\OneToMany(targetEntity="meta\StandardProjectProfileBundle\Entity\StandardProject", mappedBy="originalIdea")
     **/
    private $resultingProjects;

    /**
     * @var boolean
     *
     * @ORM\Column(name="archived", type="boolean", nullable=true)
     */
    private $archived;


    /**
     * Users watching me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", mappedBy="ideasWatched")
     **/
    private $watchers;

    /**
     * Creators of the idea (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", mappedBy="ideasCreated")
     **/
    private $creators;

    /**
     * Users participating in me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", mappedBy="ideasParticipatedIn")
     **/
    private $participants;

    /**
     * Log entries about this idea
     * @ORM\OneToMany(targetEntity="meta\GeneralBundle\Entity\Log\IdeaLogEntry", mappedBy="idea")
     **/
    private $logEntries;

    /**
     * Comments on this idea (OWNING SIDE)
     * @ORM\OneToMany(targetEntity="meta\IdeaProfileBundle\Entity\Comment\IdeaComment", mappedBy="idea", cascade="remove")
     * @ORM\OrderBy({"created_at" = "DESC"})
     **/
    private $comments;

    public function __construct()
    {
        
        $this->created_at = $this->updated_at = new \DateTime('now');
        
        $this->watchers = new ArrayCollection();

        $this->creators = new ArrayCollection();
        $this->participants = new ArrayCollection();

        $this->comments = new ArrayCollection();
        $this->resultingProjects = new ArrayCollection();

        $this->archived = false;

    }

    public function getLogName()
    {
        return $this->name;
    }
    public function getLogArgs(){
        return array( 'id' => $this->id );
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Idea
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
     * @return Idea
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
     * Set name
     *
     * @param string $name
     * @return Idea
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
     * @return Idea
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
     * Set concept_text
     *
     * @param string $conceptText
     * @return Idea
     */
    public function setConceptText($conceptText)
    {
        $this->concept_text = $conceptText;
    
        return $this;
    }

    /**
     * Get concept_text
     *
     * @return string 
     */
    public function getConceptText()
    {
        return $this->concept_text;
    }

    /**
     * Set knowledge_text
     *
     * @param string $knowledgeText
     * @return Idea
     */
    public function setKnowledgeText($knowledgeText)
    {
        $this->knowledge_text = $knowledgeText;
    
        return $this;
    }

    /**
     * Get knowledge_text
     *
     * @return string 
     */
    public function getKnowledgeText()
    {
        return $this->knowledge_text;
    }

    /**
     * Set resultingProject
     *
     * @param \meta\StandardProjectProfileBundle\Entity\StandardProject $resultingProject
     * @return Idea
     */
    public function setResultingProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $resultingProject = null)
    {
        $this->resultingProject = $resultingProject;
    
        return $this;
    }

    /**
     * Get resultingProject
     *
     * @return \meta\StandardProjectProfileBundle\Entity\StandardProject 
     */
    public function getResultingProject()
    {
        return $this->resultingProject;
    }

    /**
     * Add watchers
     *
     * @param \meta\UserProfileBundle\Entity\User $watchers
     * @return Idea
     */
    public function addWatcher(\meta\UserProfileBundle\Entity\User $watchers)
    {
        $this->watchers[] = $watchers;
    
        return $this;
    }

    /**
     * Remove watchers
     *
     * @param \meta\UserProfileBundle\Entity\User $watchers
     */
    public function removeWatcher(\meta\UserProfileBundle\Entity\User $watchers)
    {
        $this->watchers->removeElement($watchers);
    }

    /**
     * Set watchers
     *
     * @return Idea 
     */
    public function setWatchers(\Doctrine\Common\Collections\Collection $watchers)
    {
        $this->watchers = $watchers;
        return $this;
    }

    /**
     * Get watchers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getWatchers()
    {
        return $this->watchers;
    }

    /**
     * Get N random watchers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRandomWatchers($limit)
    {
        $sub_array = $this->watchers->slice(0,max(0,$limit));
        shuffle($sub_array);

        return $sub_array;
    }

    /**
     * Set picture
     *
     * @param string $picture
     * @return Idea
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
            return "/bundles/metageneral/img/defaults/idea.png";
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
     * Set archived
     *
     * @param boolean $archived
     * @return Idea
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;
    
        return $this;
    }

    /**
     * Get archived
     *
     * @return boolean 
     */
    public function getArchived()
    {
        return $this->archived;
    }
    public function isArchived()
    {
        return $this->getArchived();
    }

    /**
     * Add participants
     *
     * @param \meta\UserProfileBundle\Entity\User $participant
     * @return Idea
     */
    public function addParticipant(\meta\UserProfileBundle\Entity\User $participant)
    {
        $this->participants[] = $participant;
    
        return $this;
    }

    /**
     * Remove participants
     *
     * @param \meta\UserProfileBundle\Entity\User $participant
     */
    public function removeParticipant(\meta\UserProfileBundle\Entity\User $participant)
    {
        $this->participants->removeElement($participant);
    }

    /**
     * Get participants
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Add logEntries
     *
     * @param \meta\GeneralBundle\Entity\Log\IdeaLogEntry $logEntries
     * @return Idea
     */
    public function addLogEntrie(\meta\GeneralBundle\Entity\Log\IdeaLogEntry $logEntries)
    {
        $this->logEntries[] = $logEntries;
    
        return $this;
    }

    /**
     * Remove logEntries
     *
     * @param \meta\GeneralBundle\Entity\Log\IdeaLogEntry $logEntries
     */
    public function removeLogEntrie(\meta\GeneralBundle\Entity\Log\IdeaLogEntry $logEntries)
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
     * Add comments
     *
     * @param \meta\IdeaProfileBundle\Entity\Comment\IdeaComment $comment
     * @return Idea
     */
    public function addComment(\meta\IdeaProfileBundle\Entity\Comment\IdeaComment $comment)
    {
        $comment->setIdea($this);
        $this->comments[] = $comment;
    
        return $this;
    }

    /**
     * Remove comments
     *
     * @param \meta\IdeaProfileBundle\Entity\Comment\IdeaComment $comment
     */
    public function removeComment(\meta\IdeaProfileBundle\Entity\Comment\IdeaComment $comment)
    {
        $comment->setIdea(null);
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
     * Set slug
     *
     * @param string $slug
     * @return Idea
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
     * Add resultingProjects
     *
     * @param \meta\StandardProjectProfileBundle\Entity\StandardProject $resultingProject
     * @return Idea
     */
    public function addResultingProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $resultingProject)
    {
        $this->resultingProjects[] = $resultingProject;
    
        return $this;
    }

    /**
     * Remove resultingProjects
     *
     * @param \meta\StandardProjectProfileBundle\Entity\StandardProject $resultingProject
     */
    public function removeResultingProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $resultingProject)
    {
        $this->resultingProjects->removeElement($resultingProject);
    }

    /**
     * Get resultingProjects
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getResultingProjects()
    {
        return $this->resultingProjects;
    }

    /**
     * Set about
     *
     * @param string $about
     * @return Idea
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
     * Add creators
     *
     * @param \meta\UserProfileBundle\Entity\User $creators
     * @return Idea
     */
    public function addCreator(\meta\UserProfileBundle\Entity\User $creators)
    {
        $this->creators[] = $creators;
    
        return $this;
    }

    /**
     * Remove creators
     *
     * @param \meta\UserProfileBundle\Entity\User $creators
     */
    public function removeCreator(\meta\UserProfileBundle\Entity\User $creators)
    {
        $this->creators->removeElement($creators);
    }

    /**
     * Get creators
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCreators()
    {
        return $this->creators;
    }
}