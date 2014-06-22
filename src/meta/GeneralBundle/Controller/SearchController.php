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
    public function searchAction(Request $request)
    {

      $term = $request->request->get('term');

      // No term ? Redirect
      if ($term == "") {

        $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('search.no.term')
                );

        return $this->redirect($this->generateUrl('g_home_community'));
      
      }

      $finder = $this->container->get('fos_elastica.finder.igneet');

      // Returns a mixed array of any objects mapped
      $results = $finder->find($term);

      // Init some arrays for the JSON
      $projects = array();
      $ideas = array();
      $users = array();
      $resources = array();
      $lists = array();
      $wikipages = array();

      foreach($results as $result) {

        // TODO FIXME
        // TODO Check if $authenticatedUser has the right to see the items
        // TODO FIXME

        if ($result instanceof StandardProject) {
          $projects[] = array(
            'path' => $this->generateUrl('p_show_project', array('uid' => $this->container->get('uid')->toUId($result->getId()))),
            'picture' => $result->getPicture(),
            'title' => $result->getName(),
            'extra' => $result->getHeadline()
          );
        } elseif ($result instanceof Idea) {
          $ideas[] = array(
            'path' => $this->generateUrl('i_show_idea', array('uid' => $this->container->get('uid')->toUId($result->getId()))),
            'picture' => $result->getPicture(),
            'title' => $result->getName(),
            'extra' => $result->getHeadline()
          );
        } elseif ($result instanceof User) {
          $users[] = array(
            'path' => $this->generateUrl('u_show_user_profile', array('username' => $result->getUsername())),
            'picture' => $result->getAvatar(),
            'title' => $result->getFullName(),
            'extra' => $result->getHeadline()
          );
        } elseif ($result instanceof WikiPage) {
          $wikipages[] = array(
            'path' => $this->generateUrl('p_show_project_wiki_show_page', array('uid' => $this->container->get('uid')->toUId($result->getWiki()->getProject()->getId()), 'page_uid' => $this->container->get('uid')->toUId($result->getId()))),
            'picture' => $result->getWiki()->getProject()->getPicture(),
            'title' => $result->getTitle(),
            'extra' => $result->getWiki()->getProject()->getName()
          );
        } elseif ($result instanceof Resource) {
          $resources[] = array(
            'path' => $this->generateUrl('p_show_project_resource', array('uid' => $this->container->get('uid')->toUId($result->getProject()->getId()), 'resource_uid' => $this->container->get('uid')->toUId($result->getId()))),
            'picture' => $result->getProject()->getPicture(),
            'title' => $result->getTitle(),
            'extra' => $result->getProject()->getName()
          );
        } elseif ($result instanceof CommonList) {
          $lists[] = array(
            'path' => $this->generateUrl('p_show_project_list', array('uid' => $this->container->get('uid')->toUId($result->getProject()->getId()), 'list_uid' => $this->container->get('uid')->toUId($result->getId()))),
            'picture' => $result->getProject()->getPicture(),
            'title' => $result->getName(),
            'extra' => $result->getProject()->getName()
          );
        } elseif ($result instanceof CommonListItem) {
          $listitems[] = array(
            'path' => $this->generateUrl('p_show_project_list', array('uid' => $this->container->get('uid')->toUId($result->getCommonList()->getProject()->getId()), 'list_uid' => $this->container->get('uid')->toUId($result->getCommonList()->getId()))),
            'picture' => $result->getCommonList()->getProject()->getPicture(),
            'title' => $result->getText(),
            'extra' => $result->getCommonList()->getProject()->getName()
          );
        }
        
      }

      $total = count($results);

      return $this->render('metaGeneralBundle:Search:results.html.twig', array(
        'total' => $total,
        'term' => $term,
        'data' => array(
          'projects' => $projects, 
          'ideas' => $ideas, 
          'users' => $users,
          'wikipages' => $wikipages,
          'lists' => $lists, 
          'resources' => $resources)
        ));

    }

}
