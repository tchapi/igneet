<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * UserLogEntryRepository
 *
 */
class UserLogEntryRepository extends EntityRepository
{

  public function findLastSocialActivityForUser($user, $max = 3)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->select('l')
            ->from('metaGeneralBundle:Log\UserLogEntry', 'l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->andWhere('l.type = :type')
            ->setParameter('type', 'user_follow_user')
            ->orderBy('l.created_at', 'DESC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();

  }

  private function getLogsQuery($user, $from)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->from('metaGeneralBundle:Log\UserLogEntry', 'l')
            ->where('l.other_user = :user')
            ->setParameter('user', $user)
            ->andWhere('l.type = :type')
            ->setParameter('type', 'user_follow_user')
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from)
            ->orderBy('l.created_at', 'DESC');

  }

  public function findLogsForUser($user, $from) // Effectively, gets all logs related to new followers of $user
  {

    return $this->getLogsQuery($user, $from)->select('l')
                                            ->getQuery()
                                            ->getResult();

  }

  public function countLogsForUser($user, $from) // Effectively, conts all logs related to new followers of $user
  {

    return $this->getLogsQuery($user, $from)->select('COUNT(l)')
                                            ->getQuery()
                                            ->getSingleScalarResult();

  }

}
