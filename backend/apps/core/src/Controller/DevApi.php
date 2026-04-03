<?php

namespace App\apps\core\Controller;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Repository\ParametroRepository;
use App\shared\Api\AbstractSerializerApi;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dev')]
#[IsGranted('ROLE_KNUTO')]
class DevApi extends AbstractSerializerApi
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%kernel.environment%')]
        private readonly string $env,
        private readonly MailerInterface $mailer,
    ) {
    }

    #[Route('/info', name: 'dev_info', methods: ['GET'])]
    public function info(
        DespachoRepository $despachoRepository,
        ClienteRepository $clienteRepository,
        FacturaRepository $facturaRepository,
        ArchivoDespachoRepository $archivoDespachoRepository,
    ): Response {
        $uploadsDir = $this->projectDir . '/public/uploads';
        $uploadsDiskFree = disk_free_space($uploadsDir) ?: 0;
        $uploadsDiskTotal = disk_total_space($uploadsDir) ?: 0;

        // Tamaño del directorio de uploads
        $uploadsSize = $this->dirSize($uploadsDir);

        // Tamaño del cache
        $cacheDir = $this->projectDir . '/var/cache';
        $cacheSize = $this->dirSize($cacheDir);

        $now = new \DateTime('now', new \DateTimeZone('America/Lima'));

        $mailerDsn = getenv('MAILER_DSN') ?: ($_ENV['MAILER_DSN'] ?? 'not set');
        $mailerConfigurado = !empty($mailerDsn) && $mailerDsn !== 'null://null' && $mailerDsn !== 'not set';

        return $this->ok([
            'item' => [
                'php'             => PHP_VERSION,
                'symfony_env'     => $this->env,
                'os'              => PHP_OS_FAMILY,
                'server_time'     => $now->format('d/m/Y H:i:s'),
                'timezone'        => 'America/Lima',
                'memory_usage'    => round(memory_get_usage(true) / 1048576, 2),
                'memory_peak'     => round(memory_get_peak_usage(true) / 1048576, 2),
                'memory_limit'    => ini_get('memory_limit'),
                'disk_free_gb'    => round($uploadsDiskFree / 1073741824, 2),
                'disk_total_gb'   => round($uploadsDiskTotal / 1073741824, 2),
                'uploads_size_mb' => round($uploadsSize / 1048576, 2),
                'cache_size_mb'   => round($cacheSize / 1048576, 2),
                'mailer_ok'       => $mailerConfigurado,
                'stats'           => [
                    'despachos' => $despachoRepository->count([]),
                    'clientes'  => $clienteRepository->count([]),
                    'facturas'  => $facturaRepository->count([]),
                    'archivos'  => $archivoDespachoRepository->count([]),
                ],
            ],
        ]);
    }

    #[Route('/health', name: 'dev_health', methods: ['GET'])]
    public function health(Connection $connection): Response
    {
        // DB
        try {
            $connection->executeQuery('SELECT 1');
            $dbOk = true;
            $dbMsg = 'Conectado';
        } catch (\Throwable $e) {
            $dbOk = false;
            $dbMsg = $e->getMessage();
        }

        // Mailer
        $mailerDsn = getenv('MAILER_DSN') ?: ($_ENV['MAILER_DSN'] ?? '');
        $mailerOk = !empty($mailerDsn) && $mailerDsn !== 'null://null';

        // Uploads dir
        $uploadsDir = $this->projectDir . '/public/uploads';
        $uploadsOk  = is_dir($uploadsDir) && is_writable($uploadsDir);

        // Cache dir
        $cacheDir = $this->projectDir . '/var/cache';
        $cacheOk  = is_dir($cacheDir) && is_writable($cacheDir);

        return $this->ok([
            'item' => [
                'db'      => ['ok' => $dbOk,      'msg' => $dbMsg],
                'mailer'  => ['ok' => $mailerOk,  'msg' => $mailerOk ? 'DSN configurado' : 'null://null (no envía)'],
                'uploads' => ['ok' => $uploadsOk, 'msg' => $uploadsOk ? 'Directorio escribible' : 'No escribible'],
                'cache'   => ['ok' => $cacheOk,   'msg' => $cacheOk  ? 'Cache escribible'     : 'No escribible'],
            ],
        ]);
    }

    #[Route('/cache/clear', name: 'dev_cache_clear', methods: ['POST'])]
    public function cacheClear(): Response
    {
        $console = $this->projectDir . '/bin/console';

        if (!\file_exists($console)) {
            return $this->fail('No se encontró el archivo console en: ' . $console);
        }

        $phpBin = PHP_BINARY;
        $output = [];
        $code   = 0;
        \exec("{$phpBin} {$console} cache:clear --no-interaction 2>&1", $output, $code);

        if ($code !== 0) {
            return $this->fail('Error al limpiar cache: ' . \implode("\n", $output));
        }

        return $this->ok(['message' => 'Cache limpiado correctamente', 'item' => ['output' => \implode("\n", $output)]]);
    }

    #[Route('/correo/test', name: 'dev_correo_test', methods: ['POST'])]
    public function correoTest(
        Request $request,
        ParametroRepository $parametroRepository,
    ): Response {
        $body       = \json_decode($request->getContent(), true) ?? [];
        $destinatario = \trim($body['destinatario'] ?? '');
        $asunto       = \trim($body['asunto'] ?? 'TEST - Sistema Trazabilidad');
        $cuerpo       = \trim($body['cuerpo'] ?? "Correo de prueba enviado desde el Panel Developer.\n\nSi recibiste este mensaje, el sistema de correo está funcionando correctamente.");

        if (!$destinatario || !\filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Ingresa un email de destinatario válido.');
        }

        // Reutiliza el servicio de correo: crea un despacho ficticio no, mejor envío directo
        // Envío directo usando los parámetros de remitente configurados
        $remitenteNombre = $parametroRepository->findByAlias('REMNOM')?->getName() ?? 'Sistema Trazabilidad';
        $remitenteEmail  = $parametroRepository->findByAlias('REMAIL')?->getName() ?? '';

        if (!$remitenteEmail) {
            return $this->fail('No hay email remitente configurado. Crea el parámetro REMAIL en Configuración > Parámetros.');
        }

        try {
            $email = (new Email())
                ->from(new Address($remitenteEmail, $remitenteNombre))
                ->to($destinatario)
                ->subject($asunto)
                ->text($cuerpo);

            $this->mailer->send($email);

            return $this->ok(['message' => "Correo enviado a {$destinatario}", 'item' => null]);
        } catch (\Throwable $e) {
            return $this->fail('Error al enviar: ' . $e->getMessage());
        }
    }

    #[Route('/migraciones', name: 'dev_migraciones', methods: ['GET'])]
    public function migraciones(Connection $connection): Response
    {
        try {
            // Tablas ejecutadas
            $ejecutadas = $connection->fetchFirstColumn(
                "SELECT version FROM doctrine_migration_versions ORDER BY executed_at DESC"
            );
        } catch (\Throwable) {
            $ejecutadas = [];
        }

        // Migraciones en disco
        $migrationsDir  = $this->projectDir . '/../migrations';
        $enDisco = [];
        if (\is_dir($migrationsDir)) {
            foreach (\glob($migrationsDir . '/Version*.php') as $file) {
                $enDisco[] = \pathinfo($file, PATHINFO_FILENAME);
            }
            \sort($enDisco);
        }

        $pendientes = \array_filter($enDisco, static fn($v) => !\in_array('DoctrineMigrations\\' . $v, $ejecutadas, true)
            && !\in_array($v, $ejecutadas, true));

        return $this->ok([
            'item' => [
                'ejecutadas' => \count($ejecutadas),
                'en_disco'   => \count($enDisco),
                'pendientes' => \array_values($pendientes),
            ],
        ]);
    }

    private function dirSize(string $dir): int
    {
        if (!\is_dir($dir)) return 0;
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
}
