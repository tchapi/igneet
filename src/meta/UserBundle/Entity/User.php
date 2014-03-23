<?php

namespace meta\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity,
    Symfony\Component\Security\Core\User\AdvancedUserInterface,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\UserBundle\Entity\User
 *
 * @ORM\Table(name="User")
 * @ORM\Entity(repositoryClass="meta\UserBundle\Entity\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields="username", message="user.already.taken")
 * @UniqueEntity(fields="email", message="user.email.registered")
 */
class User implements AdvancedUserInterface
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
     * @ORM\Column(name="username", type="string", length=255, unique=true, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3")
     * @Assert\Regex(pattern="/^[a-zA-Z0-9\-]+$/")
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
     * @ORM\Column(name="email", type="string", length=255, unique=true, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @var string $first_name
     *
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
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
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     * @Assert\Length(max = 255)
     */
    private $city;

    /**
     * @var string $avatar
     *
     * @ORM\Column(name="avatar", type="string", length=255, nullable=true)
     */
    private $avatar;

    /**
     * @Assert\File(maxSize="10000000")
     */
    private $file;

    /**
     *  The token, if any, that th user used to sign up
     *
     * @ORM\OneToOne(targetEntity="UserInviteToken", mappedBy="resultingUser")
     */
    private $originatingToken;

    /**
     *  The tokens created by this user
     *
     * @ORM\OneToMany(targetEntity="UserInviteToken", mappedBy="referalUser")
     */
    private $createdTokens;

    /**
     * @var string $token
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=true)
     */
    private $token;

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
     * @var date $last_seen_at
     * 
     * @ORM\Column(name="last_seen_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $last_seen_at;  

    /**
     * @var date $last_notified_at
     * 
     * @ORM\Column(name="last_notified_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $last_notified_at;  

    /**
     * @var \DateTime $deleted_at
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $deleted_at;
    
    /**
     * @var string $headline
     *
     * @ORM\Column(name="headline", type="string", length=100, nullable=true)
     * @Assert\Length(max = 100)
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
     * @ORM\ManyToMany(targetEntity="meta\ProjectBundle\Entity\StandardProject", inversedBy="owners")
     * @ORM\JoinTable(name="User_owns_StandardProject",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="standard_project_id", referencedColumnName="id")}
     *      )
     **/
    private $projectsOwned;

    /**
     * Projects I participate in (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\ProjectBundle\Entity\StandardProject", inversedBy="participants")
     * @ORM\JoinTable(name="User_participatesIn_StandardProject",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="standard_project_id", referencedColumnName="id")}
     *      )
     **/
    private $projectsParticipatedIn;

    /**
     * Projects I watch (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\ProjectBundle\Entity\StandardProject", inversedBy="watchers")
     * @ORM\JoinTable(name="User_watches_StandardProject",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="standard_project_id", referencedColumnName="id")}
     *      )
     **/
    private $projectsWatched;

    /**
     * Ideas I watch (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\IdeaBundle\Entity\Idea", inversedBy="watchers")
     * @ORM\JoinTable(name="User_watches_Idea",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="idea_id", referencedColumnName="id")}
     *      )
     **/
    private $ideasWatched;

    /**
     * Ideas I have created (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\IdeaBundle\Entity\Idea", mappedBy="creators")
     **/
    private $ideasCreated;

    /**
     * Ideas I participate in (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\IdeaBundle\Entity\Idea", inversedBy="participants")
     * @ORM\JoinTable(name="User_participatesIn_Idea")
     **/
    private $ideasParticipatedIn;
    
    /**
     * Comments I created 
     * @ORM\OneToMany(targetEntity="meta\GeneralBundle\Entity\Comment\BaseComment", mappedBy="user")
     **/
    private $comments;

    /**
     * Comments validated by this user
     * @ORM\ManyToMany(targetEntity="meta\GeneralBundle\Entity\Comment\BaseComment", mappedBy="validators")
     **/
    private $validatedComments;

    /**
     * Log entries I created 
     * @ORM\OneToMany(targetEntity="meta\GeneralBundle\Entity\Log\BaseLogEntry", mappedBy="user")
     **/
    private $initiatedLogEntries;

    /**
     * User in subject : user 
     * @ORM\OneToMany(targetEntity="meta\GeneralBundle\Entity\Log\UserLogEntry", mappedBy="other_user")
     **/
    private $logEntries;

    /**
     * Communities this user is in
     * @ORM\OneToMany(targetEntity="meta\UserBundle\Entity\UserCommunity", mappedBy="user")
     **/
    private $userCommunities;

    /**
     * Current Community this user is in
     * @ORM\ManyToOne(targetEntity="meta\GeneralBundle\Entity\Community\Community")
     **/
    private $currentCommunity;

    /**
     * Preferred Language for the user interface
     * @ORM\Column(name="culture", type="string", length=5, nullable=true)
     **/
    private $preferredLanguage;

    /**
     * @var boolean $enableDigest
     *
     * @ORM\Column(name="enableDigest", type="boolean", nullable=true)
     */
    private $enableDigest;

    /**
     * @var string $digestFrequency
     *
     * @ORM\Column(name="digest_frequency", type="string", length=15, nullable=true)
     * @Assert\Length(max = 15)
     */
    private $digestFrequency;

    /**
     * @var boolean $enableSpecificDay
     *
     * @ORM\Column(name="enableSpecificDay", type="boolean", nullable=true)
     */
    private $enableSpecificDay;

    /**
     * @var string $digestDay
     *
     * @ORM\Column(name="digest_day", type="string", length=15, nullable=true)
     * @Assert\Length(max = 15)
     */
    private $digestDay;

    /**
     * @var boolean $enableSpecificEmails
     *
     * @ORM\Column(name="enableSpecificEmails", type="boolean", nullable=true)
     */
    private $enableSpecificEmails;

    /**
     * Announcements this user must see
     * @ORM\ManyToMany(targetEntity="meta\AdminBundle\Entity\Announcement", mappedBy="targetedUsers")
     **/
    private $targetedAnnouncements;

    /**
     * Announcements this user has seen
     * @ORM\ManyToMany(targetEntity="meta\AdminBundle\Entity\Announcement", mappedBy="hitUsers")
     **/
    private $viewedAnnouncements;


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
        /* Links to ideas */
        $this->ideasWatched = new ArrayCollection();
        $this->ideasCreated = new ArrayCollection();
        $this->ideasParticipatedIn = new ArrayCollection();

        $this->comments = new ArrayCollection();
        $this->validatedComments = new ArrayCollection();

        $this->logEntries = new ArrayCollection();
        $this->initiatedLogEntries = new ArrayCollection();

        $this->createdTokens = new ArrayCollection();

        $this->userCommunities = new ArrayCollection();
        $this->currentCommunity = null;
        
        /* announcements */
        $this->targetedAnnouncements = new ArrayCollection();
        $this->viewedAnnouncements = new ArrayCollection();

        /* init */
        $this->salt = md5(uniqid(null, true));
        $this->roles = array('ROLE_USER');
        $this->created_at = $this->last_seen_at = $this->updated_at = $this->last_notified_at = new \DateTime('now');

        $this->preferredLanguage = "en_US";

        /* digests by mail */
        $this->enableDigest = false;
        $this->digestFrequency = null;
        $this->enableSpecificDay = false;
        $this->digestDay = null;
        $this->enableSpecificEmails = false;

    }

    public function getLogName()
    {
        return $this->first_name . " " . $this->last_name;
    }

    /* 

     So, why this function here ?
     Symfony tries to serialize the User object, which is indeed what I'm asking since I want to be able to log
     users in and out of this site.
     When serializing, Doctrine2 will try to serialize the mapped entities (such as Projects, Ideas, etc ...) 
     leading to cyclic references that are not handled correctly in Serializable. Thus, it will badly fail.

     The __sleep() method is the method that lists the parameters that have to be serialized. When creating a 
     proxy class for User, Symfony includes in the __sleep() method all the properties in the scope, notably
     properties that are mapped classes. Serializing a user entity thus leads to serializing a cyclic loop.

     Defining __sleep() here redefines the Symfony __sleep() method to make sure that no cyclic loop is present
     when serializing a User object.

     Source : https://groups.google.com/forum/?fromgroups=#!topic/symfony2/iL8C2hSMAfI

    */

    public function __sleep(){

        return array("id", "first_name", "last_name", "username", "email", "avatar");
    }

    /*
     * This is for the AdvancedUserInterface
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return !$this->isDeleted();
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
     * Get full name
     *
     * @return string 
     */
    public function getFullName()
    {
        return $this->first_name.' '.$this->last_name;
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

        if ($this->avatar === null){
           
            $hash = md5(strtolower(trim($this->email)));
            return "http://www.gravatar.com/avatar/".$hash."?s=150&d=".self::$gravatar_default_style;
        
        } else {

            return $this->getAvatarWebPath();
        
        }

    }

    public function isAvatarGravatar()
    {
        return ($this->avatar === null);
    }

    public function getAbsoluteAvatarPath()
    {
        return null === $this->avatar
            ? null
            : $this->getUploadRootDir().'/'.$this->avatar;
    }

    public function getAvatarWebPath()
    {
        return null === $this->avatar
            ? null
            : '/'.$this->getUploadDir().'/'.$this->avatar;
    }

    private function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    private function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/avatars';
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }
    
    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->file) {
            // Generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->avatar = $filename.'.'.$this->file->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->file->move($this->getUploadRootDir(), $this->avatar);

        unset($this->file);
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsoluteAvatarPath()) {
            unlink($file);
        }
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
     * Add skill
     *
     * @param meta\UserBundle\Entity\Skill $skills
     * @return User
     */
    public function addSkill(\meta\UserBundle\Entity\Skill $skill)
    {
        if (!is_null($skill)){
            $skill->addSkilledUser($this);
        }
        $this->skills[] = $skill;
    
        return $this;
    }

    /**
     * Remove skill
     *
     * @param meta\UserBundle\Entity\Skill $skills
     */
    public function removeSkill(\meta\UserBundle\Entity\Skill $skill)
    {
        if (!is_null($skill)){
            $skill->removeSkilledUser($this);
        }
        $this->skills->removeElement($skill);
        
    }

    /**
     * Has the user got this skill ?
     *
     * @return boolean 
     */
    public function hasSkill(\meta\UserBundle\Entity\Skill $skill)
    {
        return $this->skills->contains($skill);
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
     * Add followers
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS > addFollowing
     * @param meta\UserBundle\Entity\User $followers
     * @return User
     */
    public function addFollower(\meta\UserBundle\Entity\User $follower)
    {
        $this->followers[] = $follower;
        return $this;
    }

    /**
     * Remove followers
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS > removeFollowing
     * @param meta\UserBundle\Entity\User $followers
     */
    public function removeFollower(\meta\UserBundle\Entity\User $follower)
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
     * Count followers
     *
     * @return integer
     */
    public function countFollowers()
    {
        $count = 0;

        foreach ($this->followers as $user) {   
            if ( !($user->isDeleted()) ) $count++;
        }

        return $count;
    }

    /**
     * Add following
     *
     * @param meta\UserBundle\Entity\User $following
     * @return User
     */
    public function addFollowing(\meta\UserBundle\Entity\User $following)
    {
        if (!is_null($following)){
            $following->addFollower($this);
        }

        $this->following[] = $following;
        return $this;
    }

    /**
     * Remove following
     *
     * @param meta\UserBundle\Entity\User $following
     */
    public function removeFollowing(\meta\UserBundle\Entity\User $following)
    {
        if (!is_null($following)){
            $following->removeFollower($this);
        }

        $this->following->removeElement($following);
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
    public function isFollowing(\meta\UserBundle\Entity\User $user)
    {
        return $this->following->contains($user);
    }

    /**
     * Count following
     *
     * @return integer
     */
    public function countFollowing()
    {
        $count = 0;

        foreach ($this->following as $user) {   
            if ( !($user->isDeleted()) ) $count++;
        }

        return $count;
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
     * @param meta\ProjectBundle\Entity\StandardProject $projectOwned
     * @return User
     */
    public function addProjectsOwned(\meta\ProjectBundle\Entity\StandardProject $projectOwned)
    {
        if (!is_null($projectOwned)){
            $projectOwned->addOwner($this);
        }

        $this->projectsOwned[] = $projectOwned;
        return $this;
    }

    /**
     * Remove projectsOwned
     *
     * @param meta\ProjectBundle\Entity\StandardProject $projectOwned
     */
    public function removeProjectsOwned(\meta\ProjectBundle\Entity\StandardProject $projectOwned)
    {
        if (!is_null($projectOwned)){
            $projectOwned->removeOwner($this);
        }

        $this->projectsOwned->removeElement($projectOwned);
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
     * Get N random projects owned
     *
     * @return Doctrine\Common\Collections\Collections
     */
    public function getRandomProjectsOwned($limit)
    {
        $sub_array = $this->projectsOwned->slice(0,max(0,$limit));
        shuffle($sub_array);

        return $sub_array;

    }

    /**
     * Is already owning a standard project
     *
     * @return boolean 
     */
    public function isOwning(\meta\ProjectBundle\Entity\StandardProject $project)
    {
        return $this->projectsOwned->contains($project);
    }

    /**
     * Add projectsParticipatedIn
     *
     * @param meta\ProjectBundle\Entity\StandardProject $projectParticipatedIn
     * @return User
     */
    public function addProjectsParticipatedIn(\meta\ProjectBundle\Entity\StandardProject $projectParticipatedIn)
    {
        if (!is_null($projectParticipatedIn)){
            $projectParticipatedIn->addParticipant($this);
        }
        $this->projectsParticipatedIn[] = $projectParticipatedIn;
    
        return $this;
    }

    /**
     * Remove projectsParticipatedIn
     *
     * @param meta\ProjectBundle\Entity\StandardProject $projectParticipatedIn
     */
    public function removeProjectsParticipatedIn(\meta\ProjectBundle\Entity\StandardProject $projectParticipatedIn)
    {
        if (!is_null($projectParticipatedIn)){
            $projectParticipatedIn->removeParticipant($this);
        }
        $this->projectsParticipatedIn->removeElement($projectParticipatedIn);
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
    public function isParticipatingIn(\meta\ProjectBundle\Entity\StandardProject $project)
    {
        return $this->projectsParticipatedIn->contains($project);
    }

    /**
     * Can edit = is participating or owning
     *
     * @return boolean 
     */
    public function canEditProject(\meta\ProjectBundle\Entity\StandardProject $project)
    {
        return $this->isParticipatingIn($project) || $this->isOwning($project);
    }

    /**
     * Add projectsWatched
     *
     * @param meta\ProjectBundle\Entity\StandardProject $projectWatched
     * @return User
     */
    public function addProjectsWatched(\meta\ProjectBundle\Entity\StandardProject $projectWatched)
    {
        if (!is_null($projectWatched)){
            $projectWatched->addWatcher($this);
        }
        $this->projectsWatched[] = $projectWatched;
    
        return $this;
    }

    /**
     * Remove projectsWatched
     *
     * @param meta\ProjectBundle\Entity\StandardProject $projectWatched
     */
    public function removeProjectsWatched(\meta\ProjectBundle\Entity\StandardProject $projectWatched)
    {
        if (!is_null($projectWatched)){
            $projectWatched->removeWatcher($this);
        }
        $this->projectsWatched->removeElement($projectWatched);
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
    public function isWatchingProject(\meta\ProjectBundle\Entity\StandardProject $project)
    {
        return $this->projectsWatched->contains($project);
    }

    /**
     * Add comments
     *
     * BINDING LOGIC IS DONE IN 'BASECOMMENT' CLASS
     * @param \meta\GeneralBundle\Entity\Comment\BaseComment $comment
     * @return User
     */
    public function addComment(\meta\GeneralBundle\Entity\Comment\BaseComment $comment)
    {
        $this->comments[] = $comment;
        return $this;
    }

    /**
     * Remove comments
     *
     * BINDING LOGIC IS DONE IN 'BASECOMMENT' CLASS
     * @param \meta\GeneralBundle\Entity\Comment\BaseComment $comment
     */
    public function removeComment(\meta\GeneralBundle\Entity\Comment\BaseComment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Add ideaWatched
     *
     * @param \meta\IdeaBundle\Entity\Idea $ideaWatched
     * @return User
     */
    public function addIdeasWatched(\meta\IdeaBundle\Entity\Idea $ideaWatched)
    {
        if (!is_null($ideaWatched)){
            $ideaWatched->addWatcher($this);
        }

        $this->ideasWatched[] = $ideaWatched;
        return $this;
    }

    /**
     * Is already watching an idea
     *
     * @return boolean 
     */
    public function isWatchingIdea(\meta\IdeaBundle\Entity\Idea $idea)
    {
        return $this->ideasWatched->contains($idea);
    }

    /**
     * Remove ideaWatched
     *
     * @param \meta\IdeaBundle\Entity\Idea $ideaWatched
     */
    public function removeIdeasWatched(\meta\IdeaBundle\Entity\Idea $ideaWatched)
    {
        if (!is_null($ideaWatched)){
            $ideaWatched->removeWatcher($this);
        }
        $this->ideasWatched->removeElement($ideaWatched);
        
    }

    /**
     * Get ideasWatched
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getIdeasWatched()
    {
        return $this->ideasWatched;
    }

    /**
     * Add ideasCreated
     *
     * BINDING LOGIC IS DONE IN 'IDEA' CLASS
     * @param \meta\IdeaBundle\Entity\Idea $ideaCreated
     * @return User
     */
    public function addIdeasCreated(\meta\IdeaBundle\Entity\Idea $ideaCreated)
    {
        $this->ideasCreated[] = $ideaCreated;
        return $this;
    }

    /**
     * Remove ideasCreated
     *
     * BINDING LOGIC IS DONE IN 'IDEA' CLASS
     * @param \meta\IdeaBundle\Entity\Idea $ideaCreated
     */
    public function removeIdeasCreated(\meta\IdeaBundle\Entity\Idea $ideaCreated)
    {
        $this->ideasCreated->removeElement($ideaCreated);
    }

    /**
     * Get ideasCreated
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getIdeasCreated()
    {
        return $this->ideasCreated;
    }

    /**
     * has created an idea
     *
     * @return boolean 
     */
    public function hasCreatedIdea(\meta\IdeaBundle\Entity\Idea $idea)
    {
        return $this->ideasCreated->contains($idea);
    }

    /**
     * Add ideasParticipatedIn
     *
     * @param \meta\IdeaBundle\Entity\Idea $ideaParticipatedIn
     * @return User
     */
    public function addIdeasParticipatedIn(\meta\IdeaBundle\Entity\Idea $ideaParticipatedIn)
    {
        if (!is_null($ideaParticipatedIn)){
            $ideaParticipatedIn->addParticipant($this);
        }

        $this->ideasParticipatedIn[] = $ideaParticipatedIn;
        return $this;
    }

    /**
     * Remove ideaParticipatedIn
     *
     * @param \meta\IdeaBundle\Entity\Idea $ideaParticipatedIn
     */
    public function removeIdeasParticipatedIn(\meta\IdeaBundle\Entity\Idea $ideaParticipatedIn)
    {
        if (!is_null($ideaParticipatedIn)){
            $ideaParticipatedIn->removeParticipant($this);
        }

        $this->ideasParticipatedIn->removeElement($ideaParticipatedIn);
        
    }

    /**
     * Get ideasParticipatedIn
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getIdeasParticipatedIn()
    {
        return $this->ideasParticipatedIn;
    }

    /**
     * Is already participating in an idea
     *
     * @param \meta\IdeaBundle\Entity\Idea $idea
     * @return boolean 
     */
    public function isParticipatingInIdea(\meta\IdeaBundle\Entity\Idea $idea)
    {
        return $this->ideasParticipatedIn->contains($idea);
    }

    /**
     * Add logEntry
     *
     * BINDING LOGIC IS DONE IN 'USERLOGENTRY' CLASS 
     * @param \meta\GeneralBundle\Entity\Log\BaseLogEntry $logEntry
     * @return User
     */
    public function addLogEntry(\meta\GeneralBundle\Entity\Log\BaseLogEntry $logEntry)
    {
        $this->logEntries[] = $logEntry;
        return $this;
    }

    /**
     * Remove logEntries
     *
     * BINDING LOGIC IS DONE IN 'USERLOGENTRY' CLASS 
     * @param \meta\GeneralBundle\Entity\Log\BaseLogEntry $logEntry
     */
    public function removeLogEntry(\meta\GeneralBundle\Entity\Log\BaseLogEntry $logEntry)
    {
        $this->logEntries->removeElement($logEntry);
    }

    /**
     * Get logEntries
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLogEntries()
    {
        return $this->logEntries;
    }

    /**
     * Add initiatedLogEntries
     *
     * BINDING LOGIC IS DONE IN 'BASELOGENTRY' CLASS
     * @param \meta\GeneralBundle\Entity\Log\BaseLogEntry $initiatedLogEntry
     * @return User
     */
    public function addInitiatedLogEntry(\meta\GeneralBundle\Entity\Log\BaseLogEntry $initiatedLogEntry)
    {
        $this->initiatedLogEntries[] = $initiatedLogEntry;
        return $this;
    }

    /**
     * Remove initiatedLogEntries
     *
     * BINDING LOGIC IS DONE IN 'BASELOGENTRY' CLASS
     * @param \meta\GeneralBundle\Entity\Log\BaseLogEntry $initiatedLogEntry
     */
    public function removeInitiatedLogEntry(\meta\GeneralBundle\Entity\Log\BaseLogEntry $initiatedLogEntry)
    {
        $this->initiatedLogEntries->removeElement($initiatedLogEntry);
    }

    /**
     * Get initiatedLogEntries
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInitiatedLogEntries()
    {
        return $this->initiatedLogEntries;
    }

    /**
     * Set last_seen_at
     *
     * @param \DateTime $lastSeenAt
     * @return User
     */
    public function setLastSeenAt($lastSeenAt)
    {
        $this->last_seen_at = $lastSeenAt;
        return $this;
    }

    /**
     * Get last_seen_at
     *
     * @return \DateTime 
     */
    public function getLastSeenAt()
    {
        return $this->last_seen_at;
    }

    /**
     * Add validatedComments
     *
     * BINDING LOGIC IS DONE IN 'BASECOMMENT' CLASS 
     * @param \meta\GeneralBundle\Entity\Comment\BaseComment $validatedComment
     * @return User
     */
    public function addValidatedComment(\meta\GeneralBundle\Entity\Comment\BaseComment $validatedComment)
    {
        $this->validatedComments[] = $validatedComment;
    
        return $this;
    }

    /**
     * Remove validatedComments
     *
     * BINDING LOGIC IS DONE IN 'BASECOMMENT' CLASS 
     * @param \meta\GeneralBundle\Entity\Comment\BaseComment $validatedComment
     */
    public function removeValidatedComment(\meta\GeneralBundle\Entity\Comment\BaseComment $validatedComment)
    {
        $this->validatedComments->removeElement($validatedComment);
    }

    /**
     * Get validatedComments
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getValidatedComments()
    {
        return $this->validatedComments;
    }
    
    /**
     * Set originatingToken
     *
     * BINDING LOGIC IS DONE IN 'USERINVITETOKEN' CLASS 
     * @param \meta\UserBundle\Entity\UserInviteToken $originatingToken
     * @return User
     */
    public function setOriginatingToken(\meta\UserBundle\Entity\UserInviteToken $originatingToken = null)
    {
        $this->originatingToken = $originatingToken;
    
        return $this;
    }

    /**
     * Get originatingToken
     *
     * @return \meta\UserBundle\Entity\UserInviteToken 
     */
    public function getOriginatingToken()
    {
        return $this->originatingToken;
    }

    /**
     * Add createdTokens
     *
     * BINDING LOGIC IS DONE IN 'USERINVITETOKEN' CLASS 
     * @param \meta\UserBundle\Entity\UserInviteToken $createdTokens
     * @return User
     */
    public function addCreatedToken(\meta\UserBundle\Entity\UserInviteToken $createdTokens)
    {
        $this->createdTokens[] = $createdTokens;
    
        return $this;
    }

    /**
     * Remove createdTokens
     *
     * BINDING LOGIC IS DONE IN 'USERINVITETOKEN' CLASS 
     * @param \meta\UserBundle\Entity\UserInviteToken $createdTokens
     */
    public function removeCreatedToken(\meta\UserBundle\Entity\UserInviteToken $createdTokens)
    {
        $this->createdTokens->removeElement($createdTokens);
    }

    /**
     * Get createdTokens
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCreatedTokens()
    {
        return $this->createdTokens;
    }

    /**
     * Create a token for recovery
     *
     */
    public function createNewRecoverToken()
    {
        $this->token = base64_encode(md5(uniqid(null, true)));
    }

    /**
     * Set deleted_at
     *
     * @param \DateTime $deletedAt
     * @return User
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deleted_at = $deletedAt;
    
        return $this;
    }

    /**
     * Get deleted_at
     *
     * @return \DateTime 
     */
    public function getDeletedAt()
    {
        return $this->deleted_at;
    }

    /**
     * Is deleted
     *
     * @return boolean 
     */
    public function isDeleted()
    {
        return !($this->deleted_at === NULL);
    }

    /**
     * Deletes
     *
     * @return User 
     */
    public function delete()
    {
        $this->deleted_at = new \DateTime('now');
        // Resets the unique properties
        $this->username = null;
        $this->email = null;
        return $this;
    }

    /**
     * Set preferredLanguage
     *
     * @param string $preferredLanguage
     * @return User
     */
    public function setPreferredLanguage($preferredLanguage)
    {
        $this->preferredLanguage = $preferredLanguage;

        return $this;
    }

    /**
     * Get preferredLanguage
     *
     * @return string 
     */
    public function getPreferredLanguage()
    {
        return $this->preferredLanguage;
    }

    /**
     * Set currentCommunity
     *
     * @param \meta\GeneralBundle\Entity\Community\Community $currentCommunity
     * @return User
     */
    public function setCurrentCommunity(\meta\GeneralBundle\Entity\Community\Community $currentCommunity = null)
    {
        $this->currentCommunity = $currentCommunity;
    
        return $this;
    }

    /**
     * Get currentCommunity
     *
     * @return \meta\GeneralBundle\Entity\Community\Community 
     */
    public function getCurrentCommunity()
    {
        return $this->currentCommunity;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return User
     */
    public function setToken($token)
    {
        $this->token = $token;
    
        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set last_notified_at
     *
     * @param \DateTime $lastNotifiedAt
     * @return User
     */
    public function setLastNotifiedAt($lastNotifiedAt)
    {
        $this->last_notified_at = $lastNotifiedAt;
    
        return $this;
    }

    /**
     * Get last_notified_at
     *
     * @return \DateTime 
     */
    public function getLastNotifiedAt()
    {
        return $this->last_notified_at;
    }

    /**
     * Add userCommunity
     *
     * BINDING LOGIC IS DONE IN 'COMMUNITY' CLASS 
     * @param \meta\UserBundle\Entity\UserCommunity $userCommunity
     * @return User
     */
    public function addUserCommunity(\meta\UserBundle\Entity\UserCommunity $userCommunity)
    {
        $this->userCommunities[] = $userCommunity;
    
        return $this;
    }

    /**
     * Remove userCommunity
     *
     * @param \meta\UserBundle\Entity\UserCommunity $userCommunity
     */
    public function removeUserCommunity(\meta\UserBundle\Entity\UserCommunity $userCommunity)
    {
        $this->userCommunities->removeElement($userCommunity);
    }

    /**
     * Get userCommunities
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserCommunities()
    {
        return $this->userCommunities;
    }

    /**
     * Set digestFrequency
     *
     * @param string $digestFrequency
     * @return User
     */
    public function setDigestFrequency($digestFrequency)
    {
        $this->digestFrequency = $digestFrequency;
        return $this;
    }

    /**
     * Get digestFrequency
     *
     * @return string 
     */
    public function getDigestFrequency()
    {
        return $this->digestFrequency;
    }

    /**
     * Set digestDay
     *
     * @param string $digestDay
     * @return User
     */
    public function setDigestDay($digestDay)
    {
        $this->digestDay = $digestDay;
        return $this;
    }

    /**
     * Get digestDay
     *
     * @return string 
     */
    public function getDigestDay()
    {
        return $this->digestDay;
    }

    /**
     * Set enableSpecificDay
     *
     * @param string $enableSpecificDay
     * @return User
     */
    public function setEnableSpecificDay($enableSpecificDay)
    {
        $this->enableSpecificDay = $enableSpecificDay;
        return $this;
    }

    /**
     * Get enableSpecificDay
     *
     * @return string 
     */
    public function getEnableSpecificDay()
    {
        return $this->enableSpecificDay;
    }

    /**
     * Set enableSpecificEmails
     *
     * @param string $enableSpecificEmails
     * @return User
     */
    public function setEnableSpecificEmails($enableSpecificEmails)
    {
        $this->enableSpecificEmails = $enableSpecificEmails;
        return $this;
    }

    /**
     * Get enableSpecificEmails
     *
     * @return string 
     */
    public function getEnableSpecificEmails()
    {
        return $this->enableSpecificEmails;
    }

    /**
     * Set enableDigest
     *
     * @param string $enableDigest
     * @return User
     */
    public function setEnableDigest($enableDigest)
    {
        $this->enableDigest = $enableDigest;
        return $this;
    }

    /**
     * Get enableDigest
     *
     * @return string 
     */
    public function getEnableDigest()
    {
        return $this->enableDigest;
    }

    /**
     * Add targetedAnnoucement
     *
     * @param \meta\AdminBundle\Entity\Announcement $announcement
     * @return Taggable
     */
    public function addTargetedAnnouncement(\meta\AdminBundle\Entity\Announcement $announcement)
    {
        if (!is_null($announcement)){
            $announcement->addTargetedUser($this);
        }
        $this->targetedAnnouncements[] = $announcement;
    
        return $this;
    }

    /**
     * Remove targetedAnnoucement
     *
     * @param \meta\AdminBundle\Entity\Announcement $announcement
     */
    public function removeTargetedAnnouncement(\meta\AdminBundle\Entity\Announcement $announcement)
    { 
        if(!is_null($announcement)){
            $announcement->removeTargetedUser($this);
        }
        $this->targetedAnnouncements->removeElement($announcement);
    }

    /**
     * get targetedAnnoucement
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTargetedAnnouncements()
    { 
       return $this->targetedAnnouncements;
    }

    /**
     * Add viewedAnnouncement
     *
     * @param \meta\AdminBundle\Entity\Announcement $announcement
     * @return Taggable
     */
    public function addViewedAnnouncement(\meta\AdminBundle\Entity\Announcement $announcement)
    {
        if (!is_null($announcement)){
            $announcement->addHitUser($this);
        }
        $this->viewedAnnouncements[] = $announcement;
    
        return $this;
    }

    /**
     * Remove viewedAnnouncement
     *
     * @param \meta\AdminBundle\Entity\Announcement $announcement
     */
    public function removeViewedAnnouncement(\meta\AdminBundle\Entity\Announcement $announcement)
    { 
        if(!is_null($announcement)){
            $announcement->removeHitUser($this);
        }
        $this->viewedAnnouncements->removeElement($announcement);
    }

    /**
     * get viewedAnnoucement
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getViewedAnnouncements()
    { 
       return $this->viewedAnnouncements;
    }

}
