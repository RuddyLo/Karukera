<?php

namespace App\Twig;

use App\Repository\SocialMediaRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SocialMediaExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private SocialMediaRepository $socialMediaRepository)
    {
    }

    public function getGlobals(): array
    {
        return [
            'social_medias' => $this->socialMediaRepository->findAll(),
        ];
    }
}
