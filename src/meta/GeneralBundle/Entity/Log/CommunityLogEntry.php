<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\Mapping as ORM;

/**
 * CommunityLogEntry
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="meta\GeneralBundle\Entity\Log\CommunityLogEntryRepository")
 */
class CommunityLogEntry extends BaseLogEntry
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /* NO SUBJECT parameter here  we use the community that is already in the base log object */

    /**
     * Set subject
     *
     * @param \meta\GeneralBundle\Entity\Community\Community $community
     * @return CommunityLogEntry
     */
    public function setSubject(\meta\GeneralBundle\Entity\Community\Community $community = null)
    {
        return $this->setCommunity($community);
    }

    /**
     * Get subject
     *
     * @return \meta\GeneralBundle\Entity\Community\Community
     */
    public function getSubject()
    {
        return $this->getCommunity();
    }

}