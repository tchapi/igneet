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
    $sqb = $this->getEntityManager()->createQueryBuilder();

    $now = new \DateTime('now');

    
    $subquery = $sqb->select('a_sub')
                    ->from('metaAdminBundle:Announcement', 'a_sub')
                    ->leftJoin('a_sub.hitUsers', 'hu')
                    ->where('hu = :user')
                    ->setParameter('user', $user);

    return $qb->select('a')
            ->from('metaAdminBundle:Announcement', 'a')
            ->leftJoin('a.targetedUsers', 'tu')
            ->where('a.active = 1')
            ->andWhere('a.valid_until > :now')
            ->andWhere('a.valid_from < :now')
            ->andWhere('tu IS NULL OR tu = :user')
            ->andWhere('a NOT IN (' . $subquery->getDql() . ')')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
  }

}
