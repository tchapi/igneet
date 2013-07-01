<?php

namespace meta\GeneralBundle\Twig;

use Symfony\Component\Routing\Router;

class DeepLinkingExtension extends \Twig_Extension
{

    private $deep_linking_tags, $em, $template, $router;

    public function __construct($deep_linking_tags, $entity_manager, Router $router, $translator, $uid, $log_routing)
    {

        $this->deep_linking_tags = $deep_linking_tags;
        $this->em = $entity_manager;
        $this->router = $router;

        $this->translator = $translator;
        $this->uid = $uid;
        $this->log_routing = $log_routing;

        $this->template = '<a title="' . $translator->trans('goto.related.object') . '" href="%s"><i class="icon-%s"></i> %s</a>';
        $this->templateUnknown = '<strong><i class="icon-%s"></i> %s</strong>';

    }

    public function getFilters()
    {
        return array(
            'deeplinks' => new \Twig_Filter_Method($this, 'convertDeepLinks'),
        );
    }

    private function renderLink($params)
    {
        if (is_null($params['args'])) {
            return sprintf($this->templateUnknown, $params['icon'], $params['title']);
        } else {
            $url = $this->router->generate($params['path'], $params['args']);
            return sprintf($this->template, $url, $params['icon'], $params['title']);
        }

    }

    public function convertDeepLinks($text)
    {

        $count = preg_match_all("/\[\[(\w+?)\:(\w+?)\]\]/", $text, $matches);

        if($count > 0)
        {
             for($i = 0; $i < $count; $i++)
             {
                 // $matches[0][$i] contains the entire matched string
                 // $matches[1][$i] contains the first portion (ex: user)
                 // $matches[2][$i] contains the second portion (ex: tchap)

                $matched = false;
                $title = $args = null;

                switch ($matches[1][$i]) {
                    case 'user':
                        $repository = $this->em->getRepository('metaUserBundle:User');
                        $matched = true;
                        $user = $repository->findOneByUsername($matches[2][$i]);
                        if ($user && !$user->isDeleted()){
                            $args = array( $this->log_routing['user']['key'] => $user->getUsername());
                            $title = $user->getFullName();  
                        } else {
                            $title = $this->translator->trans('unknown.user', array(), 'errors');
                        }
                        break;
                    
                    case 'project':
                        $repository = $this->em->getRepository('metaProjectBundle:StandardProject');
                        $matched = true;
                        $project = $repository->findOneById($matches[2][$i]);
                        if ($project && !$project->isDeleted()){
                            $args = array( $this->log_routing['project']['key'] => $this->uid->toUId($project->getId()));
                            $title = $project->getName();  
                        } else {
                            $title = $this->translator->trans('unknown.project', array(), 'errors');
                        }
                        break;
                    
                    case 'idea':
                        $repository = $this->em->getRepository('metaIdeaBundle:Idea');
                        $matched = true;
                        $idea = $repository->findOneById($matches[2][$i]);
                        if ($idea && !$idea->isDeleted()){
                            $args = array( $this->log_routing['idea']['key'] => $this->uid->toUId($idea->getId()));
                            $title = $idea->getName();  
                        } else {
                            $title = $this->translator->trans('unknown.idea', array(), 'errors');
                        }
                        break;

                    case 'wikipage':
                        $repository = $this->em->getRepository('metaProjectBundle:WikiPage');
                        $wikiPage = $repository->findOneById($matches[2][$i]);
                        $matched = true;
                        if ($wikiPage && $wikiPage->getWiki()){
                            $project = $wikiPage->getWiki()->getProject();
                            $args = array( $this->log_routing['project']['key'] => $this->uid->toUId($project->getId()), $this->log_routing['wikipage']['key'] => $this->uid->toUId($wikiPage->getId()));
                            $title = $wikiPage->getTitle();  
                        } else {
                            $title = $this->translator->trans('unknown.wikipage', array(), 'errors');
                        }
                        break;

                    case 'list':
                        $repository = $this->em->getRepository('metaProjectBundle:CommonList');
                        $commonList = $repository->findOneById($matches[2][$i]);
                        $matched = true;
                        if ($commonList){
                            $project = $commonList->getProject();
                            $args = array( $this->log_routing['project']['key'] => $this->uid->toUId($project->getId()), $this->log_routing['list']['key'] => $this->uid->toUId($commonList->getId()));
                            $title = $commonList->getName();  
                        } else {
                            $title = $this->translator->trans('unknown.list', array(), 'errors');
                        }
                        break;

                    case 'resource':
                        $repository = $this->em->getRepository('metaProjectBundle:Resource');
                        $resource = $repository->findOneById($matches[2][$i]);
                        $matched = true;
                        if ($resource){
                            $project = $resource->getProject();
                            $args = array( $this->log_routing['project']['key'] => $this->uid->toUId($project->getId()), $this->log_routing['resource']['key'] => $this->uid->toUId($resource->getId()));
                            $title = $resource->getTitle();  
                        } else {
                            $title = $this->translator->trans('unknown.resource', array(), 'errors');
                        }
                        break;
                }

                if ($matched){

                    $config = $this->deep_linking_tags[ $matches[1][$i] ];
                    $replacement = $this->renderLink(
                        array('path' => $config['routing'], 
                              'args' => $args,
                              'icon' => $config['icon'], 
                              'title' => $title
                        ));

                    $text = str_replace($matches[0][$i], $replacement, $text);
                }

             }
        }

        return $text;

    }

    public function getName()
    {
        return 'deep_linking_extension';
    }
}