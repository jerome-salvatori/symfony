<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
//use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Utilisateur;
use App\Entity\Article;
use App\Entity\Tag;
use Faker\Factory;


class ArticleFixtures extends Fixture implements FixtureGroupInterface
{
    private $faker;
    
    public function __construct() {
        $this->faker = Factory::create('fr_FR');
    }
    
    public function load(ObjectManager $manager)
    {
        
        //$entity_manager = new EntityManagerInterface;
        $user_ids = $manager->getRepository(Utilisateur::class)->findAllUserIds();
        $uid_max_key = count($user_ids) - 1;
        $tags = $manager->getRepository(Tag::class)->findAll();
        $nb_tags = count($tags);
        $tags_max_key = $nb_tags - 1;
        
        for ($i = 0; $i < 100; $i++) {
            $article = new Article;
            $auteur_key = rand(0, $uid_max_key);
            $auteur_id = $user_ids[$auteur_key]['id'];
            $auteur = $manager->getRepository(Utilisateur::class)->find($auteur_id);
            $article->setAuteur($auteur);
            $date_pub = new \DateTime('now', new \DateTimezone('Europe/Paris'));
            //$dpub_str = $date_pub->format('Y-m-d H:i:s');
            $article->setDatePublication($date_pub);
            $modif = rand(0, 1);
            if ($modif == 1) {
                $date_modif = new \DateTime('now +1 hour', new \DateTimezone('Europe/Paris'));
                //$dmod_str = $date_modif->format('Y-m-d H:i:s');
                $article->setDateModif($date_modif);
            } else {
                $article->setDateModif(null);
            }
            $article->setTitre($this->faker->sentence(3));
            $article->setContenu($this->createContent());
            
            $nb_art_tags = rand(1, 10);
            for ($ii = $nb_art_tags; $ii > 0; $ii--) {
                $idx_tag = rand(0, $tags_max_key);
                $tag = $tags[$idx_tag];
                if (!$article->getTags()->contains($tag)){
                    $article->addTag($tag);
                } else {
                    $ii++;
                }
            }
            $manager->persist($article);
        }
        

        $manager->flush();
    }
    
    private function createContent() {
        $p1 = $this->faker->paragraph();
        $image = $this->faker->imageUrl(1900, 1000, null, true);
        $alt = $this->faker->words(2, true);
        $caption = $this->faker->sentence(8);
        $p2 = $this->faker->paragraph();
        
        $content = "
            <p>$p1</p>
            <figure>
                <img src=\"$image\" alt=\"$alt\">
                <figcaption>$caption</figcaption>
            </figure>
            <p>$p2</p>
            ";
        
        return $content;
    }
        
    public static function getGroups(): array
    {
        return ['articles'];
    }
}
