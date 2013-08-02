<?php

namespace meta\GeneralBundle\Services;

use Doctrine\ORM\EntityManager;

use meta\UserBundle\Entity\User,
    meta\GeneralBundle\Entity\Log\UserLogEntry,
    meta\GeneralBundle\Entity\Log\IdeaLogEntry,
    meta\GeneralBundle\Entity\Log\StandardProjectLogEntry,
    meta\GeneralBundle\Entity\Comment\BaseComment;

class LogService
{

    private $em;
    private $log_types, $log_routing, $concurrent_merge_interval;
    private $twig, $template_link, $template_link_null, $template_item;

    public function __construct(EntityManager $entity_manager, $log_types, $log_routing, $log_concurrent_merge_interval, $security_context, $twig, $translator, $uid)
    {
        $this->em = $entity_manager;
        
        $this->log_types = $log_types;
        $this->log_routing = $log_routing;
        $this->concurrent_merge_interval = $log_concurrent_merge_interval;

        $this->security_context = $security_context;

        $this->twig = $twig;
        $this->translator = $translator;

        $this->uid = $uid;

        $this->template_link         = 'metaGeneralBundle:Log:logLink.html.twig';
        $this->template_link_null    = 'metaGeneralBundle:Log:logLink.null.html.twig';
        $this->template_item         = 'metaGeneralBundle:Log:logItem.html.twig';
        $this->template_item_comment = 'metaGeneralBundle:Log:logItemComment.html.twig';
    }

    public function log($user, $logActionName, $subject, array $objects)
    {

        // Find the type of LogEntry
        $type = $this->log_types[$logActionName]['type'];
        
        switch ($type) {
            case 'idea':
                 $entry = new IdeaLogEntry();
                 $repositoryName = 'metaGeneralBundle:Log\IdeaLogEntry';
                 $subjectType = "idea";
                 break;
            case 'project':
                 $entry = new StandardProjectLogEntry();
                 $repositoryName = 'metaGeneralBundle:Log\StandardProjectLogEntry';
                 $subjectType = "standardProject";
                 break;
            case 'other_user':
                 $entry = new UserLogEntry();
                 $repositoryName = 'metaGeneralBundle:Log\UserLogEntry';
                 $subjectType = "other_user";
                 break;   
            default:
                 return;
        }
        
        $lastEntry = null;

        if ($this->log_types[$logActionName]['combinable'] === true){
            // Merge concurrent log updates
            // Check if there is a similar log less than X seconds ago
            $repository = $this->em->getRepository('metaGeneralBundle:Log\BaseLogEntry');
            $lastEntries = $repository->findSimilarEntries($repositoryName, $user, $logActionName, $subjectType, $subject, date_create('now -'.$this->concurrent_merge_interval.' seconds'));

            foreach ($lastEntries as $entry) {
                if ( $entry->getObjects() == $objects ) {
                    $lastEntry = $entry;
                    break;
                }
            }
        }

        if ( !is_null($lastEntry) ) {
            // if update < XX secondes , then update the date of the old one with the new date
            $lastEntry->setCreatedAt(new \Datetime('now'));
            $lastEntry->incrementCombinedCount();
        } else {

            // else persists the new log
            $entry->setCommunity($this->security_context->getToken()->getUser()->getCurrentCommunity());
            $entry->setUser($user);
            $entry->setType($logActionName);
            $entry->setSubject($subject);
            $entry->setObjects($objects);

            $this->em->persist($entry);
        }

        // Flush the shit
        $this->em->flush();

    }

    public function getText($logEntryOrComment)
    {

        if ( is_null($logEntryOrComment) ) {
            return $this->twig->render($this->template_link_null);
        }

        if ($logEntryOrComment instanceof BaseComment) {

            return $logEntryOrComment->getText();
            
        } else {

            $parameters = $this->getParameters($logEntryOrComment);

            // We get the text for the log
            return $this->translator->trans( "logs." . $logEntryOrComment->getType(), $parameters, 'logs' );

        }

    }

    public function getHTML($logEntryOrComment)
    {

        if ( is_null($logEntryOrComment) ) {
            return $this->twig->render($this->template_link_null);
        }

        if ($logEntryOrComment instanceof BaseComment) {

            $text = $logEntryOrComment->getText();
            $user = $logEntryOrComment->getUser();
            $date = $logEntryOrComment->getCreatedAt();

            return $this->twig->render($this->template_item_comment, array('user' => $user, 'comment' => $logEntryOrComment));
            
        } else {

            $parameters = $this->getParameters($logEntryOrComment);

            // We get the formatted text for the log
            $text = $this->translator->trans( "logs." . $logEntryOrComment->getType(), $parameters, 'logs' );

            $date = $logEntryOrComment->getCreatedAt();
            $user = $logEntryOrComment->getUser();
            $combinedCount = $logEntryOrComment->getCombinedCount();
            $icon = $this->log_types[$logEntryOrComment->getType()]['icon'];

            return $this->twig->render($this->template_item, array( 'icon' => $icon, 'user' => $user, 'text' => $text, 'date' => $date, 'combinedCount' => $combinedCount));

        }

    }

    private function getParameters($logEntry)
    {

        $type = $this->log_types[$logEntry->getType()]['type'];
        $parameters = array();

        // Fetches parameters to display the link to the user that created the log
        $user_logName = $logEntry->getUser()->getLogName();

        // Constructs the uid from the id (in this case, we don't need to obfuscate)
        $user_uid = $logEntry->getUser()->getUsername();

        // Deleted users should not be linked to
        if ($logEntry->getUser()->isDeleted()) {
            $user_routing = null;
        } else {
            $user_routing = array( 'path' => $this->log_routing['user']['path'], 'args' => array( $this->log_routing['user']['key'] => $user_uid ) );
        } 
       
        $parameters["%user%"] = $this->twig->render($this->template_link, array( 'logName' => $user_logName, 'routing' => $user_routing ) );


        // Fetches parameters to display the subject link
        $subject_logName = $logEntry->getSubject()->getLogName();

        // Constructs the uid from the id
        $subject_uid = $logEntry->getSubject()->getId();
        if ($this->log_routing["$type"]['is_uid']){
            $subject_uid = $this->uid->toUId($subject_uid);
        }

        // Creates the routing
        $subject_routing = array( 'path' => $this->log_routing["$type"]['path'], 'args' => array( $this->log_routing["$type"]['key'] => $subject_uid ) );

        $parameters["%$type%"] = $this->twig->render($this->template_link, array( 'logName' => $subject_logName, 'routing' => $subject_routing ) );


        // Now fetches objects
        foreach ($logEntry->getObjects() as $key => $object) {

            // Can we link to the object ? (backward compatibility is ensured here)
            if ( !isset($object['identifier']) || is_null($object['identifier']) ) {
                $routing = null;
            } else {

                // Constructs the uid from the id
                $object_uid = $object['identifier'];
                if ($this->log_routing["$key"]['is_uid']){
                    $object_uid = $this->uid->toUId($object_uid);
                }

                // we need to merge with the subject for the routing (/project/{uid}/list/{list_uid} for example)
                $routing = array( 'path' => $this->log_routing["$key"]['path'], 'args' => array_merge($subject_routing['args'], array( $this->log_routing["$key"]['key'] => $object_uid )) ); 
            
            }

            $parameters["%$key%"] = $this->twig->render($this->template_link, array( 'logName' => $object['logName'], 'routing' => $routing ) );
            
        }

        return $parameters;

    }

}