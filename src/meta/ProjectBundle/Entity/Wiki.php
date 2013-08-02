<?php

namespace meta\ProjectBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\ProjectBundle\Entity\Wiki
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
     * BINDING LOGIC IS DONE IN 'STANDARDPROJECT' CLASS
     * @param \meta\ProjectBundle\Entity\StandardProject $project
     * @return Wiki
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
     * Add pages
     *
     * @param \meta\ProjectBundle\Entity\WikiPage $page
     * @return Wiki
     */
    public function addPage(\meta\ProjectBundle\Entity\WikiPage $page)
    {
        if (!is_null($page)){
            $page->setWiki($this);
        }
        $this->pages[] = $page;
    
        return $this;
    }

    /**
     * Remove pages
     *
     * @param \meta\ProjectBundle\Entity\WikiPage $page
     */
    public function removePage(\meta\ProjectBundle\Entity\WikiPage $page)
    {
        if (!is_null($page)){
            $page->setWiki(null);
        }
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
     * NO REVERSE BINDING
     * @param \meta\ProjectBundle\Entity\WikiPage $homePage
     * @return Wiki
     */
    public function setHomePage(\meta\ProjectBundle\Entity\WikiPage $homePage = null)
    {
        // No reverse binding necessary
        $this->homePage = $homePage;
        return $this;
    }

    /**
     * Get homePage
     *
     * @return \meta\ProjectBundle\Entity\WikiPage 
     */
    public function getHomePage()
    {
        return $this->homePage;
    }
}