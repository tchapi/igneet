<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\EntityRepository;

/**
 * StandardProjectLogEntryRepository
 *
 */
class StandardProjectLogEntryRepository extends EntityRepository
{

  private function getLogsQuery($projects, $from)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->from('metaGeneralBundle:Log\StandardProjectLogEntry', 'l')
            ->where('l.standardProject IN (:projects)')
            ->setParameter('projects', $projects)
            ->andWhere('l.created_at > :from')
            ->setParameter('from', $from)
            ->orderBy('l.created_at', 'DESC');
  }

  public function findLogsForProjects($projects, $from)
  {

    return $this->getLogsQuery($projects, $from)->select('l')
                                            ->getQuery()
                                            ->getResult();

  }

  public function countLogsForProjects($projects, $from)
  {

    return $this->getLogsQuery($projects, $from)->select('COUNT(l)')
                                            ->getQuery()
                                            ->getSingleScalarResult();

  }

}
