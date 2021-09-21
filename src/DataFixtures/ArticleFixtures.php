<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Article;
use Faker\Factory;


class ArticleFixtures extends Fixture implements FixtureGroupInterface
{
    private $faker;
    
    public function __construct() {
        $this->faker = Factory::create('fr_FR');
    }
    
    public function load(ObjectManager $manager)
    {
        
        for ($i = 0; $i < 100; $i++) {
            $article = new Article;
            $article->setAuteur();
        }
        

        $manager->flush();
    }
    
        
    public static function getGroups(): array
    {
        return ['articles'];
    }
}
