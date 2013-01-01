<?php
namespace ColourStream\Bundle\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class CronBaseCommand extends ContainerAwareCommand
{
    /**
     * @return Doctrine\ORM\EntityManager
     */
    protected function getEntityManger()
    {
        return $this->getContainer()->get('doctrine')
                ->getManager($this->getContainer()
                                  ->getParameter('colour_stream_cron.entity_manager'));
    }
}
