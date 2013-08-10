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
      $sendBiMonthlyEmails = $this->isEvenWeek();
      $sendDefaultDayEmails = $this->isDefaultDay();

      $output->writeln('Today is : ' . $today.'.');

      if ($sendBiMonthlyEmails){
        $output->writeln(' # This week we are sending bi-monthly emails.');
      }

      if ($sendDefaultDayEmails){
        $output->writeln(" # It's the default day");
      }

      // List all users to whom we need to send a digest today
      $userRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:User');
      $usersToSendDigestsTo = $userRepository->findUsersWhoNeedDigestOnDay($today, $sendBiMonthlyEmails, $sendDefaultDayEmails);

      // Get the communities, and then the emails
      if ($usersToSendDigestsTo){

        $output->writeln(' # We have to notify ' . count($usersToSendDigestsTo) . ' user(s) today');

        foreach ($usersToSendDigestsTo as $user) {
          $output->writeln("   - " . $user->getFullName());
        }

        $userCommunityRepository = $this->getContainer()->get('doctrine')->getRepository('metaUserBundle:UserCommunity');

      } else {

        $output->writeln(' # No users to notify today.');

      }

      // sendMails();

    }


    private function getActualDay(){

      return strtolower(date('l'));

    }

    private function isEvenWeek(){

      return (intval(date('W')) % 2 == 0);

    }

    private function isDefaultDay(){

      $defaultDay = $this->getContainer()->getParameter('digest.day');
      return ($this->getActualDay() == $defaultDay);

    }
}
