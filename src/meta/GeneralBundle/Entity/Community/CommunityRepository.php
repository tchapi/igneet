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

  public function findAllPrunableGuestsInCommunity($community)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    $sqb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('u AS user, uc.id AS userCommunityId');

    // For each user, counting all projects in the community in which they are either participant or owner    
    $subquery = $sqb->select('COUNT(DISTINCT p_sub)')
                    ->from('metaProjectBundle:StandardProject', 'p_sub')
                    ->join('p_sub.owners', 'u1')
                    ->leftJoin('p_sub.participants', 'u2')
                    ->where('p_sub.deleted_at IS NULL')
                    ->andWhere('p_sub.community = :community')
                    ->setParameter('community', $community)
                    ->andWhere('u1 = u OR u2 = u');

    // For each user that is a guest in the community, we have the count and we only take those users with 0
    $query->addSelect(sprintf('(%s) AS nb', $subquery->getDql()))
            ->from('metaUserBundle:User', 'u')
            ->join('u.userCommunities', 'uc')
            ->where('uc.community = :community')
            ->setParameter('community', $community)
            ->andWhere("uc.guest = 1")
            ->andWhere("u.deleted_at IS NULL")
            ->add("having", "nb = 0");

    return $query->getQuery()
            ->getResult();

  }

  public function findAllExpiringCommunity($datetime)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('c')
            ->from('metaGeneralBundle:Community\Community', 'c')
            ->where('c.valid_until > CURRENT_TIMESTAMP()')
            ->andWhere('c.valid_until < :date')
            ->setParameter('date', $datetime)
            ->getQuery()
            ->getResult();

  }
}
