<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Repository\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogger
{
    private EntityManagerInterface $em;
    private ActivityLogRepository $activityLogRepository;

    public function __construct(EntityManagerInterface $em, ActivityLogRepository $activityLogRepository)
    {
        $this->em = $em;
        $this->activityLogRepository = $activityLogRepository;
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