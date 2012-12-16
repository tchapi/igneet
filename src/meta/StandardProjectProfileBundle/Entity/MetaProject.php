<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MetaProject
 *
 * @ORM\Entity
 */
class MetaProject extends StandardProject
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
     * Projects in this meta
     * @ORM\OneToMany(targetEntity="StandardProject", mappedBy="meta")
     **/
    private $projects;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->projects = new ArrayCollection();
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
     * Add project
     *
     * @param \meta\StandardProjectProfileBundle\Entity\StandardProject $project
     * @return MetaProject
     */
    public function addProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $project)
    {
        $project->setMeta($this);
        $this->projects[] = $project;
    
        return $this;
    }

    /**
     * Remove project
     *
     * @param \meta\StandardProjectProfileBundle\Entity\StandardProject $project
     */
    public function removeProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $project)
    {
        $project->setMeta(null);
        $this->projects->removeElement($project);
    }

    /**
     * Get projects
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProjects()
    {
        return $this->projects;
    }
}