<?php

namespace meta\AdminBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * AnnouncementRepository
 *
 */
class AnnouncementRepository extends EntityRepository
{

  public function findAnnouncementsForUser($user)
  {
 
    $qb = $this->getEntityManager()->createQueryBuilder();

    $now = new \DateTime('now');

    return $qb->select('a')
            ->from('metaAdminBundle:Announcement', 'a')
            ->leftJoin('a.targetedUsers', 'tu')
            ->leftJoin('a.hitUsers', 'hu')
            ->where('a.active = 1')
            ->andWhere('a.valid_until > :now')
            ->andWhere('a.valid_from < :now')
            ->andWhere('tu IS NULL OR tu = :user')
            ->andWhere('hu IS NULL OR hu <> :user')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
  }

}
