<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Service\Slugify;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/article")
 * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_AUTHOR')")
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("/", name="article_index", methods={"GET"})
     * @param ArticleRepository $articleRepository
     * @return Response
     */
    public function findArticlesWithTags(ArticleRepository $articleRepository): Response
    {
        return $this->render('article/index.html.twig', [
            'articles' => $articleRepository->findAllWithCategoriesAndTags(),
        ]);
    }


    /**
     * @Route("/new", name="article_new", methods={"GET","POST"})
     * @param Request $request
     * @param Slugify $slugify
     * @param \Swift_Mailer $mailer
     * @return Response
     */

    public function new(Request $request, Slugify $slugify, \Swift_Mailer $mailer): Response
    {

        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $article->setSlug($slugify->generate($article->getTitle()));
            $article = $article->setTitle($article->getSlug());
            $author = $this->getUser();
            $article->setAuthor($author);
            $entityManager->persist($article);
            $entityManager->flush();

            $message = (new \Swift_Message('Un nouvel article vient d\'être publié !'))
                ->setFrom($this->getParameter('mailer_from'))
                ->setTo($this->getParameter('mailer_from'))
                ->setBody($this->renderView('article/email/notification.html.twig', ['article' => $article ]

                ), 'text/html');

                $mailer->send($message);
            $this->addFlash('success', 'Nouvel article enregistré');

            return $this->redirectToRoute('article_index');
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="article_show", methods={"GET"})
     * @param Article $article
     * @return Response
     */

    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="article_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Article $article
     * @param Slugify $slugify
     * @return Response
     */
    public function edit(Request $request, Article $article, Slugify $slugify): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        if ($this->isGranted('ROLE_ADMIN') or ($article->getAuthor() === $this->getUser())) {

            if ($form->isSubmitted() && $form->isValid()) {
                $article->setSlug($slugify->generate($article->getTitle()));
                $article = $article->setTitle($article->getSlug());
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Edition enregistrée');


                return $this->redirectToRoute('article_index', [
                    'id' => $article->getId(),
                ]);
            }
        } else {
            throw $this->createAccessDeniedException();

        }


        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="article_delete", methods={"DELETE"})
     * @param Request $request
     * @param Article $article
     * @return Response
     */
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($article);
            $entityManager->flush();

            $this->addFlash('danger', 'Article supprimé');

        }

        return $this->redirectToRoute('article_index');
    }
}
