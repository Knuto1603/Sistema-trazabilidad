# Backend Patterns - Guia para crear nuevas entidades

## Checklist para nueva entidad

Para cada nueva entidad se necesitan estos archivos (ejemplo: `Lote`):

```
apps/core/src/
├── Entity/Lote.php                          # Entidad Doctrine
├── Repository/LoteRepository.php            # Repositorio
├── Controller/LoteApi.php                   # Controlador API
└── Service/Lote/
    ├── CreateLoteService.php                # Crear
    ├── UpdateLoteService.php                # Actualizar
    ├── GetLoteService.php                   # Obtener uno
    ├── GetLotesService.php                  # Listar con paginacion
    ├── DeleteLoteService.php                # Eliminar
    ├── ChangeStateLoteService.php           # Habilitar/deshabilitar
    ├── GetSharedLoteService.php             # Para selects/dropdowns (opcional)
    ├── DownloadLotesService.php             # Exportar (opcional)
    ├── Dto/
    │   ├── LoteDto.php                      # DTO con validaciones
    │   ├── LoteDtoTransformer.php           # Entity → DTO
    │   └── LoteFactory.php                  # DTO → Entity
    └── Filter/
        └── LoteFilterDto.php                # Filtro avanzado (opcional)
```

---

## 1. ENTITY PATTERN

```php
<?php
namespace App\apps\core\Entity;

use App\apps\core\Repository\LoteRepository;
use App\shared\Entity\EntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoteRepository::class)]
#[ORM\Table(name: 'core_lote')]
#[ORM\HasLifecycleCallbacks]
class Lote implements \Stringable
{
    use EntityTrait;  // uuid, createdAt, updatedAt, isActive

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    // ManyToOne (owning side)
    #[ORM\ManyToOne(targetEntity: Campahna::class)]
    #[ORM\JoinColumn(name: 'campahna_id', nullable: false)]
    private ?Campahna $campahna = null;

    // OneToMany (inverse side) - SIEMPRE con cascade
    #[ORM\OneToMany(targetEntity: OtraEntidad::class, mappedBy: 'lote', cascade: ['persist', 'remove'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();  // SIEMPRE inicializar collections
    }

    public function __toString(): string
    {
        return $this->getNombre() ?? '';
    }

    // Getters retornan tipo nullable
    public function getId(): ?int { return $this->id; }
    public function getNombre(): ?string { return $this->nombre; }

    // Setters retornan static (fluent interface)
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }
}
```

### Tipos de relaciones usados:

**ManyToOne (owning side):**
```php
#[ORM\ManyToOne(targetEntity: Fruta::class)]
#[ORM\JoinColumn(name: 'fruta_id', nullable: false)]
private ?Fruta $fruta = null;
```

**OneToMany (inverse side):**
```php
#[ORM\OneToMany(targetEntity: ProductorCampahna::class, mappedBy: 'campahna', cascade: ['persist', 'remove'])]
private Collection $productorCampahnas;
```

**ManyToMany (owning side - como User→UserRole):**
```php
#[ORM\ManyToMany(targetEntity: UserRole::class, inversedBy: 'users')]
private Collection $rol;
```

**Junction entity (como ProductorCampahna):**
```php
#[ORM\UniqueConstraint(name: 'unique_productor_campahna', columns: ['productor_id', 'campahna_id'])]
// Dos ManyToOne, ambos owning side
#[ORM\ManyToOne(targetEntity: Productor::class, inversedBy: 'campahnas')]
#[ORM\JoinColumn(name: 'productor_id', referencedColumnName: 'id', nullable: false)]
private ?Productor $productor = null;
```

**Self-referencing (como Parametro):**
```php
#[ORM\ManyToOne(targetEntity: self::class)]
private ?self $parent = null;
```

---

## 2. REPOSITORY PATTERN

```php
<?php
namespace App\apps\core\Repository;

use App\apps\core\Entity\Lote;
use App\shared\Doctrine\DoctrineEntityRepository;
use App\shared\Repository\PaginatorInterface;
use App\shared\Service\FilterService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends DoctrineEntityRepository<Lote>
 */
class LoteRepository extends DoctrineEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lote::class);
    }

    // OBLIGATORIO: query base con eager loading de relaciones
    public function allQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('lote')
            ->select(['lote', 'campahna'])
            ->leftJoin('lote.campahna', 'campahna');
    }

    public function paginateAndFilter(FilterService $filterService): PaginatorInterface
    {
        $qb = $this->allQuery();
        $filterService->apply($qb);
        return $this->paginator($qb);
    }

    // Para selects/dropdowns
    public function allActive(): array
    {
        return $this->allQuery()
            ->select('lote.uuid as id')
            ->addSelect('lote.nombre as nombre')
            ->where('lote.isActive = true')
            ->orderBy('lote.nombre', 'asc')
            ->getQuery()->getResult();
    }
}
```

**Base class provee:** `save()`, `remove()`, `ofId(string $uuid, bool $strict)`, `all()`, `paginator()`

---

## 3. DTO PATTERN

```php
<?php
namespace App\apps\core\Service\Lote\Dto;

use App\shared\Service\Dto\DtoRequestInterface;
use App\shared\Service\Dto\DtoTrait;
use App\shared\Validator\Uid;
use Symfony\Component\Validator\Constraints as Assert;

final class LoteDto implements DtoRequestInterface
{
    use DtoTrait;  // agrega: $id (uuid string), $isActive

    public function __construct(
        // Campos de INPUT con validacion
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public ?string $nombre = null,

        #[Assert\NotBlank]
        #[Uid]  // Validador custom para UUIDs
        public ?string $campahnaId = null,

        #[Assert\Date]
        public ?string $fecha = null,  // Fechas son STRINGS ISO 8601

        // Campos de OUTPUT (sin validacion, los llena el transformer)
        public ?string $campahnaNombre = null,
    ) {}
}
```

**Convenciones:**
- Relaciones: solo UUID string, nunca objetos
- Fechas: strings formato 'Y-m-d'
- Todos los campos public con default null
- `final class`

---

## 4. FACTORY PATTERN (DTO → Entity)

```php
<?php
namespace App\apps\core\Service\Lote\Dto;

use App\apps\core\Entity\Lote;
use App\apps\core\Repository\CampahnaRepository;

final readonly class LoteFactory
{
    public function __construct(
        private CampahnaRepository $campahnaRepository,
    ) {}

    // CREATE
    public function ofDto(LoteDto $dto): Lote
    {
        $lote = new Lote();
        $this->updateOfDto($dto, $lote);
        return $lote;
    }

    // UPDATE (misma logica, reutilizada)
    public function updateOfDto(LoteDto $dto, Lote $lote): void
    {
        $lote->setNombre($dto->nombre);

        if ($dto->campahnaId) {
            $campahna = $this->campahnaRepository->ofId($dto->campahnaId);
            $lote->setCampahna($campahna);
        }

        if ($dto->fecha) {
            $lote->setFecha(new \DateTime($dto->fecha));
        }
    }
}
```

---

## 5. TRANSFORMER PATTERN (Entity → DTO)

```php
<?php
namespace App\apps\core\Service\Lote\Dto;

use App\shared\Doctrine\UidType;
use App\shared\Service\Transformer\DtoTransformer;

final class LoteDtoTransformer extends DtoTransformer
{
    public function fromObject(mixed $object): ?LoteDto
    {
        if (null === $object) return null;

        $dto = new LoteDto();
        $dto->nombre = $object->getNombre();
        $dto->campahnaId = UidType::toString($object->getCampahna()?->uuid());
        $dto->campahnaNombre = $object->getCampahna()?->getNombre();
        $dto->fecha = $object->getFecha()?->format('Y-m-d');

        $dto->ofEntity($object);  // SIEMPRE llamar - setea id y isActive

        return $dto;
    }
}
```

---

## 6. SERVICE PATTERNS

**CreateService:**
```php
final readonly class CreateLoteService
{
    public function __construct(
        private LoteRepository $repository,
        private LoteFactory $factory,
    ) {}

    public function execute(LoteDto $dto): Lote
    {
        $this->isValid($dto);
        $entity = $this->factory->ofDto($dto);
        $this->repository->save($entity);
        return $entity;
    }

    public function isValid(LoteDto $dto): void
    {
        if (null === $dto->nombre) throw new MissingParameterException('Missing parameter nombre');
    }
}
```

**GetListService (con paginacion):**
```php
readonly class GetLotesService
{
    public function __construct(
        protected LoteRepository $repository,
        protected FilterService $filterService,
        protected LoteDtoTransformer $dtoTransformer,
    ) {}

    public function execute(FilterDto $filterDto): array
    {
        $this->filterService->addFilter(new PaginationFilter($filterDto->page, $filterDto->itemsPerPage));
        $this->filterService->addFilter(new SearchTextFilter($filterDto->search, ['lote.nombre']));

        $sorting = SortingDto::create($filterDto->sort, $filterDto->direction);
        $this->filterService->addSorting(new SortByRequestField($sorting, [
            'nombre' => 'lote.nombre',
            'createdAt' => 'lote.createdAt',
        ]));

        $paginator = $this->repository->paginateAndFilter($this->filterService);
        $items = $this->dtoTransformer->fromObjects($paginator->getIterator());

        return ['items' => $items, 'pagination' => $paginator->pagination()];
    }
}
```

**ChangeStateService:**
```php
final readonly class ChangeStateLoteService
{
    public function __construct(private LoteRepository $repository) {}

    public function execute(string $id, bool $state): Lote
    {
        $entity = $this->repository->ofId($id, true);
        $state ? $entity->enable() : $entity->disable();
        $this->repository->save($entity);
        return $entity;
    }
}
```

---

## 7. CONTROLLER PATTERN

```php
<?php
namespace App\apps\core\Controller;

use App\shared\Api\AbstractSerializerApi;
use App\shared\Doctrine\UidType;
use App\shared\Service\Dto\FilterDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lotes')]
final class LoteApi extends AbstractSerializerApi
{
    #[Route('/', name: 'lote_list', methods: ['GET'])]
    public function list(#[MapQueryString] FilterDto $filterDto, GetLotesService $service): Response
    {
        return $this->ok($service->execute($filterDto));
    }

    #[Route('/', name: 'lote_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] LoteDto $dto, CreateLoteService $service, LoteDtoTransformer $t): Response
    {
        return $this->ok(['message' => 'Lote creado exitosamente', 'item' => $t->fromObject($service->execute($dto))]);
    }

    #[Route('/{id}', name: 'lote_view', requirements: ['id' => UidType::REGEX], methods: ['GET'])]
    public function view(string $id, GetLoteService $service, LoteDtoTransformer $t): Response
    {
        return $this->ok(['message' => 'Lote obtenido', 'item' => $t->fromObject($service->execute($id, true))]);
    }

    #[Route('/{id}', name: 'lote_update', requirements: ['id' => UidType::REGEX], methods: ['PUT'])]
    public function update(#[MapRequestPayload] LoteDto $dto, string $id, UpdateLoteService $s, LoteDtoTransformer $t): Response
    {
        return $this->ok(['message' => 'Lote actualizado', 'item' => $t->fromObject($s->execute($id, $dto))]);
    }

    #[Route('/{id}/enable', name: 'lote_enable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function enable(string $id, ChangeStateLoteService $s, LoteDtoTransformer $t): Response
    {
        return $this->ok(['message' => 'Lote habilitado', 'item' => $t->fromObject($s->execute($id, true))]);
    }

    #[Route('/{id}/disable', name: 'lote_disable', requirements: ['id' => UidType::REGEX], methods: ['PATCH'])]
    public function disable(string $id, ChangeStateLoteService $s, LoteDtoTransformer $t): Response
    {
        return $this->ok(['message' => 'Lote deshabilitado', 'item' => $t->fromObject($s->execute($id, false))]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'lote_delete', requirements: ['id' => UidType::REGEX], methods: ['DELETE'])]
    public function delete(string $id, DeleteLoteService $s): Response
    {
        $s->execute($id);
        return $this->ok(['message' => 'Lote eliminado', 'item' => null]);
    }
}
```

---

## 8. NAMING CONVENTIONS

| Elemento | Patron | Ejemplo |
|----------|--------|---------|
| Entity class | PascalCase | `Lote` |
| Table name | `app_snake_case` | `core_lote` |
| Property | camelCase, private nullable | `private ?string $nombre = null` |
| Getter | `get[Prop](): ?Type` | `getNombre(): ?string` |
| Setter | `set[Prop](): static` | `setNombre(): static` |
| DTO | `[Entity]Dto` | `LoteDto` |
| FilterDto | `[Entity]FilterDto` | `LoteFilterDto` |
| Factory | `[Entity]Factory` | `LoteFactory` |
| Transformer | `[Entity]DtoTransformer` | `LoteDtoTransformer` |
| Repository | `[Entity]Repository` | `LoteRepository` |
| Controller | `[Entity]Api` | `LoteApi` |
| Create service | `Create[Entity]Service` | `CreateLoteService` |
| Get one service | `Get[Entity]Service` | `GetLoteService` |
| Get list service | `Get[Entity]sService` | `GetLotesService` |
| Update service | `Update[Entity]Service` | `UpdateLoteService` |
| Delete service | `Delete[Entity]Service` | `DeleteLoteService` |
| ChangeState service | `ChangeState[Entity]Service` | `ChangeStateLoteService` |
| Route prefix | `/entidades` (plural) | `/lotes` |
| Route name | `entity_action` | `lote_create` |

## 9. RESPONSE FORMAT

```json
// Exito con item
{"status": true, "message": "...", "item": {...}}

// Exito con lista
{"status": true, "items": [...], "pagination": {"page": 0, "itemsPerPage": 5, "count": 5, "totalItems": 42, "startIndex": 1, "endIndex": 5}}

// Error
{"status": false, "message": "...", "exception": "missing_parameter_exception"}
```

## 10. UUID HANDLING

- Entidades usan `int $id` interno + `AbstractUid $uuid` publico
- APIs SIEMPRE usan UUID (Base58, 22 chars)
- Regex para rutas: `UidType::REGEX` = `[1-9A-HJ-NP-Za-km-z]{22}`
- Repository `ofId()` busca por UUID, no por int
- `UidType::toString($entity->uuid())` para convertir a string
- `UidType::generate()` para crear nuevo (auto en PrePersist)
