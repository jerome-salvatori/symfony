<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\Entity\Article;
use App\Entity\Commentaire;
use App\Entity\Utilisateur;


class CommentaireFixtures extends Fixture implements FixtureGroupInterface
{
    private $faker;
    
    public function __construct() {
        $this->faker = Factory::create('fr_FR');
    }
    
    public function load(ObjectManager $manager)
    {
        
        $utilisateurs = $manager->getRepository(Utilisateur::class)->findAll();
        $nb_users = count($utilisateurs);
        $max_idx_users = $nb_users - 1;
        $articles = $manager->getRepository(Article::class)->findAll();
        $nb_arts = count($articles);
        $max_idx_arts = $nb_arts - 1;
        $commentaires = $manager->getRepository(Commentaire::class)->findAll();
        $nb_coms = count($commentaires);
        $max_idx_coms = $nb_coms - 1;
        
        for ($i = 0; $i < 100; $i++) {
            $com = new Commentaire;
            $idx_aut = rand(0, $max_idx_users);
            $auteur = $utilisateurs[$idx_aut];
            $com->setAuteur($auteur);
            $com->setContenu($this->faker->paragraph());
            $date_pub = new \DateTime('now', new \DateTimezone('Europe/Paris'));
            $com->setDatePublication($date_pub);
            $coin_flip = rand (0, 1);
            if ($coin_flip == 1 && $nb_coms > 0) {
                $reponse = false;
                while (!$reponse || $reponse->getProfondeur() > 1) {
                    $reponse_idx = rand(0, $max_idx_coms);
                    $reponse = $commentaires[$reponse_idx];
                }
                $profondeur = $reponse->getProfondeur() + 1;
                $com->setReponse($reponse);
                $com->setProfondeur($profondeur);
                $com->setArticle($reponse->getArticle());
            } else {
                $com->setReponse(null);
                $com->setProfondeur(0);
                $idx_art = rand(0, $max_idx_arts);
                $article = $articles[$idx_art];
                $com->setArticle($article);
            }
            $com->setLikes(0);
            
            $manager->persist($com);
        }

        $manager->flush();
    }
    
    public static function getGroups(): array
    {
        return ['commentaires'];
    }
}
