<?php

namespace App\Controller\App;

use App\Entity\Comment;
use App\Entity\News;
use App\Filter\ProfanityFilter;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
class CommentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private CommentRepository $commentRepository;
    private ProfanityFilter $profanityFilter;

    public function __construct(EntityManagerInterface $entityManager, CommentRepository $commentRepository, ProfanityFilter $profanityFilter)
    {
        $this->entityManager = $entityManager;
        $this->commentRepository = $commentRepository;
        $this->profanityFilter = $profanityFilter;
    }

    #[Route('/add/{id}', name: 'app_news_comment_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(Request $request, News $news): Response
    {
        // Yorumların devre dışı olup olmadığını kontrol et
        if (!$news->isCommentsEnabled()) {
            $this->addFlash('error', 'Bu habere yorum yapma özelliği kapatılmıştır.');
            return $this->redirectToRoute('app_news_show', ['slug' => $news->getSlug()]);
        }

        $content = $request->request->get('content');

        if (empty($content)) {
            $this->addFlash('error', 'Yorum içeriği boş olamaz.');
            return $this->redirectToRoute('app_news_show', ['slug' => $news->getSlug()]);
        }

        if ($this->profanityFilter->hasProfanity($content)) {
            $this->addFlash('error', 'Yorumunuz uygunsuz ifadeler içeriyor ve kaydedilemedi.');
            return $this->redirectToRoute('app_news_show', ['slug' => $news->getSlug()]);
        }

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setUser($this->getUser());
        $comment->setNews($news);

        // Admin kullanıcılar için yorumu otomatik onayla
        if ($this->isGranted('ROLE_ADMIN')) {
            $comment->setIsApproved(true);
        }

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('success', 'Yorumunuz başarıyla eklendi.');
        } else {
            $this->addFlash('info', 'Yorumunuz onay bekliyor. Onaylandıktan sonra görüntülenecektir.');
        }

        return $this->redirectToRoute('app_news_show', ['slug' => $news->getSlug()]);
    }

    #[Route('/delete/{id}', name: 'app_news_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment): Response
    {
        // CSRF token kontrolü
        if (!$this->isCsrfTokenValid('delete-comment-' . $comment->getId(), $request->request->get('_token'))) {
            return $this->json(['message' => 'Geçersiz CSRF token'], Response::HTTP_BAD_REQUEST);
        }

        // Yorum sahibi veya admin yetkisi kontrolü
        if ($comment->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['message' => 'Bu yorumu silme yetkiniz yok.'], Response::HTTP_FORBIDDEN);
        }

        $newsSlug = $comment->getNews()->getSlug();

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Yorum başarıyla silindi.');

        return $this->redirectToRoute('app_news_show', ['slug' => $newsSlug]);
    }

    #[Route('/approve/{id}', name: 'app_news_comment_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Comment $comment): Response
    {
        $comment->setIsApproved(true);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Yorum onaylandı.'
        ]);
    }

    #[Route('/reject/{id}', name: 'app_news_comment_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Comment $comment): Response
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Yorum reddedildi ve silindi.'
        ]);
    }

    #[Route('/admin/pending', name: 'app_admin_comments_pending')]
    #[IsGranted('ROLE_ADMIN')]
    public function pendingComments(): Response
    {
        $pendingComments = $this->commentRepository->findPendingComments();

        return $this->render('admin/comments/pending.html.twig', [
            'pendingComments' => $pendingComments
        ]);
    }
}
