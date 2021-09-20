<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Utilisateur;


class UtilisateurFixtures extends Fixture
{
    private $passwordHasher;
    
    public function __construct(UserPasswordHasherInterface $passwordHasher) {
        $this->passwordHasher = $passwordHasher;
    }
    
    public function load(ObjectManager $manager)
    {
        $user = new Utilisateur;
        
        $user->setUserName('qdzqdzq');
        $user->setPassword($this->passwordHasher->hashPassword(
            $user,
            'the_new_password'
        ));
        
        $user->setRoles(["ROLE_USER"]);
        
        

        $manager->flush();
    }
}
