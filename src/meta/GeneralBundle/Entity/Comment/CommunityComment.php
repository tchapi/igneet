<?php

namespace meta\GeneralBundle\Entity\Comment;

use meta\GeneralBundle\Entity\Comment\BaseComment;
use Doctrine\ORM\Mapping as ORM;

/**
 * CommunityComment
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class CommunityComment extends BaseComment
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
     * Community commented
     * @ORM\ManyToOne(targetEntity="\meta\GeneralBundle\Entity\Community\Community", inversedBy="comments")
     **/
    private $community;


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
     * Set community
     *
     * BINDING LOGIC IS DONE IN 'COMMUNITY' CLASS 
     * @param \meta\GeneralBundle\Entity\Community\Community $community
     * @return CommunityComment
     */
    public function setCommunity(\meta\GeneralBundle\Entity\Community\Community $community = null)
    {
        $this->community = $community;
        return $this;
    }

    /**
     * Get Community
     *
     * @return \meta\GeneralBundle\Entity\Community\Community 
     */
    public function getCommunity()
    {
        return $this->community;
    }
}