<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppAdminFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->createUser($manager);
        $this->createOasisKarurioAdmin($manager);

        $manager->flush();
    }

    private function createUser(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail("ratianarivoruddy@gmail.com");
        $user->setRoles(['ROLE_ADMIN']);
        $plaintextPassword = "karukera";


        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);
        $manager->persist($user);
    }

    private function createOasisKarurioAdmin(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail("contact@oasiskarurio.com");
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsVerified(true);
        $plaintextPassword = "oasiskarurio2026!\$";

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);
        $manager->persist($user);
    }
}
