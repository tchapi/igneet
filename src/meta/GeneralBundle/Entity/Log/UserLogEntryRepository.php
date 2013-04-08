<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * UserLogEntryRepository
 *
 */
class UserLogEntryRepository extends EntityRepository
{

  public function findLogsForUser($user, $from) // Effectively, gets all logs related to new followers of $user
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->select('l')
            ->from('metaGeneralBundle:Log\UserLogEntry', 'l')
            ->where('l.other_user = :user')
            ->setParameter('user', $user)
            ->andWhere('l.type = :type')
            ->setParameter('type', 'user_follow_user')
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from)
            ->orderBy('l.created_at', 'DESC')
            ->getQuery()
            ->getResult();

  }

}
