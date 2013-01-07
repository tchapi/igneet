<?php

namespace meta\GeneralBundle\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;

class HistoryListener
{
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() AS $entity) {

        }

        foreach ($uow->getScheduledEntityUpdates() AS $entity) {

        }

        foreach ($uow->getScheduledEntityDeletions() AS $entity) {

        }

        foreach ($uow->getScheduledCollectionDeletions() AS $col) {

        }

        foreach ($uow->getScheduledCollectionUpdates() AS $col) {

        }
    }
}