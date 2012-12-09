<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * WikiPage
 *
 * @ORM\Table()
 * @ORM\Entity
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
     * @var text $content
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;

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
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
}