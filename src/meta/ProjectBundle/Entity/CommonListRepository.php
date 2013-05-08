<?php

namespace meta\ProjectBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * CommonListRepository
 *
 */
class CommonListRepository extends EntityRepository
{

  public function findOneByIdInProject($id, $projectId)
  {
 
    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('cl')
            ->from('metaProjectBundle:CommonList', 'cl')
            ->join('cl.project', 'sp')
            ->where('sp.id = :pid')
            ->setParameter('pid', $projectId)
            ->andWhere('cl.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery();

    try {
        $commonList = $query->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
        $commonList = null;
    }

    return $commonList;

  }

  public function findFirstInProject($projectId)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('cl')
            ->from('metaProjectBundle:CommonList', 'cl')
            ->join('cl.project', 'sp')
            ->where('sp.id = :pid')
            ->setParameter('pid', $projectId)
            ->orderBy('cl.rank', 'ASC')
            ->setMaxResults(1)
            ->getQuery();

    try {
        $commonList = $query->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
        $commonList = null;
    }

    return $commonList;

  }

  public function findAllInProject($projectId)
  {

    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('cl')
            ->from('metaProjectBundle:CommonList', 'cl')
            ->join('cl.project', 'sp')
            ->where('sp.id = :pid')
            ->setParameter('pid', $projectId)
            ->orderBy('cl.rank', 'ASC')
            ->getQuery()
            ->getResult();
  }

}
