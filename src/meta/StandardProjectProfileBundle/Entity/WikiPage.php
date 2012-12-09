<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
}