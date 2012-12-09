<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Wiki
 *
 * @ORM\Table(name="Wiki")
 * @ORM\Entity
 */
class Wiki
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
     * Associated project (REVERSE SIDE)
     * @ORM\OneToOne(targetEntity="StandardProject", mappedBy="wiki")
     **/
    private $project;

    /**
     * Pages of the wiki (not necessarily first order children)
     * @ORM\OneToMany(targetEntity="WikiPage", mappedBy="wiki", cascade="remove")
     **/
    private $pages;

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
     * Set project
     *
     * @param \meta\StandardProjectProfileBundle\Entity\StandardProject $project
     * @return Wiki
     */
    public function setProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $project = null)
    {
        $this->project = $project;
    
        return $this;
    }

    /**
     * Get project
     *
     * @return \meta\StandardProjectProfileBundle\Entity\StandardProject 
     */
    public function getProject()
    {
        return $this->project;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pages = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add pages
     *
     * @param \meta\StandardProjectProfileBundle\Entity\WikiPage $pages
     * @return Wiki
     */
    public function addPage(\meta\StandardProjectProfileBundle\Entity\WikiPage $pages)
    {
        $this->pages[] = $pages;
    
        return $this;
    }

    /**
     * Remove pages
     *
     * @param \meta\StandardProjectProfileBundle\Entity\WikiPage $pages
     */
    public function removePage(\meta\StandardProjectProfileBundle\Entity\WikiPage $pages)
    {
        $this->pages->removeElement($pages);
    }

    /**
     * Get pages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPages()
    {
        return $this->pages;
    }
}