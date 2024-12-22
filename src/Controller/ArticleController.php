<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(): Response
    {
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }

    #[Route('/article/creer', name: 'app_article_creer')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imageDirectory
    ): Response
    {
        $article = new Article();

        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();

            if($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move($imageDirectory, $newFilename);
                } catch (FileException $e) {
                    print($e->getMessage() . "\n line : ".$e->getLine() . "\n file : ".$e->getFile());
                }
                var_dump($newFilename);
                $article->setImage($newFilename);
            }

            $entityManager->persist($article);
            $entityManager->flush();
            $this->addFlash('success', 'Article Créer');
        }

        return $this->render('article/creer.html.twig', [
            'controller_name' => 'ArticleController',
            'titre' => 'Créer un article',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/article/modifier/{id}', name: 'app_article_modifier')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException('L\'article n\'a pas été trouvé');
        }

        // Créer le formulaire avec ArticleType
        $form = $this->createForm(ArticleType::class, $article);

        // Traiter la requête du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder les modifications
            $entityManager->flush();

            // Ajouter un message flash
            $this->addFlash('success', 'Article modifié avec succès !');
        }

        return $this->render('article/modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/article/supprimer/{id}', name: 'app_article_supprimer')]
    public function delete(int $id, EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException('L\'article n\'a pas été trouvé');
        }

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success', 'Article supprimé avec succès !');

        return $this->redirectToRoute('app_article_liste');
    }

    #[Route('/article/liste', name: 'app_article_liste')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findAll();

        return $this->render('article/liste.html.twig', [
            'articles' => $articles,
        ]);
    }
}
