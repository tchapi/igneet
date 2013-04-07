<?php

namespace meta\GeneralBundle\Entity\Community;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Community
 * @ORM\Table(name="Community")
 * @ORM\Entity(repositoryClass="meta\GeneralBundle\Entity\Community\CommunityRepository")
 *
 */
class Community
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string $headline
     *
     * @ORM\Column(name="headline", type="string", length=255, nullable=true)
     */
    private $headline;
    
    /**
     * @var \DateTime $created_at
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;

    /**
     * Projects in this community
     * @ORM\OneToMany(targetEntity="meta\ProjectBundle\Entity\StandardProject", mappedBy="community")
     **/
    private $projects;
    
    /**
     * Ideas in this community
     * @ORM\OneToMany(targetEntity="meta\IdeaBundle\Entity\Idea", mappedBy="community")
     **/
    private $ideas;
    
    /**
     * Users in this community
     * @ORM\ManyToMany(targetEntity="meta\UserBundle\Entity\User", mappedBy="communities")
     **/
    private $users;

    /**
     * Guests in this community
     * @ORM\ManyToMany(targetEntity="meta\UserBundle\Entity\User", mappedBy="restrictedCommunities")
     **/
    private $guests;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->created_at = new \DateTime('now');

        $this->projects = new ArrayCollection();
        $this->ideas = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->guests = new ArrayCollection();
    }
    
    public function getLogName()
    {
        return $this->name;
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
     * @param \meta\ProjectBundle\Entity\StandardProject $project
     * @return Community
     */
    public function addProject(\meta\ProjectBundle\Entity\StandardProject $project)
    {
        if (!is_null($project)){
            $project->setCommunity($this);
            $this->projects[] = $project;
        }
    
        return $this;
    }

    /**
     * Remove project
     *
     * @param \meta\ProjectBundle\Entity\StandardProject $project
     */
    public function removeProject(\meta\ProjectBundle\Entity\StandardProject $project)
    {
        if (!is_null($project)){
            $project->setCommunity(null);
            $this->projects->removeElement($project);
        }
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

    /**
     * Add idea
     *
     * @param \meta\IdeaBundle\Entity\Idea $idea
     * @return Community
     */
    public function addIdea(\meta\IdeaBundle\Entity\Idea $idea)
    {
        if (!is_null($idea)){
            $idea->setCommunity($this);
            $this->ideas[] = $idea;
        }
    
        return $this;
    }

    /**
     * Remove idea
     *
     * @param \meta\IdeaBundle\Entity\Idea $idea
     */
    public function removeIdea(\meta\IdeaBundle\Entity\Idea $idea)
    {
        if (!is_null($idea)){
            $idea->setCommunity(null);
            $this->ideas->removeElement($idea);
        }
    }

    /**
     * Get ideas
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getIdeas()
    {
        return $this->ideas;
    }

    /**
     * Add user
     *
     * @param \meta\UserBundle\Entity\User $user
     * @return Community
     */
    public function addUser(\meta\UserBundle\Entity\User $user)
    {
        if (!is_null($user)){
            $user->addCommunitie($this);
            $this->users[] = $user;
        }
    
        return $this;
    }

    /**
     * Remove user
     *
     * @param \meta\UserBundle\Entity\User $user
     */
    public function removeUser(\meta\UserBundle\Entity\User $user)
    {
        if (!is_null($user)){
            $user->removeCommunitie($this);
            $this->users->removeElement($user);
        }
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add guest
     *
     * @param \meta\UserBundle\Entity\User $guest
     * @return Community
     */
    public function addGuest(\meta\UserBundle\Entity\User $guest)
    {
        if (!is_null($guest)){
            $guest->addRestrictedCommunitie($this);
            $this->guests[] = $guest;
        }

        return $this;
    }

    /**
     * Remove guest
     *
     * @param \meta\UserBundle\Entity\User $guest
     */
    public function removeGuest(\meta\UserBundle\Entity\User $guest)
    {
        if (!is_null($guest)){
            $guest->removeRestrictedCommunitie($this);
            $this->guests->removeElement($guest);
        }
    }

    /**
     * Get guests
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getGuests()
    {
        return $this->guests;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Community
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
     * @return Community
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Community
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
}