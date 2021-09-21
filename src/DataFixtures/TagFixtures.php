<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Tag;
use Faker\Factory;


class TagFixtures extends Fixture implements FixtureGroupInterface
{
    private $faker;
    
    public function __construct() {
        $this->faker = Factory::create('fr_FR');
    }
    
    public function load(ObjectManager $manager)
    {
        
        
        $tags_array = [];
        for ($i = 0; $i < 100; $i++) {
            $tag = new Tag;
            $tag->setNom($this->faker->word());
            $tags_array[$i] = $tag;
            $manager->persist($tags_array[$i]);
        }
        
        

        $manager->flush();
    }
    
        
    public static function getGroups(): array
    {
        return ['tags'];
    }
}
