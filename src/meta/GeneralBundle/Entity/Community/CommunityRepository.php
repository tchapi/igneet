<?php

namespace meta\GeneralBundle\Entity\Community;

use Doctrine\ORM\EntityRepository;

/**
 * CommunityRepository
 *
 */
class CommunityRepository extends EntityRepository
{

  public function findAllFilesInCommunity($id)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('r')
            ->from('metaProjectBundle:Resource', 'r')
            ->join('r.project', 'p')
            ->join('p.community', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->andWhere('r.provider = :local')
            ->setParameter('local', "local")
            ->andWhere("p.deleted_at IS NULL")
            ->getQuery()
            ->getResult();

  }

 public function findAllFiles()
  {
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('r')
            ->from('metaProjectBundle:Resource', 'r')
            ->join('r.project', 'p')
            ->andWhere('r.provider = :local')
            ->setParameter('local', "local")
            ->andWhere("p.deleted_at IS NULL")
            ->getQuery()
            ->getResult();

  }

  public function findAllPrunableFiles()
  {
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('r')
            ->from('metaProjectBundle:Resource', 'r')
            ->join('r.project', 'p')
            ->andWhere('r.provider = :local')
            ->setParameter('local', "local")
            ->andWhere("p.deleted_at IS NOT NULL")
            ->getQuery()
            ->getResult();
  }

}
