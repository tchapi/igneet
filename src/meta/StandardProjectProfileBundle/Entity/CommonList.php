<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\StandardProjectProfileBundle\Entity\List
 *
 * @ORM\Table(name="CommonList")
 * @ORM\Entity(repositoryClass="meta\StandardProjectProfileBundle\Entity\CommonListRepository")
 */
class CommonList
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
     * @var string $name
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
     * @var string $description
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

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
     * Common List Items
     * @ORM\OneToMany(targetEntity="CommonListItem", mappedBy="commonList", cascade="remove")
     **/
    private $items;

    /**
     * Project this common list is linked to (REVERSE SIDE)
     * @ORM\ManyToOne(targetEntity="StandardProject", inversedBy="commonLists")
     **/
    private $project;

    /**
     * Comments on this page (OWNING SIDE)
     * @ORM\OneToMany(targetEntity="meta\StandardProjectProfileBundle\Entity\Comment\CommonListComment", mappedBy="commonList", cascade="remove")
     * @ORM\OrderBy({"created_at" = "DESC"})
     **/
    private $comments;

    public function __construct(){

        $this->comments = new ArrayCollection();

        $this->items = new ArrayCollection();
        $this->created_at = $this->updated_at = new \DateTime('now');

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
     * @return List
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
     * Set description
     *
     * @param string $description
     * @return List
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * Set slug
     *
     * @param string $slug
     * @return CommonList
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
     * Add items
     *
     * @param meta\StandardProjectProfileBundle\Entity\CommonListItem $item
     * @return CommonList
     */
    public function addItem(\meta\StandardProjectProfileBundle\Entity\CommonListItem $item)
    {
        $item->setCommonList($this);
        $this->items[] = $item;
    
        return $this;
    }

    /**
     * Remove items
     *
     * @param meta\StandardProjectProfileBundle\Entity\CommonListItem $item
     */
    public function removeItem(\meta\StandardProjectProfileBundle\Entity\CommonListItem $item)
    {
        $this->items->removeElement($item);
    }

    /**
     * Get items
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set project
     *
     * @param meta\StandardProjectProfileBundle\Entity\StandardProject $project
     * @return CommonList
     */
    public function setProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $project = null)
    {
        $this->project = $project;
    
        return $this;
    }

    /**
     * Get project
     *
     * @return meta\StandardProjectProfileBundle\Entity\StandardProject 
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return CommonList
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
     * @return CommonList
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
     * Add comments
     *
     * @param \meta\StandardProjectProfileBundle\Entity\Comment\CommonListComment $comment
     * @return CommonList
     */
    public function addComment(\meta\StandardProjectProfileBundle\Entity\Comment\CommonListComment $comment)
    {
        $comment->setCommonList($this);
        $this->comments[] = $comment;
    
        return $this;
    }

    /**
     * Remove comments
     *
     * @param \meta\StandardProjectProfileBundle\Entity\Comment\CommonListComment $comment
     */
    public function removeComment(\meta\StandardProjectProfileBundle\Entity\Comment\CommonListComment $comment)
    {
        $comment->setCommonList(null);
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
}