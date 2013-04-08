<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * IdeaLogEntryRepository
 *
 */
class IdeaLogEntryRepository extends EntityRepository
{

  public function findLogsForIdeas($ideas, $from)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->select('l')
            ->from('metaGeneralBundle:Log\IdeaLogEntry', 'l')
            // ->andWhere('l.type = :type')
            // ->setParameter('type', 'user_follow_user')
            ->where('l.idea IN (:ideas)')
            ->setParameter('ideas', $ideas)
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from)
            ->orderBy('l.created_at', 'DESC')
            ->getQuery()
            ->getResult();

  }

}
