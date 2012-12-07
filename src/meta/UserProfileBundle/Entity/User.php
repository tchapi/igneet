<?php

namespace meta\UserProfileBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\UserProfileBundle\Entity\User
 *
 * @ORM\Table(name="User")
 * @ORM\Entity(repositoryClass="meta\UserProfileBundle\Entity\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 */
class User implements UserInterface
{
    
    private static $gravatar_default_style = 'retro';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $username
     *
     * @ORM\Column(name="username", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3")
     * @Assert\Regex(pattern="/[a-zA-Z]{1}[a-zA-Z0-9\-]+/")
     */
    private $username;

    /**
     * @var string $password
     *
     * @ORM\Column(name="password", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3")
     */
    private $password;
 
    /**
     * @var string $salf
     *
     * @ORM\Column(name="salt", type="string", length=255)
     */
    private $salt;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     * @Assert\Email()
     * @Assert\NotBlank()
     */
    private $email;

    /**
     * @var string $first_name
     *
     * @ORM\Column(name="first_name", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     */
    private $first_name;

    /**
     * @var string $last_name
     *
     * @ORM\Column(name="last_name", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     */
    private $last_name;

    /**
     * @var string $city
     *
     * @ORM\Column(name="city", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max = 255)
     */
    private $city;

    /**
     * @var string $avatar
     *
     * @ORM\Column(name="avatar", type="string", length=255, nullable=true)
     * @Assert\Url()
     */
    private $avatar;

    /**
     * @var date $created_at
     * 
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;  
    
    /**
     * @var date $updated_at
     * 
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $updated_at;  

    /**
     * @var string $headline
     *
     * @ORM\Column(name="headline", type="string", length=40, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(max = 40)
     */
    private $headline;

    /**
     * @var text $about
     *
     * @ORM\Column(name="about", type="text", nullable=true)
     */
    private $about;

    /**
     * @var array $roles
     *
     * @ORM\Column(name="roles", type="array")
     */
    private $roles;

    /**
     * Skills I have (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="Skill", inversedBy="skilledUsers")
     * @ORM\JoinTable(name="User_has_Skill",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="skill_id", referencedColumnName="id")}
     *      )
     **/
    private $skills;

    /**
     * Users I follow (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="User", inversedBy="followers")
     * @ORM\JoinTable(name="Follow_Relationships",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="following_user_id", referencedColumnName="id")}
     *      )
     **/
    private $following;

    /**
     * Users following me (REVERSE SIDE)
     * @ORM\ManyToMany(targetEntity="User", mappedBy="following")
     **/
    private $followers;

    /**
     * Projects I own (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\StandardProjectProfileBundle\Entity\StandardProject", inversedBy="owners")
     * @ORM\JoinTable(name="User_owns_StandardProject",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="standard_project_id", referencedColumnName="id")}
     *      )
     **/
    private $projectsOwned;

    /**
     * Projects I participate in (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\StandardProjectProfileBundle\Entity\StandardProject", inversedBy="participants")
     * @ORM\JoinTable(name="User_participatesIn_StandardProject",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="standard_project_id", referencedColumnName="id")}
     *      )
     **/
    private $projectsParticipatedIn;

    /**
     * Projects I watch (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\StandardProjectProfileBundle\Entity\StandardProject", inversedBy="watchers")
     * @ORM\JoinTable(name="User_watches_StandardProject",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="standard_project_id", referencedColumnName="id")}
     *      )
     **/
    private $projectsWatched;


    public function __construct() {
        /* Links to Skills */
        $this->skills = new ArrayCollection();
        /* Links to Network of Users */
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
        /* Links to Standard Projects */
        $this->projectsOwned = new ArrayCollection();
        $this->projectsParticipatedIn = new ArrayCollection();
        $this->projectsWatched = new ArrayCollection();

        /* init */
        $this->salt = md5(uniqid(null, true));
        $this->roles = array('ROLE_USER');
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
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set first_name
     *
     * @param string $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->first_name = $firstName;
    
        return $this;
    }

    /**
     * Get first_name
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set last_name
     *
     * @param string $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->last_name = $lastName;
    
        return $this;
    }

    /**
     * Get last_name
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return User
     */
    public function setCity($city)
    {
        $this->city = $city;
    
        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set avatar
     *
     * @param string $avatar
     * @return User
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    
        return $this;
    }

    /**
     * Get avatar
     *
     * @return string 
     */
    public function getAvatar()
    {

        if ($this->avatar == ""){
           
            $hash = md5(strtolower(trim($this->email)));
            return "http://www.gravatar.com/avatar/".$hash."?s=150&d=".self::$gravatar_default_style;
        
        } else {

            return $this->avatar;
        
        }

    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->created_at = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue()
    {
        $this->updated_at = new \DateTime();
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return User
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
     * @return User
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
     * Set headline
     *
     * @param string $headline
     * @return User
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
     * Set about
     *
     * @param string $about
     * @return User
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
     * Add skills
     *
     * @param meta\UserProfileBundle\Entity\Skill $skills
     * @return User
     */
    public function addSkill(\meta\UserProfileBundle\Entity\Skill $skill)
    {
        $skill->addSkilledUser($this);
        $this->skills[] = $skill;
    
        return $this;
    }

    /**
     * Remove skills
     *
     * @param meta\UserProfileBundle\Entity\Skill $skills
     */
    public function removeSkill(\meta\UserProfileBundle\Entity\Skill $skill)
    {
        $this->skills->removeElement($skill);
        $skill->removeSkilledUser($this);
    }

    /**
     * Get skills
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getSkills()
    {
        return $this->skills;
    }

    /**
     * Set skills
     *
     * @param Array $skills
     * @return Skill
     */
    public function setSkills($skills)
    {
        $this->skills = $skills;

        return $this;
    }

    /**
     * Add followers
     *
     * @param meta\UserProfileBundle\Entity\User $followers
     * @return User
     */
    public function addFollower(\meta\UserProfileBundle\Entity\User $follower)
    {
        $this->followers[] = $follower;
    
        return $this;
    }

    /**
     * Remove followers
     *
     * @param meta\UserProfileBundle\Entity\User $followers
     */
    public function removeFollower(\meta\UserProfileBundle\Entity\User $follower)
    {
        $this->followers->removeElement($follower);
    }

    /**
     * Get followers
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getFollowers()
    {
        return $this->followers;
    }

    /**
     * Add following
     *
     * @param meta\UserProfileBundle\Entity\User $following
     * @return User
     */
    public function addFollowing(\meta\UserProfileBundle\Entity\User $following)
    {
        $following->addFollower($this);
        $this->following[] = $following;
    
        return $this;
    }

    /**
     * Remove following
     *
     * @param meta\UserProfileBundle\Entity\User $following
     */
    public function removeFollowing(\meta\UserProfileBundle\Entity\User $following)
    {
        $this->following->removeElement($following);
        $following->removeFollower($this);
    }

    /**
     * Get following
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getFollowing()
    {
        return $this->following;
    }

    /**
     * Is already following
     *
     * @return boolean 
     */
    public function isFollowing(\meta\UserProfileBundle\Entity\User $user)
    {
        return $this->following->contains($user);
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    
        return $this;
    }

    /**
     * Get salt
     *
     * @return string 
     */
    public function getSalt()
    {
        return $this->salt;
    }

    public function eraseCredentials()
    {
        // We're going to be using hashed passwords, so we don't have to do anything here.
        return;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;
    
        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set roles
     *
     * @param array $roles
     * @return User
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    
        return $this;
    }

    /**
     * Get roles
     *
     * @return array 
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Add projectsOwned
     *
     * @param meta\StandardProjectProfileBundle\Entity\StandardProject $projectOwned
     * @return User
     */
    public function addProjectsOwned(\meta\StandardProjectProfileBundle\Entity\StandardProject $projectOwned)
    {
        $projectOwned->addOwner($this);
        $this->projectsOwned[] = $projectOwned;
    
        return $this;
    }

    /**
     * Remove projectsOwned
     *
     * @param meta\StandardProjectProfileBundle\Entity\StandardProject $projectOwned
     */
    public function removeProjectsOwned(\meta\StandardProjectProfileBundle\Entity\StandardProject $projectOwned)
    {
        $this->projectsOwned->removeElement($projectOwned);
        $projectOwned->removeOwner($this);
    }

    /**
     * Get projectsOwned
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getProjectsOwned()
    {
        return $this->projectsOwned;
    }

    /**
     * Is already owning a standard project
     *
     * @return boolean 
     */
    public function isOwning(\meta\StandardProjectProfileBundle\Entity\StandardProject $project)
    {
        return $this->projectsOwned->contains($project);
    }

    /**
     * Add projectsParticipatedIn
     *
     * @param meta\StandardProjectProfileBundle\Entity\StandardProject $projectParticipatedIn
     * @return User
     */
    public function addProjectsParticipatedIn(\meta\StandardProjectProfileBundle\Entity\StandardProject $projectParticipatedIn)
    {
        $projectParticipatedIn->addParticipant($this);
        $this->projectsParticipatedIn[] = $projectParticipatedIn;
    
        return $this;
    }

    /**
     * Remove projectsParticipatedIn
     *
     * @param meta\StandardProjectProfileBundle\Entity\StandardProject $projectParticipatedIn
     */
    public function removeProjectsParticipatedIn(\meta\StandardProjectProfileBundle\Entity\StandardProject $projectsParticipatedIn)
    {
        $this->projectsParticipatedIn->removeElement($projectsParticipatedIn);
        $projectParticipatedIn->removeParticipant($this);
    }

    /**
     * Get projectsParticipatedIn
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getProjectsParticipatedIn()
    {
        return $this->projectsParticipatedIn;
    }

    /**
     * Is already participating in a standard project
     *
     * @return boolean 
     */
    public function isParticipatingIn(\meta\StandardProjectProfileBundle\Entity\StandardProject $project)
    {
        return $this->projectsParticipatedIn->contains($project);
    }

    /**
     * Add projectsWatched
     *
     * @param meta\StandardProjectProfileBundle\Entity\StandardProject $projectWatched
     * @return User
     */
    public function addProjectsWatched(\meta\StandardProjectProfileBundle\Entity\StandardProject $projectWatched)
    {
        $projectWatched->addWatcher($this);
        $this->projectsWatched[] = $projectWatched;
    
        return $this;
    }

    /**
     * Remove projectsWatched
     *
     * @param meta\StandardProjectProfileBundle\Entity\StandardProject $projectWatched
     */
    public function removeProjectsWatched(\meta\StandardProjectProfileBundle\Entity\StandardProject $projectWatched)
    {
        $this->projectsWatched->removeElement($projectWatched);
        $projectWatched->removeWatcher($this);
    }

    /**
     * Get projectsWatched
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getProjectsWatched()
    {
        return $this->projectsWatched;
    }

    /**
     * Is already watching a standard project
     *
     * @return boolean 
     */
    public function isWatching(\meta\StandardProjectProfileBundle\Entity\StandardProject $project)
    {
        return $this->projectsWatched->contains($project);
    }
}