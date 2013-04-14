<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * IdeaLogEntryRepository
 *
 */
class IdeaLogEntryRepository extends EntityRepository
{

  private function getLogsQuery($ideas, $from)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->from('metaGeneralBundle:Log\IdeaLogEntry', 'l')
            ->where('l.idea IN (:ideas)')
            ->setParameter('ideas', $ideas)
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from)
            ->orderBy('l.created_at', 'DESC');
  }

  public function findLogsForIdeas($ideas, $from)
  {

    return $this->getLogsQuery($ideas, $from)->select('l')
                                            ->getQuery()
                                            ->getResult();

  }

  public function countLogsForIdeas($ideas, $from)
  {

    return $this->getLogsQuery($ideas, $from)->select('COUNT(l)')
                                            ->getQuery()
                                            ->getSingleScalarResult();

  }
}
