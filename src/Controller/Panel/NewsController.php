<?php

namespace App\Controller\Panel;

use App\Entity\News;
use App\Form\NewsForm;
use App\Service\DatatableService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panel/news', name: 'panel_news_')]
class NewsController extends AbstractController
{
    private const COLUMN_MAPPING = [
        0 => 'n.id',
        1 => 'n.title',
        2 => 'n.category',
        3 => 'n.status',
        4 => 'n.createdAt',
    ];

    private const STATUS_BADGES = [
        'draft' => 'bg-secondary',
        'published' => 'bg-success',
        'archived' => 'bg-warning',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DatatableService       $dataTableService
    )
    {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('panel/news/index.html.twig', [
            'page_title' => 'Haberler'
        ]);
    }

    #[Route('/data', name: 'data', methods: ['GET'])]
    public function getNewsData(Request $request): JsonResponse
    {
        $params = $this->dataTableService->extractParameters($request);

        $queryBuilder = $this->createBaseQuery();
        $this->applySearch($queryBuilder, $params['search']);

        $totalRecords = $this->getTotalCount();
        $filteredRecords = $this->getFilteredCount($queryBuilder, $params['search']);

        $this->applyOrderBy($queryBuilder, $params['order']);
        $this->applyPagination($queryBuilder, $params['start'], $params['length']);

        $news = $queryBuilder->getQuery()->getResult();
        $data = $this->formatNewsData($news);

        return new JsonResponse([
            'draw' => $params['draw'],
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function createBaseQuery()
    {
        return $this->entityManager->createQueryBuilder()
            ->select('n')
            ->from(News::class, 'n');
    }

    private function applySearch($queryBuilder, string $search): void
    {
        if (!empty($search)) {
            $queryBuilder
                ->andWhere('n.title LIKE :search OR n.description LIKE :search OR n.category LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
    }

    private function getTotalCount(): int
    {
        return (int)$this->entityManager->createQueryBuilder()
            ->select('COUNT(n.id)')
            ->from(News::class, 'n')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getFilteredCount($queryBuilder, string $search): int
    {
        if (empty($search)) {
            return $this->getTotalCount();
        }

        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder->select('COUNT(n.id)');

        return (int)$countQueryBuilder->getQuery()->getSingleScalarResult();
    }

    private function applyOrderBy($queryBuilder, array $orders): void
    {
        if (!empty($orders)) {
            $order = $orders[0];
            $direction = strtoupper($order['dir']) === 'ASC' ? 'ASC' : 'DESC';
            $columnIndex = (int)$order['column'];

            if (isset(self::COLUMN_MAPPING[$columnIndex])) {
                $queryBuilder->orderBy(self::COLUMN_MAPPING[$columnIndex], $direction);
                return;
            }
        }

        $queryBuilder->orderBy('n.createdAt', 'DESC');
    }

    private function applyPagination($queryBuilder, int $start, int $length): void
    {
        $queryBuilder
            ->setFirstResult($start)
            ->setMaxResults($length);
    }

    private function formatNewsData(array $newsItems): array
    {
        return array_map(function (News $news) {
            $author = $news->getAuthor() ? $news->getAuthor()->getUsername() : '-';
            $createdAt = $news->getCreatedAt()->format('d.m.Y H:i');
            $statusBadge = $this->formatStatus($news->getStatus());

            return [
                'id' => $news->getId(),
                'title' => $news->getTitle(),
                'category' => $news->getCategory() ?: '-',
                'author' => $author,
                'status' => $statusBadge,
                'createdAt' => $createdAt,
                'isPinned' => $news->isPinned() ? '<i class="fas fa-thumbtack text-warning"></i>' : '',
                'actions' => $this->renderView('panel/news/_actions.html.twig', ['news' => $news])
            ];
        }, $newsItems);
    }

    private function formatStatus(string $status): string
    {
        $badgeClass = self::STATUS_BADGES[$status] ?? 'bg-secondary';
        $labels = [
            'draft' => 'Taslak',
            'published' => 'Yayında',
            'archived' => 'Arşivlenmiş'
        ];
        $label = $labels[$status] ?? $status;

        return sprintf('<span class="badge %s">%s</span>', $badgeClass, $label);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $news = new News();

        $news->setAuthor($this->getUser());

        $form = $this->createForm(NewsForm::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->generateUniqueSlug($news);

                $coverImageFile = $form->get('coverImage')->getData();
                if ($coverImageFile) {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/images/news';

                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0755, true);
                    }

                    $newFilename = uniqid() . '.' . $coverImageFile->guessExtension();

                    $coverImageFile->move($uploadsDir, $newFilename);
                    $news->setCoverImage('/uploads/images/news/' . $newFilename);
                }

                if (!$news->getCreatedAt()) {
                    $news->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Istanbul')));
                }

                $news->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Europe/Istanbul')));

                if (!$news->getStatus()) {
                    $news->setStatus('draft');
                }

                $this->entityManager->persist($news);
                $this->entityManager->flush();

                $this->addFlash('success', 'Haber başarıyla oluşturuldu.');
                return $this->redirectToRoute('panel_news_index');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Haber kaydedilirken bir hata oluştu: ' . $e->getMessage());
            }
        } else if ($form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            if (!empty($errors)) {
                $this->addFlash('error', 'Form hatası: ' . implode(', ', $errors));
            }
        }

        return $this->render('panel/news/new.html.twig', [
            'form' => $form,
            'page_title' => 'Yeni Haber Ekle'
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, News $news): Response
    {
        // Store the original author
        $originalAuthor = $news->getAuthor();
        $originalTitle = $news->getTitle();

        $form = $this->createForm(NewsForm::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Restore the original author
            $news->setAuthor($originalAuthor);

            if ($news->getSlug() === null || $originalTitle !== $news->getTitle()) {
                $this->generateUniqueSlug($news);
            }

            // Handle file upload
            $coverImageFile = $form->get('coverImage')->getData();
            if ($coverImageFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/images/news';

                // Create directory if it doesn't exist
                if (!file_exists($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                }

                // Generate a unique name for the file
                $newFilename = uniqid() . '.' . $coverImageFile->guessExtension();

                // Move the file to the directory
                try {
                    $coverImageFile->move(
                        $uploadsDir,
                        $newFilename
                    );

                    // Update the coverImage property to store the image path
                    $news->setCoverImage('/uploads/images/news/' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Dosya yüklenirken bir hata oluştu: ' . $e->getMessage());
                }
            }

            $news->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Europe/Istanbul')));
            $this->entityManager->flush();

            $this->addFlash('success', 'Haber başarıyla güncellendi.');
            return $this->redirectToRoute('panel_news_index');
        }

        return $this->render('panel/news/edit.html.twig', [
            'form' => $form,
            'news' => $news,
            'page_title' => 'Haber Düzenle: ' . $news->getTitle()
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(News $news): Response
    {
        return $this->render('panel/news/show.html.twig', [
            'news' => $news,
            'page_title' => 'Haber Detayı: ' . $news->getTitle()
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST', 'DELETE'])]
    public function delete(News $news): JsonResponse
    {
        try {
            $coverImagePath = $this->getParameter('kernel.project_dir') . '/public' . $news->getCoverImage();
            if (file_exists($coverImagePath)) {
                unlink($coverImagePath);
            }

            $this->entityManager->remove($news);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Haber başarıyla silindi.'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Haber silinirken bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateUniqueSlug(News $news): void
    {
        $slugger = new \Symfony\Component\String\Slugger\AsciiSlugger('tr');
        $originalSlug = $slugger->slug(mb_strtolower($news->getTitle()))->toString();
        $slug = $originalSlug;

        $counter = 1;

        while ($this->slugExists($slug, $news->getId())) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $news->setSlug($slug);
    }

    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(n.id)')
            ->from(News::class, 'n')
            ->where('n.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $qb->andWhere('n.id != :id')
                ->setParameter('id', $excludeId);
        }

        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }
}
