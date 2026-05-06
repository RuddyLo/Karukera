<?php

namespace App\EventSubscriber;

use App\Repository\SocialMediaRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class SocialMediaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SocialMediaRepository $socialMediaRepository,
        private Environment $twig,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->twig->addGlobal('social_medias', $this->socialMediaRepository->findAll());
    }
}
