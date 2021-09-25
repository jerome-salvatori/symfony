<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;
use App\Entity\Utilisateur;


class UtilisateurFixtures extends Fixture implements FixtureGroupInterface
{
    private $passwordHasher;
    private $faker;
    
    public function __construct(UserPasswordHasherInterface $passwordHasher) {
        $this->passwordHasher = $passwordHasher;
        $this->faker = Factory::create('fr_FR');
    }
    
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < 100; $i++) {
            $user = new Utilisateur;

            $user->setUserName($this->faker->userName());
            $user->setPassword($this->passwordHasher->hashPassword(
                $user,
                $this->faker->password()
            ));

            $user->setRoles(["ROLE_USER"]);
            $user->setAvatar($this->faker->imageUrl(100, 100, null, true));
            $user->setEmail($this->faker->email());
            $manager->persist($user);
        }

        $manager->flush();
    }
    
    public static function getGroups(): array
    {
        return ['users'];
    }
}
