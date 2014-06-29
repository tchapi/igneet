<?php

namespace meta\UserBundle\Entity;

use Doctrine\ORM\EntityRepository,
    Symfony\Component\Security\Core\User\UserProviderInterface ,
    Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\Security\Core\Exception\UsernameNotFoundException,
    Symfony\Component\Security\Core\Exception\DisabledException,
    Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * UserRepository
 *
 */
class UserRepository extends EntityRepository implements UserProviderInterface 
{

  /* This is because we want to be able to login with username OR email */
  function loadUserByUsername($username)
  {

      $qb = $this->createQueryBuilder('u') ;

      try {

        $user = $qb->select('u')
        ->where(
            $qb->expr()->orx(
                $qb->expr()->like('u.username' ,':username') ,
                $qb->expr()->like('u.email' ,':username')
            )
        )
        ->setParameters(array('username' =>$username ) )        
        ->getQuery()
        ->getSingleResult(); 

      } catch (\Doctrine\Orm\NoResultException $e) {

        throw new UsernameNotFoundException();

      }

      /* Bad credentials */
      if (is_null($user)){
        throw new BadCredentialsException();
      }

      if ($user->isDeleted()) {
        throw new UsernameNotFoundException(); // DisabledException if we want to be perfectly clear, but the message is cryptic
      } else {
        return $user;  
      }              
  }

  function refreshUser(UserInterface $user)
  {
      return $this->loadUserByUsername($user->getUsername() );
  }

  function supportsClass($class)
  {
      return $class === 'meta\UserBundle\Entity\User';
  }

  public function findOneByEmail($email)
  {
    if ($email != "") {
      return parent::findOneByEmail($email);
    } else {
      return null;
    }
  }

  /*
   * Count all users in a given community
   * Includes GUESTS as well
   * Options :
   * - 'community'
   * - 'includeGuests'
   */
  public function countUsersInCommunity($options)
  {
    
    if ($options['community'] === null){
      return 0;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('COUNT(u)')
            ->from('metaUserBundle:User', 'u')
            ->leftJoin('u.userCommunities', 'uc')
            ->leftJoin('uc.community', 'c')
            ->where('u.deleted_at IS NULL')
            ->andWhere('c = :community')
            ->setParameter('community', $options['community']);

    if ($options['includeGuests'] !== true){
      $query->andWhere('uc.guest = :guest')
            ->setParameter('guest', false);
    }

    return $query->getQuery()
                ->getSingleScalarResult();

  }

  /*
   * Count all managers in a given community
   * Options :
   * - 'community'
   */
  public function countManagersInCommunity($options)
  {
    
    if ($options['community'] === null){
      return 0;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();

    return $qb->select('COUNT(u)')
            ->from('metaUserBundle:User', 'u')
            ->leftJoin('u.userCommunities', 'uc')
            ->leftJoin('uc.community', 'c')
            ->where('u.deleted_at IS NULL')
            ->andWhere('uc.manager = 1')
            ->andWhere('c = :community')
            ->setParameter('community', $options['community'])
            ->getQuery()
            ->getSingleScalarResult();

  }

  /*
   * Fetch all users in a given community
   * Options :
   * - 'community'
   * - 'includeGuests'
   * - 'sort'
   * - 'page'
   * - 'maxPerPage'
   */
  public function findAllUsersInCommunity($options)
  {

    if ($options['community'] === null){
      return null;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();
    $query = $qb->select('u AS user')
            ->addSelect('uc.guest AS isGuest') // We select as well the boolean 'isGuest'
            ->from('metaUserBundle:User', 'u')
            ->leftJoin('u.userCommunities', 'uc')
            ->leftJoin('uc.community', 'c')
            ->where('u.deleted_at IS NULL')
            ->andWhere('c = :community')
            ->setParameter('community', $options['community']);
            
    if ($options['includeGuests'] !== true){
      $query->andWhere('uc.guest = :guest')
            ->setParameter('guest', false);
    }

    switch ($options['sort']) {
      case 'update':
        $query->orderBy('u.updated_at', 'DESC');
        break;
      case 'alpha':
        $query->orderBy('u.first_name', 'ASC');
        break;
      case 'active':
        $query->orderBy('u.last_seen_at', 'DESC');
        break;
      case 'newest':
      default:
        $query->orderBy('u.created_at', 'DESC');
        break;
    }

    return $query
            ->setFirstResult(($options['page']-1)*$options['maxPerPage'])
            ->setMaxResults($options['maxPerPage'])
            ->getQuery()
            ->getResult();

  }

  /*
   * Fetch all users in a given community, except the user $user
   * Options :
   * - 'community'
   * - 'includeGuests'
   * - 'user'
   */
  public function findAllUsersInCommunityExceptMe($options)
  {
    
    if ($options['community'] === null){
      return null;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('u')
            ->from('metaUserBundle:User', 'u')
            ->leftJoin('u.userCommunities', 'uc')
            ->leftJoin('uc.community', 'c')
            ->where('u.deleted_at IS NULL')
            ->andWhere('c = :community')
            ->setParameter('community', $options['community'])
            ->andWhere('u <> :user')
            ->setParameter('user', $options['user']);

    if ($options['includeGuests'] !== true){
      $query->andWhere('uc.guest = :guest')
            ->setParameter('guest', false);
    }

    return $query
            ->getQuery()
            ->getResult();

  }

  /*
   * Fetch all followers of a user in the given community
   * Options :
   * - 'community'
   * - 'user'
   */
  public function findAllFollowersInCommunityForUser($options)
  {

    if ($options['community'] === null){
      return null;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->select('u')
            ->from('metaUserBundle:User', 'u')
            ->join('u.following', 'f')
            ->leftJoin('u.userCommunities', 'uc')
            ->join('uc.community', 'c')
            ->where('u.deleted_at IS NULL')
            ->andWhere('f = :user')
            ->setParameter('user', $options['user'])
            ->andWhere('c = :community')
            ->setParameter('community', $options['community'])
            ->getQuery()
            ->getResult();
  }

  /*
   * Fetch all followings of a user in the given community
   * Options :
   * - 'community'
   * - 'user'
   */
  public function findAllFollowingInCommunityForUser($options)
  {

    if ($options['community'] === null){
      return null;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();
    
    return $qb->select('u')
            ->from('metaUserBundle:User', 'u')
            ->join('u.followers', 'f')
            ->leftJoin('u.userCommunities', 'uc')
            ->join('uc.community', 'c')
            ->where('u.deleted_at IS NULL')
            ->andWhere('f = :user')
            ->setParameter('user', $options['user'])
            ->andWhere('c = :community')
            ->setParameter('community', $options['community'])
            ->getQuery()
            ->getResult();
  }

  /*
   * Find a user by its username in a given community
   * Options :
   * - 'community'
   * - 'username'
   * - 'includeGuests'
   */
  public function findOneByUsernameInCommunity($options)
  {

    if ($options['community'] === null){
      return null;
    }

    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('u')
            ->from('metaUserBundle:User', 'u')
            ->leftJoin('u.userCommunities', 'uc')
            ->leftJoin('uc.community', 'c')
            ->where('u.deleted_at IS NULL')
            ->andWhere('c = :community')
            ->setParameter('community', $options['community']);
            
    if ($options['includeGuests'] !== true){
      $query->andWhere('uc.guest = :guest')
            ->setParameter('guest', false);
    }

    $query->andWhere('u.username = :username')
          ->setParameter('username', $options['username']);

    try {
        $result = $query->getQuery()->getSingleResult();
    } catch (\Doctrine\Orm\NoResultException $e) {
        $result = null;
    }

    return $result;
  }

  /*
   * Find a community that is common to the two users and returns it (the community!)
   */
  public function findCommonCommunity($user1, $user2)
  {

    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('uc, COUNT(uc.user) as nbCommunities')
            ->from('metaUserBundle:UserCommunity', 'uc')
            ->where('uc.user = :user1 OR uc.user = :user2')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->groupBy('uc.community')
            ->having('nbCommunities > 1')
            ->getQuery()
            ->getResult();

    if (isset($query[0]) && isset($query[0][0])){
      return $query[0][0]->getCommunity();
    } else {
      return null;
    }

  }

  /*
   * Find all communities a user is in, as a guest or not
   */
  public function findCommunitiesOfUser($user, $guest = false)
  {

    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('c')
            ->from('metaGeneralBundle:Community\Community', 'c')
            ->join('c.userCommunities', 'uc')
            ->where('uc.user = :user')
            ->setParameter('user', $user)
            ->andWhere('uc.guest = :guest')
            ->setParameter('guest', $guest)
            ->groupBy('c');

    return $query->getQuery()
                 ->getResult();

  }


  /* 
   * Find users that should be sent a digest on the date passer
   */
  public function findUsersWhoNeedDigestOnDay($dayOfWeek, $isEvenWeek, $isDefaultDay)
  {

    $qb = $this->getEntityManager()->createQueryBuilder();

    $query = $qb->select('u')
            ->from('metaUserBundle:User', 'u')
            ->where('u.deleted_at IS NULL');

    if ($isDefaultDay){
      $digestDayQueryString = "(u.digestDay = '" . $dayOfWeek . "' OR u.digestDay IS NULL OR u.enableSpecificDay = 0)";
    } else {
      $digestDayQueryString = "(u.digestDay = '" . $dayOfWeek . "' AND u.enableSpecificDay = 1)";
    }

    if ($isEvenWeek){
      $query->andWhere("u.digestFrequency = 'daily' OR " . $digestDayQueryString) ;
    } else {
      $query->andWhere("u.digestFrequency = 'daily' OR ( (u.digestFrequency = 'weekly' OR u.digestFrequency IS NULL) AND " . $digestDayQueryString . ")");
    }
   
    return $query->getQuery()
                 ->getResult();

  }

}
