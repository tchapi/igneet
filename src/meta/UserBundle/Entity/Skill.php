<?php

namespace meta\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * meta\UserBundle\Entity\Skill
 *
 * @ORM\Table(name="Skill")
 * @ORM\Entity(repositoryClass="meta\UserBundle\Entity\SkillRepository")
 * @UniqueEntity("slug")
 */
class Skill
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
     * @var string $slug
     *
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     */
    private $slug;

    /**
     * @var string $color
     *
     * @ORM\Column(name="color", type="string", length=7, nullable=true)
     */
    private $color;


    /**
     * Bidirectional - Many skills are possessed by many users (INVERSE SIDE)
     *
     * @ORM\ManyToMany(targetEntity="User", mappedBy="skills")
     */
    private $skilledUsers;

    /**
     * Bidirectional - Many skills are possessed by many standard projects (INVERSE SIDE)
     *
     * @ORM\ManyToMany(targetEntity="meta\ProjectBundle\Entity\StandardProject", mappedBy="neededSkills")
     */
    private $skilledStandardProjects;

    public function __construct()
    {
        $this->skilledUsers = new ArrayCollection();
        $this->skilledStandardProjects = new ArrayCollection();
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
     * Set slug
     *
     * @param string $slug
     * @return Skill
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
     * Returns the internationalized string (for AJAX Calls)
     */
    public function getI18nSlug()
    {
        return $this->slug.'.name';

    }

    /**
     * Set color
     *
     * @param string $color
     * @return Skill
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Get color
     *
     * @return string 
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Add skilledUser
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS 
     * @param meta\UserBundle\Entity\User $skilledUser
     * @return Skill
     */
    public function addSkilledUser(\meta\UserBundle\Entity\User $skilledUser)
    {
        $this->skilledUsers[] = $skilledUser;
        return $this;
    }

    /**
     * Remove skilledUser
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS 
     * @param meta\UserBundle\Entity\User $skilledUser
     */
    public function removeSkilledUser(\meta\UserBundle\Entity\User $skilledUser)
    {
        $this->skilledUsers->removeElement($skilledUser);
    }

    /**
     * Get skilledUsers
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getSkilledUsers()
    {
        return $this->skilledUsers;
    }

    /**
     * Add skilledStandardProject
     *
     * BINDING LOGIC IS DONE IN 'STANDARDPROJECT' CLASS 
     * @param meta\ProjectBundle\Entity\StandardProject $skilledStandardProject
     * @return Skill
     */
    public function addSkilledStandardProject(\meta\ProjectBundle\Entity\StandardProject $skilledStandardProject)
    {
        $this->skilledStandardProjects[] = $skilledStandardProject;
        return $this;
    }

    /**
     * Remove skilledStandardProject
     *
     * BINDING LOGIC IS DONE IN 'STANDARDPROJECT' CLASS 
     * @param meta\ProjectBundle\Entity\StandardProject $skilledStandardProject
     */
    public function removeSkilledStandardProject(\meta\ProjectBundle\Entity\StandardProject $skilledStandardProject)
    {
        $this->skilledStandardProjects->removeElement($skilledStandardProject);
    }

    /**
     * Get skilledStandardProjects
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getSkilledStandardProjects()
    {
        return $this->skilledStandardProjects;
    }
}
