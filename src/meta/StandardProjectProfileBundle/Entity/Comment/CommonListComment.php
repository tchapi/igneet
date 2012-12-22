<?php

namespace meta\StandardProjectProfileBundle\Entity\Comment;

use Doctrine\ORM\Mapping as ORM;

/**
 * CommonListComment
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class CommonListComment extends BaseComment
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
     * Common list commented
     * @ORM\ManyToOne(targetEntity="\meta\StandardProjectProfileBundle\Entity\CommonList", inversedBy="comments")
     **/
    private $commonList;


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
     * Set commonList
     *
     * @param \meta\StandardProjectProfileBundle\Entity\CommonList $commonList
     * @return CommonListComment
     */
    public function setCommonList(\meta\StandardProjectProfileBundle\Entity\CommonList $commonList = null)
    {
        $this->commonList = $commonList;
    
        return $this;
    }

    /**
     * Get commonList
     *
     * @return \meta\StandardProjectProfileBundle\Entity\CommonList 
     */
    public function getCommonList()
    {
        return $this->commonList;
    }
}