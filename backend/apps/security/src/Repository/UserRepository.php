<?php

namespace App\apps\security\Repository;

use Doctrine\Persistence\ManagerRegistry;
use App\apps\security\Entity\User;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends DoctrineEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends DoctrineEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $queryBuilder = $this->allQuery();
        $filterService->apply($queryBuilder);

        return $this->paginator($queryBuilder);
    }

    public function downloadAndFilter(FilterService $filterService): iterable
    {
        $queryBuilder = $this->allQuery()
            ->select($this->entityAlias.'.username')
            ->addSelect($this->entityAlias.'.fullName')
            ->addSelect($this->entityAlias.'.isActive');

        $filterService->apply($queryBuilder);

        return $queryBuilder->getQuery()->getResult();
    }

    public function usernameExists(string $username): bool
    {
        return null !== $this->createQueryBuilder('user')
                ->select('1')
                ->where('user.username = :username')
                ->setParameter('username', $username)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
    }

    public function rolesOfUser(string $userId): array
    {
        $result = $this->createQueryBuilder('user')
            ->select('userRole.uuid')
            ->join('user.rol', 'userRole')
            ->where('user.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'uuid');
    }
}
