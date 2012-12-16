<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\StandardProjectProfileBundle\Entity\WikiPage
 *
 * @ORM\Table(name="WikiPage")
 * @ORM\Entity(repositoryClass="meta\StandardProjectProfileBundle\Entity\WikiPageRepository")
 */
class WikiPage
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
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    private $slug;

    /**
     * @var text $content
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

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
     * Wiki this page is linked to (REVERSE SIDE)
     * @ORM\ManyToOne(targetEntity="Wiki", inversedBy="pages")
     **/
    private $wiki;

    /**
     * Parent of this page
     * @ORM\ManyToOne(targetEntity="WikiPage", inversedBy="children")
     **/
    private $parent;

    /**
     * Children of this pages (first order children)
     * @ORM\OneToMany(targetEntity="WikiPage", mappedBy="parent")
     **/
    private $children;

    public function __construct() {

        $this->created_at = $this->updated_at = new \DateTime('now');

        $this->children = new ArrayCollection();
        $this->parent = null;
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
     * Set wiki
     *
     * @param \stdClass $wiki
     * @return WikiPage
     */
    public function setWiki($wiki)
    {
        $this->wiki = $wiki;
    
        return $this;
    }

    /**
     * Get wiki
     *
     * @return \stdClass 
     */
    public function getWiki()
    {
        return $this->wiki;
    }

    /**
     * Set parent
     *
     * @param \meta\StandardProjectProfileBundle\Entity\WikiPage $parent
     * @return WikiPage
     */
    public function setParent(\meta\StandardProjectProfileBundle\Entity\WikiPage $parent = null)
    {
        $this->parent = $parent;
    
        return $this;
    }

    /**
     * Get parent
     *
     * @return \meta\StandardProjectProfileBundle\Entity\WikiPage 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add children
     *
     * @param \meta\StandardProjectProfileBundle\Entity\WikiPage $children
     * @return WikiPage
     */
    public function addChildren(\meta\StandardProjectProfileBundle\Entity\WikiPage $children)
    {
        $this->children[] = $children;
    
        return $this;
    }

    /**
     * Remove children
     *
     * @param \meta\StandardProjectProfileBundle\Entity\WikiPage $children
     */
    public function removeChildren(\meta\StandardProjectProfileBundle\Entity\WikiPage $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return WikiPage
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
     * Set content
     *
     * @param string $content
     * @return WikiPage
     */
    public function setContent($content)
    {
        $this->content = $content;
    
        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return WikiPage
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
     * Set rootWiki
     *
     * @param \meta\StandardProjectProfileBundle\Entity\Wiki $rootWiki
     * @return WikiPage
     */
    public function setRootWiki(\meta\StandardProjectProfileBundle\Entity\Wiki $rootWiki = null)
    {
        $this->rootWiki = $rootWiki;
    
        return $this;
    }

    /**
     * Get rootWiki
     *
     * @return \meta\StandardProjectProfileBundle\Entity\Wiki 
     */
    public function getRootWiki()
    {
        return $this->rootWiki;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return WikiPage
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
     * @return WikiPage
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
}