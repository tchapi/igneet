<?php

namespace meta\GeneralBundle\Services;

use Doctrine\ORM\EntityManager;
use meta\UserProfileBundle\Entity\User,
    meta\GeneralBundle\Entity\Log\UserLogEntry,
    meta\GeneralBundle\Entity\Log\IdeaLogEntry,
    meta\GeneralBundle\Entity\Log\StanardProjectLogEntry;

class LogService
{

    private $em;
    private $log_types;
    private $authenticatedUser;

    public function __construct(EntityManager $entityManager, $log_types)
    {
        $this->em = $entityManager;
        $this->log_types = $log_types;
    }

    public function log($user, $name, $subject, array $objects)
    {

        // Find the type of LogEntry
        $type = $this->log_types[$name]['type'];

        switch ($type) {
            case 'idea':
                 $entry = new IdeaLogEntry();
                 break;
            case 'project':
                 $entry = new StandardProjectLogEntry();
                 break;
            case 'user':
                 $entry = new UserLogEntry();
                 break;   
            default:
                 $entry = new BaseLogEntry();
                 break;
         }
            

        // Merge concurrent updates
        // if update < XX secondes , then update the date of the old one with the new date

        $entry->setUser($user);
        $entry->setType($name);
        $entry->setSubject($subject);
        $entry->setObjects($objects);

        // pour chaque entité, faire une fonction getLogLink()
        // Si un object a été supprimé, ne pas faire le lien et/ou l'indiquer ?

        $this->em->persist($entry);
        $this->em->flush();

        return true;

    }


    /**
     * version of sprintf for cases where named arguments are desired (php syntax)
     *
     * with sprintf: sprintf('second: %2$s ; first: %1$s', '1st', '2nd');
     *
     * with sprintfn: sprintfn('second: %second$s ; first: %first$s', array(
     *  'first' => '1st',
     *  'second'=> '2nd'
     * ));
     *
     * @param string $format sprintf format string, with any number of named arguments
     * @param array $args array of [ 'arg_name' => 'arg value', ... ] replacements to be made
     * @return string|false result of sprintf call, or bool false on error
     */
    private function sprintfn ($format, array $args = array())
    {
        // map of argument names to their corresponding sprintf numeric argument value
        $arg_nums = array_slice(array_flip(array_keys(array(0 => 0) + $args)), 1);

        // find the next named argument. each search starts at the end of the previous replacement.
        for ($pos = 0; preg_match('/(?<=%)([a-zA-Z_]\w*)(?=\$)/', $format, $match, PREG_OFFSET_CAPTURE, $pos);) {
            $arg_pos = $match[0][1];
            $arg_len = strlen($match[0][0]);
            $arg_key = $match[1][0];

            // programmer did not supply a value for the named argument found in the format string
            if (! array_key_exists($arg_key, $arg_nums)) {
                user_error("sprintfn(): Missing argument '${arg_key}'", E_USER_WARNING);
                return false;
            }

            // replace the named argument with the corresponding numeric one
            $format = substr_replace($format, $replace = $arg_nums[$arg_key], $arg_pos, $arg_len);
            $pos = $arg_pos + strlen($replace); // skip to end of replacement for next iteration
        }

        return vsprintf($format, array_values($args));
    }

}