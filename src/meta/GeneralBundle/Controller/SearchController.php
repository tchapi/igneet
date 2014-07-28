<?php

namespace meta\GeneralBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse;

use meta\IdeaBundle\Entity\Idea,
    meta\ProjectBundle\Entity\StandardProject,
    meta\ProjectBundle\Entity\Resource,
    meta\ProjectBundle\Entity\WikiPage,
    meta\ProjectBundle\Entity\CommonList,
    meta\ProjectBundle\Entity\CommonListItem,
    meta\UserBundle\Entity\User;

class SearchController extends Controller
{
     
    /*
     * Allow to search the wide index
     */
    public function searchAction(Request $request, $type)
    {

      $term = $request->request->get('term');
      $limit = $this->container->getParameter('search.limit');

      $authenticatedUser = $this->getUser();
      // No term ? Redirect
      if ($term == "") {

        $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('search.no.term')
                );
        return $this->redirect($this->generateUrl('g_home_community'));
      
      }

      if ($type !== null) {
        $finder = $this->container->get('fos_elastica.finder.igneet.' . $type);
      } else {
        $finder = $this->container->get('fos_elastica.finder.igneet');
      }


      // Creates the query correctly with a bool
      $boolQuery = new \Elastica\Query\Bool();

      $queryString = new \Elastica\Query\QueryString();
      $queryString->setDefaultField('_all');
      $queryString->setQuery($term);
      $boolQuery->addMust($queryString);

      $query = new \Elastica\Query($boolQuery);

      $query->setHighlight(array(
          "fields" => array("*" => new \stdClass)
      ));
      $query->setSize($limit);

      // Returns a mixed array of any objects mapped + highlights
      $results = $finder->findHybrid($query);

      // Init some arrays for the JSON
      $projects = array();
      $ideas = array();
      $users = array();
      $resources = array();
        $types = $this->container->getParameter('project.resource_types');
      $lists = array();
      $listitems = array();
      $wikipages = array();

      // Get all the communities the user is in
      $validCommunities = $this->getDoctrine()->getRepository('metaUserBundle:User')->findCommunitiesOfUser($authenticatedUser, false);
      $validGuestCommunities = $this->getDoctrine()->getRepository('metaUserBundle:User')->findCommunitiesOfUser($authenticatedUser, true);

      // Define a test function to know if we can display the result
      $test = function ($project) use ($validCommunities, $validGuestCommunities, $authenticatedUser) {
        
        if (is_null($project) || $project->isDeleted()) { return false; }
        //  project is in one of my communities AND (project not private OR (project private AND owner or participant )  )
        //    OR 
        //  project in guest communities AND (owner OR participant)
        $privateSpace = is_null($project->getCommunity());
        $inCommunity = in_array($project->getCommunity(), $validCommunities);
        $inProject = $project->getOwners()->contains($authenticatedUser) || $project->getParticipants()->contains($authenticatedUser);
        $publicProject = !$project->isPrivate();
        $inGuestCommunity = in_array($project->getCommunity(), $validGuestCommunities);

        // error_log('inCommunity : ' . ($inCommunity?"true":"false"));
        // error_log('inProject : ' . ($inProject?"true":"false"));
        // error_log('publicProject : ' . ($publicProject?"true":"false"));
        // error_log('inGuestCommunity : ' . ($inGuestCommunity?"true":"false"));

        $allowed = ($privateSpace && $inProject) || ($inCommunity && ($publicProject || $inProject)) || ($inGuestCommunity && $inProject);

        return ($allowed === true);
      };

      $rawTotal = 0;
      foreach($results as $rawResult) {

        $rawTotal += 1;
        $result = $rawResult->getTransformed();
        $highlights = end($rawResult->getResult()->getHighlights()); // Gets the last item of the array
        $highlight = strip_tags($highlights[0], '<em>');

        /* Project */
        /***********/
        if ($result instanceof StandardProject) {

          // User in community, etc ..
          if ($test($result)) {
            $projects[] = array(
              'path' => $this->generateUrl('p_show_project', array('uid' => $this->container->get('uid')->toUId($result->getId()))),
              'picture' => $result->getPicture(),
              'title' => $result->getName(),
              'highlight' => $highlight
            );
          }
          
        /* Project derivatives */
        /***********************/
        } elseif ($result instanceof WikiPage) {
            
          if ($test($result->getWiki()->getProject())) {
            $wikipages[] = array(
              'path' => $this->generateUrl('p_show_project_wiki_show_page', array('uid' => $this->container->get('uid')->toUId($result->getWiki()->getProject()->getId()), 'page_uid' => $this->container->get('uid')->toUId($result->getId()))),
              'picture' => null,
              'title' => $result->getTitle(),
              'extra' => $result->getWiki()->getProject()->getName(),
              'extra_picture' => $result->getWiki()->getProject()->getPicture(),
              'highlight' => $highlight
            );
          }

        } elseif ($result instanceof Resource) {
          
          if ($test($result->getProject())) {
            $resources[] = array(
              'path' => $this->generateUrl('p_show_project_resource', array('uid' => $this->container->get('uid')->toUId($result->getProject()->getId()), 'resource_uid' => $this->container->get('uid')->toUId($result->getId()))),
              'picture' => '/bundles/metageneral/img/icons/' . $types[$result->getType()]["icon"] . '.png',
              'title' => $result->getTitle(),
              'extra' => $result->getProject()->getName(),
              'extra_picture' => $result->getProject()->getPicture(),
              'highlight' => $highlight
            );
          }

        } elseif ($result instanceof CommonList) {
          
          if ($test($result->getProject())) {
            $lists[] = array(
              'path' => $this->generateUrl('p_show_project_list', array('uid' => $this->container->get('uid')->toUId($result->getProject()->getId()), 'list_uid' => $this->container->get('uid')->toUId($result->getId()))),
              'picture' => null,
              'title' => $result->getName(),
              'extra' => $result->getProject()->getName(),
              'extra_picture' => $result->getProject()->getPicture(),
              'highlight' => $highlight
            );
          }

        } elseif ($result instanceof CommonListItem) {
          
          if ($test($result->getCommonList()->getProject())) {
            $listitems[] = array(
              'path' => $this->generateUrl('p_show_project_list', array('uid' => $this->container->get('uid')->toUId($result->getCommonList()->getProject()->getId()), 'list_uid' => $this->container->get('uid')->toUId($result->getCommonList()->getId()))),
              'picture' => null,
              'title' => $result->getText(),
              'extra' => $result->getCommonList()->getProject()->getName(),
              'extra_picture' => $result->getCommonList()->getProject()->getPicture(),
              'highlight' => $highlight
            );
          }

        /*  Idea   */
        /***********/
        } elseif ($result instanceof Idea) {

          if (in_array($result->getCommunity(),$validCommunities)) {
            $ideas[] = array(
              'path' => $this->generateUrl('i_show_idea', array('uid' => $this->container->get('uid')->toUId($result->getId()))),
              'picture' => $result->getPicture(),
              'title' => $result->getName(),
              'highlight' => $highlight
            );
          }

        /*   User  */
        /***********/
        } elseif ($result instanceof User) {

          $valid = false;
          foreach ($result->getUserCommunities() as $userCommunity) {
            if (in_array($userCommunity->getCommunity(),$validCommunities)) {
              $valid = true;
              break;
            }
          }

          if ($valid) {
            $users[] = array(
              'path' => $this->generateUrl('u_show_user_profile', array('username' => $result->getUsername())),
              'picture' => $result->getAvatar(),
              'title' => $result->getFullName(),
              'highlight' => $highlight
            );
          }

        }
        
      }

      // Displays a notice if we hit the limit ... ask the user to be more precise
      if ($rawTotal >= $limit) {
        $showNotice = true;
      } else {
        $showNotice = false;
      }

      $total = count($projects) + count($ideas) + count($users) + count($wikipages) + count($lists) + count($listitems) + count($resources);

      return $this->render('metaGeneralBundle:Search:results.html.twig', array(
        'icons' => $this->container->getParameter('search.icons'),
        'limit' => $this->container->getParameter('search.limit'),
        'total' => $total,
        'showNotice' => $showNotice,
        'term' => $term,
        'data' => array(
          'projects' => $projects, 
          'ideas' => $ideas, 
          'users' => $users,
          'wikipages' => $wikipages,
          'resources' => $resources,
          'lists' => $lists, 
          'listitems' => $listitems)
        ));

    }

}
