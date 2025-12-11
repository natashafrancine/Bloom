<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogger
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function log(User $user, string $action, ?string $description = null): void
    {
        $log = new ActivityLog();
        $log->setUser($user);
        $log->setAction($action);
        $log->setDescription($description);

        $this->em->persist($log);
        $this->em->flush();
    }
}