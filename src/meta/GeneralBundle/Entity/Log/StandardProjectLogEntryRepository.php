<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * StandardProjectLogEntryRepository
 *
 */
class StandardProjectLogEntryRepository extends EntityRepository
{

  public function findLogsForProjects($projects, $from)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->select('l')
            ->from('metaGeneralBundle:Log\StandardProjectLogEntry', 'l')
            // ->andWhere('l.type = :type')
            // ->setParameter('type', 'user_follow_user')
            ->where('l.standardProject IN (:projects)')
            ->setParameter('projects', $projects)
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from)
            ->orderBy('l.created_at', 'DESC')
            ->getQuery()
            ->getResult();

  }

}
