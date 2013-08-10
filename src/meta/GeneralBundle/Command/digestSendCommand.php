<?php
namespace meta\GeneralBundle\Command;
 
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;  
use Symfony\Component\Console\Input\InputArgument;  
use Symfony\Component\Console\Input\InputInterface;  
use Symfony\Component\Console\Input\InputOption;  
use Symfony\Component\Console\Output\OutputInterface;  
  
class digestSendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        parent::configure();  
        $this->setName('digest:send')->setDescription('Sends the automatic mail digests (only if --force is passed)');

        // By default, no mails are sent, the output is just printed out (number of mails sent)
        $this->addOption('force', null,  InputOption::VALUE_NONE, 'Sends the mail FOR REAL');
    }
  
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      // Should we really send mails
      $sendMails = $input->getOption('force');
 
      $today = $this->getActualDay();
      $output->writeln('Today is : ' . $today);

      // Get userCommunities that must be sent today

      $userRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:User');

      //
      // TODO TODO TODO TODO TODO TODO 
      // TODO TODO TODO TODO TODO TODO 
      // TODO TODO TODO TODO TODO TODO 
      // Attention si digestDay est null !!!!
      // TODO TODO TODO TODO TODO TODO 
      // TODO TODO TODO TODO TODO TODO 
      // TODO TODO TODO TODO TODO TODO 
      // TODO TODO TODO TODO TODO TODO 
      // TODO TODO TODO TODO TODO TODO 
      $usersToSendDigestsTo = $userRepository->findByDigestDay($today);

      if ($usersToSendDigestsTo){

        $userCommunityRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:UserCommunity');

        $output->writeln(' # We have to notify ' . count($userCommunityRepository) . ' today');

        //$userCommunityRepository->find
      } else {

        $output->writeln(' # No users to notify today.');

      }

      //sendMails();

    }


    private function getActualDay(){

      return strtolower(date('l'));

    }

}
