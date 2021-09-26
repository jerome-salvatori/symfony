<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Article;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Service\FileUploader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
* @IsGranted("ROLE_ADMIN")
*/
class AdminPanelController extends AbstractController {
    /**
    * @Route("/admin", name="admin_panel")
    */
    public function adminPanel() {
        return $this->render('admin/admin_panel.html.twig');
    }
    
    /**
    * @Route("/admin/upload", name="admin_upload")
    */
    public function uploadImage(FileUploader $file_uploader) {
        $request = Request::createFromGlobals();
        $image = $request->files->get('image');
        $upload_path = $file_uploader->upload($image);
        if ($upload_path === null) {
            $data = [
                'success' => false
            ];
        } else {
            $data = [
                'success' => true,
                'url' => $upload_path
            ];
        }
        
        return new JsonResponse($data);
    }
    
    /**
    * @Route("/admin/post-article", name="admin_post")
    */
    public function postArticle(UserInterface $user) {
        $article = new Article;
        $article->setAuteur($user);
        $article->setDatePublication(new DateTimezone);
        $article->setDateModif(null);
        $request = Request::createFromGlobals();
        $titre = $request->request->get('titre-art-adm');
        $article->setTitre($titre);
        $article->setContenu($request->request->get('editor_text'));
        $tags = $request->request->get('tags-art-adm');
        $tags = explode(' ', $tags);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            $fetched_tag = $this->getDoctrine()->getRepository(Tag::class)->findOneBy(['nom' => $tag]);
            if ($fetched_tag !== null) {
                $article->addTag($fetched_tag);
            } else {
                $new_tag = new Tag;
                $new_tag->setNom($tag);
                $article->addTag($new_tag);
            }
        }
        $this->getDoctrine()->getManager()->persist($article);
        return $this->render('admin/post_article.html.twig');
    }
}