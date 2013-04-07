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
     * @var \DateTime $archived_at
     *
     * @ORM\Column(name="archived_at", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $archived_at;

    /**
     * @var \DateTime $deleted_at
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $deleted_at;

    /**
     * Users watching me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserBundle\Entity\User", mappedBy="ideasWatched")
     **/
    private $watchers;

    /**
     * Creators of the idea (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserBundle\Entity\User", inversedBy="ideasCreated")
     * @ORM\JoinTable(name="User_created_Idea")
     **/
    private $creators;

    /**
     * Users participating in me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserBundle\Entity\User", mappedBy="ideasParticipatedIn")
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

    /**
     * Community this idea is linked to
     * @ORM\ManyToOne(targetEntity="meta\GeneralBundle\Entity\Community\Community", inversedBy="ideas")
     **/
    private $community;

    public function __construct()
    {
        
        $this->created_at = $this->updated_at = new \DateTime('now');
        
        $this->watchers = new ArrayCollection();

        $this->creators = new ArrayCollection();
        $this->participants = new ArrayCollection();

        $this->comments = new ArrayCollection();
        $this->resultingProjects = new ArrayCollection();

        $this->community = null;

        $this->archived_at = null;

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
     * @ORM\PreUpdate()
     */
    public function update()
    {
        $this->updated_at = new \DateTime('now');
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
     * BINDING LOGIC IS DONE IN 'STANDARDPROJECT' CLASS 
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
     * Add watcher
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS 
     * @param \meta\UserBundle\Entity\User $watcher
     * @return Idea
     */
    public function addWatcher(\meta\UserBundle\Entity\User $watcher)
    {
        $this->watchers[] = $watcher;
        return $this;
    }

    /**
     * Remove watcher
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS 
     * @param \meta\UserBundle\Entity\User $watcher
     */
    public function removeWatcher(\meta\UserBundle\Entity\User $watcher)
    {
        $this->watchers->removeElement($watcher);
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
     * Count watchers
     *
     * @return integer
     */
    public function countWatchers()
    {
        $count = 0;

        foreach ($this->watchers as $user) {   
            if ( !($user->isDeleted()) ) $count++;
        }

        return $count;
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
     * Set archived_at
     *
     * @param \DateTime  $archivedAt
     * @return Idea
     */
    public function setArchivedAt($archivedAt)
    {
        $this->archived_at = $archivedAt;
        return $this;
    }

    /**
     * Get archived
     *
     * @return \DateTime
     */
    public function getArchivedAt()
    {
        return $this->archived_at;
    }

    public function isArchived()
    {
        return !($this->archived_at === null);
    }

    /**
     * Archive the idea
     *
     * @return Idea
     */
    public function archive()
    {
        $this->archived_at = new \DateTime('now');
        return $this;
    }

    /**
     * Recycle the idea
     *
     * @return Idea
     */
    public function recycle()
    {
        $this->archived_at = null;
        return $this;
    }

    /**
     * Add participants
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS 
     * @param \meta\UserBundle\Entity\User $participant
     * @return Idea
     */
    public function addParticipant(\meta\UserBundle\Entity\User $participant)
    {
        $this->participants[] = $participant;
        return $this;
    }

    /**
     * Remove participants
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS 
     * @param \meta\UserBundle\Entity\User $participant
     */
    public function removeParticipant(\meta\UserBundle\Entity\User $participant)
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
     * Count participants
     *
     * @return integer
     */
    public function countParticipants()
    {
        $count = 0;

        foreach ($this->participants as $user) {   
            if ( !($user->isDeleted()) ) $count++;
        }

        return $count;
    }

    /**
     * Add logEntries
     *
     * BINDING LOGIC IS DONE IN 'IDEALOGENTRY' CLASS 
     * @param \meta\GeneralBundle\Entity\Log\IdeaLogEntry $logEntry
     * @return Idea
     */
    public function addLogEntrie(\meta\GeneralBundle\Entity\Log\IdeaLogEntry $logEntry)
    {
        $this->logEntries[] = $logEntry;
        return $this;
    }

    /**
     * Remove logEntries
     *
     * BINDING LOGIC IS DONE IN 'IDEALOGENTRY' CLASS 
     * @param \meta\GeneralBundle\Entity\Log\IdeaLogEntry $logEntry
     */
    public function removeLogEntrie(\meta\GeneralBundle\Entity\Log\IdeaLogEntry $logEntry)
    {
        $this->logEntries->removeElement($logEntry);
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
        if (!is_null($comment)){
            $comment->setIdea($this);
        }
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
        if (!is_null($comment)){
            $comment->setIdea(null);
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
     * BINDING LOGIC IS DONE IN 'STANDARDPROJECT' CLASS
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
     * BINDING LOGIC IS DONE IN 'STANDARDPROJECT' CLASS
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
     * Add creator
     *
     * @param \meta\UserBundle\Entity\User $creator
     * @return Idea
     */
    public function addCreator(\meta\UserBundle\Entity\User $creator)
    {
        if(!is_null($creator)){
            $creator->addIdeasCreated($this);
        }

        $this->creators[] = $creator;
        return $this;
    }

    /**
     * Remove creator
     *
     * @param \meta\UserBundle\Entity\User $creator
     */
    public function removeCreator(\meta\UserBundle\Entity\User $creator)
    {
        if(!is_null($creator)){
            $creator->removeIdeasCreated($this);
        }
        $this->creators->removeElement($creator);
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

    /**
     * Get a creator
     *
     * @return User
     */
    public function getACreator()
    {
        foreach ($this->creators as $user) {   
            if ( !($user->isDeleted()) ) {
                return $user;
            }
        }
    }

    /**
     * Count creators
     *
     * @return integer
     */
    public function countCreators()
    {
        $count = 0;

        foreach ($this->creators as $user) {   
            if ( !($user->isDeleted()) ) $count++;
        }

        return $count;
    }

    /**
     * Set deleted_at
     *
     * @param \DateTime $deletedAt
     * @return Idea
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
     * @return Idea 
     */
    public function delete()
    {
        $this->deleted_at = new \DateTime('now');
        return $this;
    }

    /* --------------------------------------------------------------------------------------------------------- */

    /**
     * Set community
     * BINDING LOGIC IS DONE IN 'COMMUNITY' CLASS 
     * @param \meta\GeneralBundle\Entity\Community\Community $community
     * @return Idea
     */
    public function setCommunity(\meta\GeneralBundle\Entity\Community\Community $community = null)
    {
        $this->community = $community;
    
        return $this;
    }

    /**
     * Get community
     * BINDING LOGIC IS DONE IN 'COMMUNITY' CLASS 
     * @return \meta\GeneralBundle\Entity\Community\Community 
     */
    public function getCommunity()
    {
        return $this->community;
    }

}