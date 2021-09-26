<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\SearchService;

class ArticleController extends AbstractController {
    /**
    * @Route("/article/{id}", name="article", requirements={"id"="\d+"})
    */
    public function show(Article $article) {
        $art_id = $article->getId();
        $articles_recents = $this->getDoctrine()->getRepository(Article::class)->lastTenNoRepeat($art_id);
        if ($article->getTags()->isEmpty()) {
            $tags_empty = true;
        } else {
            $tags_empty = false;
        }
        
        return $this->render('front/article.html.twig', [
            'article' => $article,
            'articles_recents' => $articles_recents,
            'tags_empty' => $tags_empty
        ]);
    }
    
    /**
    * @Route("/articles/{page}", name="articles", requirements={"page"="\d+"})
    */
    public function parcourirArticles($page = 1) {
        $articles = $this->getDoctrine()->getRepository(Article::class)->fetchPage($page);
        $nb_articles = $this->getDoctrine()->getRepository(Article::class)->countArticles()[0];
        $der_page = ceil($nb_articles / 10);
        
        return $this->render('front/articles.html.twig', [
            'articles' => $articles,
            'der_page' => $der_page,
            'page' => $page
        ]);
    }
    
    /**
    * @Route("/recherche/{page}", name="recherche", requirements={"page"="\d+"})
    */
    public function rechercheArticles(SearchService $search_srv, $page = 1): Response {
        $request = Request::createFromGlobals();
        $recherche = $request->query->get('recherche');
        $offset = $page - 1;
        $requete = $search_srv->buildSQLR($recherche);
        dump($requete);
        $articles = $this->getDoctrine()->getRepository(Article::class)->searchArticles($requete, $offset);
        $der_page = 100;//à modif
        
        return $this->render('front/recherche.html.twig', [
            'articles' => $articles,
            'der_page' => $der_page,
            'page' => $page
        ]);
    }
    
    /**
    * @Route("article/random", name="random")
    */
    public function randomArticle(): RedirectResponse {
        $nb_articles = $this->getDoctrine()->getRepository(Article::class)->countArticles()[0];
        $article = false;
        while (!$article or $article == null) {
            $art_id = rand(1, $nb_articles);
            $article = $this->getDoctrine()->getRepository(Article::class)->find($art_id);
        }
        
        return $this->redirectToRoute("article", ['id' => $article->getId()]);
    }
}