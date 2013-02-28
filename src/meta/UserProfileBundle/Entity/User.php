<?php

namespace meta\UserProfileBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity,
    Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\UserProfileBundle\Entity\User
 *
 * @ORM\Table(name="User")
 * @ORM\Entity(repositoryClass="meta\UserProfileBundle\Entity\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields="username", message="This username is already taken")
 * @UniqueEntity(fields="email", message="This email is already registered")
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
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
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
     */
    private $avatar;

    /**
     * @Assert\File(maxSize="6000000")
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

    /**
     * Ideas I watch (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\IdeaProfileBundle\Entity\Idea", inversedBy="watchers")
     * @ORM\JoinTable(name="User_watches_Idea",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="idea_id", referencedColumnName="id")}
     *      )
     **/
    private $ideasWatched;

    /**
     * Ideas I have created (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\IdeaProfileBundle\Entity\Idea", mappedBy="creators")
     * @ORM\JoinTable(name="User_created_Idea")
     **/
    private $ideasCreated;

    /**
     * Ideas I participate in (OWNING SIDE)
     * @ORM\ManyToMany(targetEntity="meta\IdeaProfileBundle\Entity\Idea", inversedBy="participants")
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

        /* init */
        $this->salt = md5(uniqid(null, true));
        $this->roles = array('ROLE_USER');
        $this->created_at = $this->last_seen_at = $this->updated_at = new \DateTime('now');

    }

    public function getLogName()
    {
        return $this->first_name . " " . $this->last_name;
    }
    public function getLogArgs(){
        return array( 'username' => $this->username );
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
    public function removeProjectsParticipatedIn(\meta\StandardProjectProfileBundle\Entity\StandardProject $projectParticipatedIn)
    {
        $this->projectsParticipatedIn->removeElement($projectParticipatedIn);
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
     * Can edit = is participating or owning
     *
     * @return boolean 
     */
    public function canEditProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $project)
    {
        return $this->isParticipatingIn($project) || $this->isOwning($project);
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
    public function isWatchingProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $project)
    {
        return $this->projectsWatched->contains($project);
    }

    /**
     * Add comments
     *
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
     * Count public comments
     *
     * @return integer
     */
    public function countPublicComments()
    {
        $count = 0;

        foreach ($this->comments as $comment) {
            
            if ($comment->isPublic())
                $count++;

        }

        return $count;
    }

    /**
     * Add ideasWatched
     *
     * @param \meta\IdeaProfileBundle\Entity\Idea $ideaWatched
     * @return User
     */
    public function addIdeasWatched(\meta\IdeaProfileBundle\Entity\Idea $ideaWatched)
    {
        $ideaWatched->addWatcher($this);
        $this->ideasWatched[] = $ideaWatched;
    
        return $this;
    }

    /**
     * Is already watching an idea
     *
     * @return boolean 
     */
    public function isWatchingIdea(\meta\IdeaProfileBundle\Entity\Idea $idea)
    {
        return $this->ideasWatched->contains($idea);
    }

    /**
     * Remove ideasWatched
     *
     * @param \meta\IdeaProfileBundle\Entity\Idea $ideaWatched
     */
    public function removeIdeasWatched(\meta\IdeaProfileBundle\Entity\Idea $ideaWatched)
    {
        $this->ideasWatched->removeElement($ideaWatched);
        $ideaWatched->removeWatcher($this);
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
     * Count ideas Watched not archived
     *
     * @return integer
     */
    public function countNotArchivedIdeasWatched()
    {
        $count = 0;

        foreach ($this->ideasWatched as $idea) {
            
            if ( !($idea->isArchived()) )
                $count++;

        }

        return $count;
    }

    /**
     * Add ideasCreated
     *
     * @param \meta\IdeaProfileBundle\Entity\Idea $ideaCreated
     * @return User
     */
    public function addIdeasCreated(\meta\IdeaProfileBundle\Entity\Idea $ideaCreated)
    {
        $ideaCreated->addCreator($this);
        $this->ideasCreated[] = $ideaCreated;
    
        return $this;
    }

    /**
     * Count ideas Created not archived
     *
     * @return integer
     */
    public function countNotArchivedIdeasCreated()
    {
        $count = 0;

        foreach ($this->ideasCreated as $idea) {
            
            if ( !($idea->isArchived()) )
                $count++;

        }

        return $count;
    }

    /**
     * Remove ideasCreated
     *
     * @param \meta\IdeaProfileBundle\Entity\Idea $ideaCreated
     */
    public function removeIdeasCreated(\meta\IdeaProfileBundle\Entity\Idea $ideaCreated)
    {
        $this->ideasCreated->removeElement($ideaCreated);
        $ideaCreated->removeCreator($this);
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
    public function hasCreatedIdea(\meta\IdeaProfileBundle\Entity\Idea $idea)
    {
        return $this->ideasCreated->contains($idea);
    }

    /**
     * Add ideasParticipatedIn
     *
     * @param \meta\IdeaProfileBundle\Entity\Idea $ideaParticipatedIn
     * @return User
     */
    public function addIdeasParticipatedIn(\meta\IdeaProfileBundle\Entity\Idea $ideaParticipatedIn)
    {
        $ideaParticipatedIn->addParticipant($this);
        $this->ideasParticipatedIn[] = $ideaParticipatedIn;
    
        return $this;
    }

    /**
     * Remove ideasParticipatedIn
     *
     * @param \meta\IdeaProfileBundle\Entity\Idea $ideaParticipatedIn
     */
    public function removeIdeasParticipatedIn(\meta\IdeaProfileBundle\Entity\Idea $ideaParticipatedIn)
    {
        $this->ideasParticipatedIn->removeElement($ideaParticipatedIn);
        $ideaParticipatedIn->removeParticipant($this);
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
     * @return boolean 
     */
    public function isParticipatingInIdea(\meta\IdeaProfileBundle\Entity\Idea $idea)
    {
        return $this->ideasParticipatedIn->contains($idea);
    }


    /**
     * Add logEntries
     *
     * @param \meta\GeneralBundle\Entity\Log\BaseLogEntry $logEntries
     * @return User
     */
    public function addLogEntrie(\meta\GeneralBundle\Entity\Log\BaseLogEntry $logEntries)
    {
        $this->logEntries[] = $logEntries;
    
        return $this;
    }

    /**
     * Remove logEntries
     *
     * @param \meta\GeneralBundle\Entity\Log\BaseLogEntry $logEntries
     */
    public function removeLogEntrie(\meta\GeneralBundle\Entity\Log\BaseLogEntry $logEntries)
    {
        $this->logEntries->removeElement($logEntries);
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
     * @param \meta\GeneralBundle\Entity\Log\BaseLogEntry $initiatedLogEntries
     * @return User
     */
    public function addInitiatedLogEntrie(\meta\GeneralBundle\Entity\Log\BaseLogEntry $initiatedLogEntries)
    {
        $this->initiatedLogEntries[] = $initiatedLogEntries;
    
        return $this;
    }

    /**
     * Remove initiatedLogEntries
     *
     * @param \meta\GeneralBundle\Entity\Log\BaseLogEntry $initiatedLogEntries
     */
    public function removeInitiatedLogEntrie(\meta\GeneralBundle\Entity\Log\BaseLogEntry $initiatedLogEntries)
    {
        $this->initiatedLogEntries->removeElement($initiatedLogEntries);
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
     * Set originatingToken
     *
     * @param \meta\UserProfileBundle\Entity\UserInviteToken $originatingToken
     * @return User
     */
    public function setOriginatingToken(\meta\UserProfileBundle\Entity\UserInviteToken $originatingToken = null)
    {
        $this->originatingToken = $originatingToken;
    
        return $this;
    }

    /**
     * Get originatingToken
     *
     * @return \meta\UserProfileBundle\Entity\UserInviteToken 
     */
    public function getOriginatingToken()
    {
        return $this->originatingToken;
    }

    /**
     * Add createdTokens
     *
     * @param \meta\UserProfileBundle\Entity\UserInviteToken $createdTokens
     * @return User
     */
    public function addCreatedToken(\meta\UserProfileBundle\Entity\UserInviteToken $createdTokens)
    {
        $this->createdTokens[] = $createdTokens;
    
        return $this;
    }

    /**
     * Remove createdTokens
     *
     * @param \meta\UserProfileBundle\Entity\UserInviteToken $createdTokens
     */
    public function removeCreatedToken(\meta\UserProfileBundle\Entity\UserInviteToken $createdTokens)
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
     * Add validatedComments
     *
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
}