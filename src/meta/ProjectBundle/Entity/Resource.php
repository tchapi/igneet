<?php

namespace meta\ProjectBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

use meta\GeneralBundle\Entity\Behaviour\Taggable;

/**
 * meta\ProjectBundle\Entity\Resource
 *
 * @ORM\Table(name="Resource")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity()
 */
class Resource extends Taggable
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
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @var string $provider
     *
     * @ORM\Column(name="provider", type="string", length=100)
     * @Assert\NotBlank()
     */
    private $provider;

    /**
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=100)
     * @Assert\NotBlank()
     */
    private $type;

    /**
     * @var string $url
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string $original_filename
     *
     * @ORM\Column(name="original_filename", type="string", length=255, nullable=true)
     */
    private $original_filename;

    /**
     * @Assert\File(maxSize="6000000")
     */
    protected $file;

    /**
     * @var date $created_at
     * 
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;  

    /**
     * @var date $updated_at
     * 
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $updated_at;  

    /**
     * @var date $latest_version_uploaded_at
     * 
     * @ORM\Column(name="latest_version_uploaded_at", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $latest_version_uploaded_at;

    /**
     * Project this resource is linked to (REVERSE SIDE)
     * @ORM\ManyToOne(targetEntity="StandardProject", inversedBy="resources")
     **/
    private $project;

    public function __construct()
    {
        $this->type = 'other';
        $this->provider = 'local';
        $this->created_at = $this->updated_at = new \DateTime('now');
    }


    public function getLogName()
    {
        return $this->title;
    }
    public function getLogArgs(){
        return array('id' => $this->id);
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Resource
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->getUrlWebPath();
    }

    public function getAbsoluteUrlPath()
    {
        return null === $this->url
            ? null
            : $this->getUploadRootDir().'/'.$this->url;
    }

    public function getUrlWebPath()
    {

        if (substr($this->url, 0, 4) === "http")
            return $this->url;

        return null === $this->url
            ? null
            : '/'.$this->getUploadDir().'/'.$this->url;
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
        return 'uploads/resources';
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
        if (isset($this->file) && null !== $this->file) {
            // Generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->original_filename = $this->file->getClientOriginalName();
            $this->url = $filename.'.'.$this->file->guessExtension();

            // Updates the date of the latest version of the file
            $this->latest_version_uploaded_at = new \DateTime('now');
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {

        if (isset($this->file)) {

            // if there is an error when moving the file, an exception will
            // be automatically thrown by move(). This will properly prevent
            // the entity from being persisted to the database on error
            $this->file->move($this->getUploadRootDir(), $this->url);

            unset($this->file);

        }

    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ( ($file = $this->getAbsoluteUrlPath()) && 
             $this->original_filename != null) {
            unlink($file);
        }
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->created_at = new \DateTime();
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
     * @return Resource
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Resource
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
     * Set title
     *
     * @param string $title
     * @return Resource
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Resource
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
     * Set project
     *
     * BINDING LOGIC IS DONE IN 'STANDARDPROJECT' CLASS
     * @param \meta\ProjectBundle\Entity\StandardProject $project
     * @return Resource
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
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return Resource
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
     * Set latest_version_uploaded_at
     *
     * @param \DateTime $latestVersionUploadedAt
     * @return Resource
     */
    public function setLatestVersionUploadedAt($latestVersionUploadedAt)
    {
        $this->latest_version_uploaded_at = $latestVersionUploadedAt;
        return $this;
    }

    /**
     * Get latest_version_uploaded_at
     *
     * @return \DateTime 
     */
    public function getLatestVersionUploadedAt()
    {
        return $this->latest_version_uploaded_at;
    }
 
    /**
     * Set original_filename
     *
     * @param string $originalFilename
     * @return Resource
     */
    public function setOriginalFilename($originalFilename)
    {
        $this->original_filename = $originalFilename;
        return $this;
    }

    /**
     * Get original_filename
     *
     * @return string 
     */
    public function getOriginalFilename()
    {
        return $this->original_filename;
    }

    /**
     * Set provider
     *
     * @param string $provider
     * @return Resource
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Get provider
     *
     * @return string 
     */
    public function getProvider()
    {
        return $this->provider;
    }
}