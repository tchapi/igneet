<?php

namespace meta\GeneralBundle\Entity\Community;

use Doctrine\ORM\EntityRepository;

/**
 * CommunityRepository
 *
 */
class CommunityRepository extends EntityRepository
{
  
  public function findAllCommunitiesForUser($user)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    
    $query = $qb->select('c')
            ->from('metaGeneralBundle:Community\Community', 'c')
            ->join('c.users', 'u')
            ->where('u = :uid')
            ->setParameter('uid', $user)
            ->getQuery();

    return $query->getResult();
  }

}
