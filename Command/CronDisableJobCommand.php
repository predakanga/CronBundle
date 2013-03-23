<?php
namespace ColourStream\Bundle\CronBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use ColourStream\Bundle\CronBundle\Entity\CronJobResult;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CronDisableJobCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName("cron:disable-job")
             ->setDescription("Disables a cron job")
             ->addArgument("job", InputArgument::REQUIRED, "Name of the job to disable");
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobName = $input->getArgument('job');
        $em = $this->getContainer()->get("doctrine.orm.entity_manager");
        $jobRepo = $em->getRepository('ColourStreamCronBundle:CronJob');
        
        $job = $jobRepo->findOneByCommand($jobName);
        if (!$job) {
            $output->writeln("Couldn't find a job by the name of " . $jobName);

            return CronJobResult::FAILED;
        }
        
        $job->setEnabled(false);
        $em->flush();
        
        $output->writeln("Disabled cron job by the name of " . $jobName);
    }
}
