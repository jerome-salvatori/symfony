<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;

class IndexController extends AbstractController {
    /**
    * @Route("/", name="index")
    */
    public function index(): Response {
        $articles = $this->getDoctrine()->getRepository(Article::class)->findLastTen();
        
        return $this->render('front/index.html.twig', [
            "articles" => $articles
        ]);
    }
}