<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\StandardProjectProfileBundle\Entity\Wiki
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
     * Home Page of the wiki or null
     * @ORM\OneToOne(targetEntity="WikiPage", cascade="remove")
     **/
    private $homePage;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pages = new ArrayCollection();
        $this->homePage = null;
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
     * Add pages
     *
     * @param \meta\StandardProjectProfileBundle\Entity\WikiPage $page
     * @return Wiki
     */
    public function addPage(\meta\StandardProjectProfileBundle\Entity\WikiPage $page)
    {
        $page->setWiki($this);
        $this->pages[] = $page;
    
        return $this;
    }

    /**
     * Remove pages
     *
     * @param \meta\StandardProjectProfileBundle\Entity\WikiPage $page
     */
    public function removePage(\meta\StandardProjectProfileBundle\Entity\WikiPage $page)
    {
        $this->pages->removeElement($page);
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

    /**
     * Set homePage
     *
     * @param \meta\StandardProjectProfileBundle\Entity\WikiPage $homePage
     * @return Wiki
     */
    public function setHomePage(\meta\StandardProjectProfileBundle\Entity\WikiPage $homePage = null)
    {
        $this->homePage = $homePage;
    
        return $this;
    }

    /**
     * Get homePage
     *
     * @return \meta\StandardProjectProfileBundle\Entity\WikiPage 
     */
    public function getHomePage()
    {
        return $this->homePage;
    }
}