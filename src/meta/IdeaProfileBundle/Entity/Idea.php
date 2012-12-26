<?php

namespace meta\IdeaProfileBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Idea
 *
 * @ORM\Table(name="Idea")
 * @ORM\Entity(repositoryClass="meta\IdeaProfileBundle\Entity\IdeaRepository")
 */
class Idea
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
     * @var string
     *
     * @ORM\Column(name="headline", type="string", length=255, nullable=true)
     */
    private $headline;

    /**
     * @var string $picture
     *
     * @ORM\Column(name="picture", type="string", length=255, nullable=true)
     * @Assert\Url()
     */
    private $picture;
    
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
     * @ORM\OneToOne(targetEntity="meta\StandardProjectProfileBundle\Entity\StandardProject", mappedBy="originalIdea")
     **/
    private $resultingProject;

    /**
     * Users watching me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", mappedBy="ideasWatched")
     **/
    private $watchers;


    public function __construct()
    {
        
        $this->created_at = $this->updated_at = new \DateTime('now');
        $this->watchers = new ArrayCollection();

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
     * Get watchers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getWatchers()
    {
        return $this->watchers;
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

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
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
        $this->file->move($this->getUploadRootDir(), $this->avatar);

        unset($this->file);
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

}