<?php

namespace App\Controller\Panel;

use App\Entity\User;
use App\Form\UserForm;
use App\Service\DatatableService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panel/users', name: 'panel_users_')]
#[IsGranted('ROLE_ADMIN', message: 'Bu sayfaya erişim yetkiniz yok.')]
#[IsGranted('ROLE_DEVELOPER', message: 'Bu sayfaya erişim yetkiniz yok.')]
class UserController extends AbstractController
{
    private const COLUMN_MAPPING = [
        0 => 'u.id',
        1 => 'u.username',
    ];

    private const ROLE_BADGES = [
        'ROLE_SUPER_ADMIN' => 'bg-dark',
        'ROLE_ADMIN' => 'bg-danger',
        'ROLE_EDITOR' => 'bg-warning',
        'ROLE_USER' => 'bg-info',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DatatableService $dataTableService,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('panel/users/index.html.twig');
    }

    #[Route('/data', name: 'data', methods: ['GET'])]
    public function getUserData(Request $request): JsonResponse
    {
        $params = $this->dataTableService->extractParameters($request);

        $queryBuilder = $this->createBaseQuery();
        $this->applySearch($queryBuilder, $params['search']);

        $totalRecords = $this->getTotalCount();
        $filteredRecords = $this->getFilteredCount($queryBuilder, $params['search']);

        $this->applyOrderBy($queryBuilder, $params['order']);
        $this->applyPagination($queryBuilder, $params['start'], $params['length']);

        $users = $queryBuilder->getQuery()->getResult();
        $data = $this->formatUserData($users);

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
            ->select('u')
            ->from(User::class, 'u');
    }

    private function applySearch($queryBuilder, string $search): void
    {
        if (!empty($search)) {
            $queryBuilder
                ->andWhere('u.username LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
    }

    private function getTotalCount(): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getFilteredCount($queryBuilder, string $search): int
    {
        if (empty($search)) {
            return $this->getTotalCount();
        }

        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder->select('COUNT(u.id)');

        return (int) $countQueryBuilder->getQuery()->getSingleScalarResult();
    }

    private function applyOrderBy($queryBuilder, array $orders): void
    {
        if (!empty($orders)) {
            $order = $orders[0];
            $direction = strtoupper($order['dir']) === 'ASC' ? 'ASC' : 'DESC';
            $columnIndex = (int) $order['column'];

            if (isset(self::COLUMN_MAPPING[$columnIndex])) {
                $queryBuilder->orderBy(self::COLUMN_MAPPING[$columnIndex], $direction);
                return;
            }
        }

        $queryBuilder->orderBy('u.id', 'DESC');
    }

    private function applyPagination($queryBuilder, int $start, int $length): void
    {
        $queryBuilder
            ->setFirstResult($start)
            ->setMaxResults($length);
    }

    private function formatUserData(array $users): array
    {
        return array_map(fn(User $user) => [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'roles' => $this->formatRoles($user->getRoles()),
            'actions' => $this->renderView('panel/users/_actions.html.twig', ['user' => $user])
        ], $users);
    }

    private function formatRoles(array $roles): string
    {
        $roleHierarchy = [
            'ROLE_DEVELOPER',
            'ROLE_ADMIN',
            'ROLE_MODERATOR',
            'ROLE_HEAD_ARCHITECT',
            'ROLE_HEAD_EDITOR',
            'ROLE_ARCHITECT',
            'ROLE_EDITOR',
            'ROLE_MOBINATOR',
            'ROLE_GRAPHICER',
            'ROLE_HEAD_IN_GAME_HELPER',
            'ROLE_IN_GAME_HELPER',
            'ROLE_USER',
        ];

        $highestRole = 'ROLE_DEVELOPER';
        foreach ($roleHierarchy as $role) {
            if (in_array($role, $roles)) {
                $highestRole = $role;
                break;
            }
        }

        $badgeClass = self::ROLE_BADGES[$highestRole] ?? 'bg-secondary';
        $label = str_replace('ROLE_', '', $highestRole);

        return sprintf('<span class="badge %s">%s</span>', $badgeClass, $label);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$user === $this->getUser()) {
                $isBlocked = $request->request->has('isBlocked');
                $user->setBlocked($isBlocked);

                $isActive = $request->request->has('isActive');
                $user->setActive($isActive);
            }

            $plainPanelPassword = $form->get('plainPanelPassword')->getData();
            if ($plainPanelPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPanelPassword);
                $user->setPanelPassword($hashedPassword);
            }

            $roles = $form->get('roles')->getData();
            if (is_array($roles)) {
                $normalizedRoles = array_values(array_filter($roles));
                $user->setRoles($normalizedRoles);
            }

            $user->setUsername($user->getUsername());
            $this->entityManager->flush();

            $this->addFlash('success', 'Kullanıcı başarıyla güncellendi.');
            return $this->redirectToRoute('panel_users_index');
        }

        return $this->render('panel/users/edit.html.twig', [
            'form' => $form,
            'user' => $user,
            'is_edit' => true,
            'page_title' => 'Kullanıcı Düzenle: ' . $user->getUsername()
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $roles = $user->getRoles();
        $roles = array_filter($roles, fn($role) => $role !== 'ROLE_USER');

        return $this->render('panel/users/show.html.twig', [
            'user' => $user,
            'roles' => $roles,
            'page_title' => 'Kullanıcı Detayı: ' . $user->getUsername()
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST', 'DELETE'])]
    public function delete(User $user): JsonResponse
    {
        try {
            if ($user === $this->getUser()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Kendi hesabınızı silemezsiniz.'
                ], 400);
            }

            $user->setActive(false);
            $user->setDeleted(true);
            $user->setPanelPassword(null);
            $user->setRoles(['ROLE_USER']);

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Kullanıcı başarıyla silindi.'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Kullanıcı silinirken bir hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }
}
