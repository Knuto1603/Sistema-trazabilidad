<?php

namespace App\apps\core\Controller;

use App\apps\core\Repository\ArchivoDespachoRepository;
use App\apps\core\Repository\ClienteRepository;
use App\apps\core\Repository\DespachoRepository;
use App\apps\core\Repository\FacturaRepository;
use App\apps\core\Repository\ParametroRepository;
use App\apps\core\Repository\UserSmtpConfigRepository;
use App\shared\Api\AbstractSerializerApi;
use App\shared\Service\SmtpEncryptionService;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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
        #[Autowire('%env(MAILER_DSN)%')]
        private readonly string $mailerDsn,
        private readonly MailerInterface $mailer,
        #[Autowire('%env(SMTP_HOST)%')]
        private readonly string $smtpHost,
        #[Autowire('%env(int:SMTP_PORT)%')]
        private readonly int $smtpPort,
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

        $mailerConfigurado = !empty($this->mailerDsn) && $this->mailerDsn !== 'null://null';

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
        $mailerOk = !empty($this->mailerDsn) && $this->mailerDsn !== 'null://null';

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

        $process = new Process([PHP_BINARY, $console, 'cache:clear', '--no-interaction']);
        $process->run();

        if (!$process->isSuccessful()) {
            return $this->fail('Error al limpiar cache: ' . $process->getErrorOutput());
        }

        return $this->ok(['message' => 'Cache limpiado correctamente', 'item' => ['output' => $process->getOutput()]]);
    }

    #[Route('/correo/test', name: 'dev_correo_test', methods: ['POST'])]
    public function correoTest(
        Request $request,
        ParametroRepository $parametroRepository,
    ): Response {
        $destinatario = \trim($request->request->get('destinatario', ''));
        $asunto       = \trim($request->request->get('asunto', 'TEST - Sistema Trazabilidad'));
        $cuerpo       = \trim($request->request->get('cuerpo', "Correo de prueba enviado desde el Panel Developer.\n\nSi recibiste este mensaje, el sistema de correo está funcionando correctamente."));

        if (!$destinatario || !\filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Ingresa un email de destinatario válido.');
        }

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

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile[] $archivos */
            $archivos = $request->files->get('archivos', []);
            if (!\is_array($archivos)) {
                $archivos = [$archivos];
            }
            foreach ($archivos as $archivo) {
                if ($archivo && $archivo->isValid()) {
                    $email->attachFromPath(
                        $archivo->getPathname(),
                        $archivo->getClientOriginalName(),
                        $archivo->getClientMimeType()
                    );
                }
            }

            $this->mailer->send($email);

            $adjuntados = \count(\array_filter($archivos, fn($f) => $f && $f->isValid()));
            $msg = "Correo enviado a {$destinatario}";
            if ($adjuntados > 0) {
                $msg .= " con {$adjuntados} adjunto(s)";
            }

            return $this->ok(['message' => $msg, 'item' => null]);
        } catch (\Throwable $e) {
            return $this->fail('Error al enviar: ' . $e->getMessage());
        }
    }

    #[Route('/correo/test-smtp', name: 'dev_correo_test_smtp', methods: ['POST'])]
    public function correoTestSmtp(
        Request $request,
        UserSmtpConfigRepository $smtpConfigRepository,
        SmtpEncryptionService $encryption,
        TokenStorageInterface $tokenStorage,
    ): Response {
        $destinatario = \trim($request->request->get('destinatario', ''));

        if (!$destinatario || !\filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return $this->fail('Ingresa un email de destinatario válido.');
        }

        $userUuid = $tokenStorage->getToken()?->getUserIdentifier();
        if (!$userUuid) {
            return $this->fail('No se pudo identificar al usuario autenticado.');
        }

        $config = $smtpConfigRepository->findByUserUuid($userUuid);
        if ($config === null) {
            return $this->fail('No tienes configuración SMTP personal. Configúrala en Usuarios > ícono de sobre.');
        }

        try {
            $smtpPassword = $encryption->decrypt($config->getSmtpPasswordEncrypted());
            $dsn = sprintf(
                'smtp://%s:%s@%s:%d',
                rawurlencode($config->getSmtpEmail()),
                rawurlencode($smtpPassword),
                $this->smtpHost,
                $this->smtpPort
            );

            $transport = Transport::fromDsn($dsn);
            sodium_memzero($smtpPassword);
            $dsn = str_repeat("\0", strlen($dsn));
            unset($dsn);

            $email = (new Email())
                ->from(new Address($config->getSmtpEmail(), $config->getSmtpEmail()))
                ->to($destinatario)
                ->subject('TEST SMTP Personal - Sistema Trazabilidad')
                ->text("Correo de prueba enviado desde tu configuración SMTP personal.\n\nRemitente: {$config->getSmtpEmail()}\nServidor: {$this->smtpHost}:{$this->smtpPort}\n\nSi recibiste este mensaje, tu SMTP personal está funcionando correctamente.");

            (new Mailer($transport))->send($email);

            return $this->ok([
                'message' => "Correo enviado a {$destinatario} desde {$config->getSmtpEmail()}",
                'item'    => null,
            ]);
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
