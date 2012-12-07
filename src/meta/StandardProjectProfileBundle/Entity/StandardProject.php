<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\StandardProjectProfileBundle\Entity\StandardProject
 *
 * @ORM\Table(name="StandardProject")
 * @ORM\Entity(repositoryClass="meta\StandardProjectProfileBundle\Entity\StandardProjectRepository")
 * @UniqueEntity("slug")
 */
class StandardProject
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
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3")
     * @Assert\Regex(pattern="/[a-zA-Z]{1}[a-zA-Z0-9\-]+/")
     */
    private $slug;

    /**
     * @var string $headline
     *
     * @ORM\Column(name="headline", type="string", length=255, nullable=true)
     */
    private $headline;

    /**
     * @var string $picture
     *
     * @ORM\Column(name="picture", type="string", length=255, nullable=true)
     * @Assert\Url()
     */
    private $picture;

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
     * @var string $about
     *
     * @ORM\Column(name="about", type="text", nullable=true)
     */
    private $about;

    /**
     * Skills I would need as a project (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\Skill", inversedBy="skilledStandardProjects")
     * @ORM\JoinTable(name="StandardProject_need_Skill",
     *      joinColumns={@ORM\JoinColumn(name="standard_project_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="skill_id", referencedColumnName="id")}
     *      )
     **/
    private $neededSkills;

    /**
     * Users owning me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", mappedBy="projectsOwned")
     **/
    private $owners;

    /**
     * Users participating in me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", mappedBy="projectsParticipatedIn")
     **/
    private $participants;

    /**
     * Users watching me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", mappedBy="projectsWatched")
     **/
    private $watchers;

/* ********** */

    /**
     * Common Lists
     * @ORM\OneToMany(targetEntity="CommonList", mappedBy="project")
     **/
    private $commonLists;

/* ********** */

    public function __construct()
    {
        $this->neededSkills = new ArrayCollection();
        $this->owners = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->watchers = new ArrayCollection();

        $this->commonLists = new ArrayCollection();

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
     * @return StandardProject
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
     * Set headline
     *
     * @param string $headline
     * @return StandardProject
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;
    
        return $this;
    }

    /**
     * Get headline
     *
     * @return string 
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * Set picture
     *
     * @param string $picture
     * @return StandardProject
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;
    
        return $this;
    }

    /**
     * Get picture
     *
     * @return string 
     */
    public function getPicture()
    {
        if ($this->picture == "")
            return "/img/defaults/StandardProject.png";
        else
            return $this->picture;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return StandardProject
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
     * @return StandardProject
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
     * Set about
     *
     * @param string $about
     * @return StandardProject
     */
    public function setAbout($about)
    {
        $this->about = $about;
    
        return $this;
    }

    /**
     * Get about
     *
     * @return string 
     */
    public function getAbout()
    {
        return $this->about;
    }

    /**
     * Add neededSkills
     *
     * @param meta\UserProfileBundle\Entity\Skill $neededSkill
     * @return StandardProject
     */
    public function addNeededSkill(\meta\UserProfileBundle\Entity\Skill $neededSkill)
    {
        $neededSkill->addSkilledStandardProject($this);
        $this->neededSkills[] = $neededSkill;
    
        return $this;
    }

    /**
     * Remove neededSkills
     *
     * @param meta\UserProfileBundle\Entity\Skill $neededSkill
     */
    public function removeNeededSkill(\meta\UserProfileBundle\Entity\Skill $neededSkill)
    {
        $this->neededSkills->removeElement($neededSkill);
        $neededSkill->removeSkilledStandardProject($this);
    }

    /**
     * Get neededSkills
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getNeededSkills()
    {
        return $this->neededSkills;
    }

    /**
     * Set neededSkills
     *
     * @param Array $neededSkills
     * @return StandardProject
     */
    public function setNeededSkills($neededSkills)
    {
        $this->neededSkills = $neededSkills;
        
        return $this;
    }

    /**
     * Add owners
     *
     * @param meta\UserProfileBundle\Entity\User $owners
     * @return StandardProject
     */
    public function addOwner(\meta\UserProfileBundle\Entity\User $owners)
    {
        $this->owners[] = $owners;
    
        return $this;
    }

    /**
     * Remove owners
     *
     * @param meta\UserProfileBundle\Entity\User $owners
     */
    public function removeOwner(\meta\UserProfileBundle\Entity\User $owners)
    {
        $this->owners->removeElement($owners);
    }

    /**
     * Get owners
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getOwners()
    {
        return $this->owners;
    }

    /**
     * Add participants
     *
     * @param meta\UserProfileBundle\Entity\User $participants
     * @return StandardProject
     */
    public function addParticipant(\meta\UserProfileBundle\Entity\User $participants)
    {
        $this->participants[] = $participants;
    
        return $this;
    }

    /**
     * Remove participants
     *
     * @param meta\UserProfileBundle\Entity\User $participants
     */
    public function removeParticipant(\meta\UserProfileBundle\Entity\User $participants)
    {
        $this->participants->removeElement($participants);
    }

    /**
     * Get participants
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Add watchers
     *
     * @param meta\UserProfileBundle\Entity\User $watchers
     * @return StandardProject
     */
    public function addWatcher(\meta\UserProfileBundle\Entity\User $watchers)
    {
        $this->watchers[] = $watchers;
    
        return $this;
    }

    /**
     * Remove watchers
     *
     * @param meta\UserProfileBundle\Entity\User $watchers
     */
    public function removeWatcher(\meta\UserProfileBundle\Entity\User $watchers)
    {
        $this->watchers->removeElement($watchers);
    }

    /**
     * Get watchers
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getWatchers()
    {
        return $this->watchers;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return StandardProject
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
     * Add CommonList
     *
     * @param meta\StandardProjectProfileBundle\Entity\CommonList $commonList
     * @return StandardProject
     */
    public function addCommonList(\meta\StandardProjectProfileBundle\Entity\CommonList $commonList)
    {
        $commonList->setProject($this);
        $this->commonLists[] = $commonList;

        return $this;
    }

    /**
     * Remove CommonList
     *
     * @param meta\StandardProjectProfileBundle\Entity\CommonList $commonList
     */
    public function removeCommonList(\meta\StandardProjectProfileBundle\Entity\CommonList $commonList)
    {
        $this->commonLists->removeElement($commonList);
        $commonList->removeProject($this);
    }

    /**
     * Get CommonLists
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getCommonLists()
    {
        return $this->commonLists;
    }

    /**
     * Has commonList
     *
     * @return boolean
     */
    public function hasCommonList(\meta\StandardProjectProfileBundle\Entity\CommonList $commonList)
    {
        return $this->commonLists->contains($commonList);
    }
}