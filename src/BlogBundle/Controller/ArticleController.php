<?php

namespace BlogBundle\Controller;

use BlogBundle\Entity\Article;
use BlogBundle\Entity\ArticleCategory;
use BlogBundle\Entity\Comment;
use BlogBundle\Form\ArticleType;
use BlogBundle\Form\CommentType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArticleController extends Controller
{
    const PAGINATION_LIMITS = [2, 5, 10, 20];


    public function indexAction(Request $request)
    {
        $repo = $this->getDoctrine()->getManager()->getRepository(Article::class);

        $currentPageNr = $request->query->getInt('page', 1);
        $currentPageLimit = $request->query->getInt('limit', static::PAGINATION_LIMITS[1]);

        if (!in_array($currentPageLimit, static::PAGINATION_LIMITS)) {
            return $this->redirectToRoute('blog_homepage');
        }

        $totalPages = ceil($repo->countArticles() / $currentPageLimit);

        if ($currentPageNr < 1 || $currentPageNr > $totalPages) {
            return $this->redirectToRoute('blog_homepage');
        }

        $repo = $this->getDoctrine()->getManager()->getRepository(Article::class);
        $langcode = $request->getLocale();

        $articles = $repo->getPaginated($currentPageNr, $currentPageLimit, null, $langcode);

        return $this->render(
            '@Blog/Article/articleList.html.twig',
            [
                'articles' => $articles,
                'currentLimit' => $currentPageLimit,
                'limits' => static::PAGINATION_LIMITS,
                'currentPage' => $currentPageNr,
                'totalPages' => $totalPages,
                'paginationRouteName' => 'blog_homepage',
                'paginationRouteParams' => [],
            ]
        );
    }

    public function detailAction(Request $request, Article $article)
    {
        $comment = new Comment();
        $form = $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $parentId = $form->get('parentId')->getData();
            if ($parentId) {
                $parent = $em->find(Comment::class, $parentId);
                $comment->setParent($parent);
            }
            $user = $request->getUser();
            if ($user) {
                $comment->setUser($user);
            }
            $comment->setArticle($article);

            $em->persist($comment);
            $em->flush();

            $this->addFlash(
                'notice',
                'Your comment was saved!'
            );

            return $this->redirectToRoute('article_detail', ['id' => $article->getId()]);
        }

        return $this->render('@Blog/Article/articleDetail.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    public function createAction(Request $request, Article $article = null)
    {
        $article = $article ?: new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute('article_detail', ['id' => $article->getId()]);
        }

        return $this->render('@Blog/Article/articleCreate.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function deleteAction(Article $article)
    {
        $em = $this->getDoctrine()->getManager();

        $article->setDeleted(true);
        $em->persist($article);
        $em->flush();

        return $this->redirectToRoute('blog_homepage');
    }

}
