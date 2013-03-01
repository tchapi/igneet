<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * WikiPageRepository
 *
 */
class WikiPageRepository extends EntityRepository
{

  public function findOneByIdInWiki($id, $wikiId)
  {
 
    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('wp')
            ->from('metaStandardProjectProfileBundle:WikiPage', 'wp')
            ->join('wp.wiki', 'w')
            ->where('w.id = :pid')
            ->setParameter('pid', $wikiId)
            ->andWhere('wp.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery();

    try {
        $wikiPage = $query->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
        $wikiPage = null;
    }

    return $wikiPage;

  }

  public function findFirstInWiki($wikiId)
  {
    
    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('wp')
            ->from('metaStandardProjectProfileBundle:WikiPage', 'wp')
            ->join('wp.wiki', 'w')
            ->where('w.id = :pid')
            ->setParameter('pid', $wikiId)
            ->orderBy('wp.rank', 'ASC')
            ->setMaxResults(1)
            ->getQuery();

    try {
        $wikiPage = $query->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
        $wikiPage = null;
    }

    return $wikiPage;

  }

  public function findAllInWiki($wikiId)
  {

    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('wp')
            ->from('metaStandardProjectProfileBundle:WikiPage', 'wp')
            ->join('wp.wiki', 'w')
            ->where('w.id = :pid')
            ->setParameter('pid', $wikiId)
            ->orderBy('wp.rank', 'ASC')
            ->getQuery()
            ->getResult();
  }

  public function findAllRootInWiki($wikiId)
  {

    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('wp')
            ->from('metaStandardProjectProfileBundle:WikiPage', 'wp')
            ->join('wp.wiki', 'w')
            ->where('w.id = :pid')
            ->setParameter('pid', $wikiId)
            ->andWhere('wp.parent IS NULL')
            ->orderBy('wp.rank', 'ASC')
            ->getQuery()
            ->getResult();
  }
}
