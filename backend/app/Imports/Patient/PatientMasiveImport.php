<?php

namespace App\Imports\Patient;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Constants;
use App\Models\Patient;
use App\Services\CacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PatientMasiveImport implements ShouldQueue, SkipsOnFailure, ToModel, WithChunkReading, WithEvents, WithHeadingRow
{
    use Importable, SkipsFailures;

    protected string $batchId;
    protected string $companyId;

    private CacheService $cacheService;
    private string $key_redis_project;

    // catálogos en memoria (Collections)
    protected $tipoIdPisis;
    protected $ripsTipoUsuarioVersion2;
    protected $sexos;
    protected $paises;
    protected $municipios;
    protected $zonaVersion2;

    public function __construct(
        string $batchId,
        string $companyId,
        $tipoIdPisis,
        $ripsTipoUsuarioVersion2,
        $sexos,
        $paises,
        $municipios,
        $zonaVersion2,
    ) {
        $this->batchId = $batchId;
        $this->companyId = $companyId;

        $this->tipoIdPisis = $tipoIdPisis;
        $this->ripsTipoUsuarioVersion2 = $ripsTipoUsuarioVersion2;
        $this->sexos = $sexos;
        $this->paises = $paises;
        $this->municipios = $municipios;
        $this->zonaVersion2 = $zonaVersion2;

        $this->cacheService = new CacheService();
        $this->key_redis_project = env('KEY_REDIS_PROJECT', '');
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                // Total de filas (primera hoja) menos la cabecera
                $totals = $event->getReader()->getTotalRows();
                $first = is_array($totals) ? (int) (array_values($totals)[0] ?? 0) : 0;
                $totalRows = max($first - 1, 0);

                Redis::set("import:{$this->batchId}:total", $totalRows);
                Redis::set("import:{$this->batchId}:processed", 0);
                Redis::set("import:{$this->batchId}:success", 0);

                event(new ImportProgressEvent(
                    $this->batchId,
                    "0/{$totalRows}",
                    'Inicializando importación',
                    ErrorCollector::countErrors($this->batchId),
                    'active',
                    'Preparando procesamiento de filas'
                ));
            },

            AfterImport::class => function (AfterImport $event) {
                // Limpiar cache de pacientes
                $this->cacheService->clearByPrefix($this->key_redis_project . 'string:patients*');

                $totalExpected = (int) (Redis::get("import:{$this->batchId}:total") ?: 0);
                $processed     = (int) (Redis::get("import:{$this->batchId}:processed") ?: 0);
                $success       = (int) (Redis::get("import:{$this->batchId}:success") ?: 0);
                $errors        = ErrorCollector::countErrors($this->batchId);

                // Estado final:
                // - failed: se procesó algo y no se insertó ningún paciente
                // - completed_with_errors: hubo inserts y al menos un error
                // - success: inserts y 0 errores
                if ($success === 0 && $processed > 0) {
                    $status = 'failed';
                } elseif ($errors > 0) {
                    $status = 'completed_with_errors';
                } else {
                    $status = 'success';
                }

                // Persistir errores + estado
                ErrorCollector::saveErrorsToDatabase($this->batchId, $status);

                // Evento final
                $progressText = "{$processed}/{$totalExpected}";
                if ($status === 'failed') {
                    event(new ImportProgressEvent(
                        $this->batchId,
                        $progressText,
                        'Proceso fallido',
                        (string)$errors,
                        'failed',
                        'Todas las filas evaluadas fallaron; no se registraron pacientes'
                    ));
                } elseif ($status === 'completed_with_errors') {
                    event(new ImportProgressEvent(
                        $this->batchId,
                        $progressText,
                        'Proceso completado con errores',
                        (string)$errors,
                        'completed_with_errors',
                        'Importación completada con novedades'
                    ));
                } else {
                    event(new ImportProgressEvent(
                        $this->batchId,
                        $progressText,
                        'Proceso completado',
                        (string)$errors,
                        'completed',
                        'Importación completada sin errores'
                    ));
                }

                // Limpiar contadores
                Redis::del("import:{$this->batchId}:total");
                Redis::del("import:{$this->batchId}:processed");
                Redis::del("import:{$this->batchId}:success");
            },
        ];
    }

    public function chunkSize(): int
    {
        return Constants::CHUNKSIZE;
    }

    public function model(array $row)
    {
        // Con WithHeadingRow, $row trae claves normalizadas desde la cabecera (p.ej. 'tipo_de_documento')
        return DB::transaction(function () use ($row) {
            $processed = (int) Redis::incrby("import:{$this->batchId}:processed", 1);
            $total = (int) (Redis::get("import:{$this->batchId}:total") ?: 1);

            // Mapea a tus nombres internos
            $data = [
                'tipo_id_pisi_id'                => $row['tipo_de_documento'] ?? null,
                'document'                       => $row['documento'] ?? null,
                'rips_tipo_usuario_version2_id'  => $row['tipo_de_usuario'] ?? null,
                'birth_date'                     => $this->castExcelDate($row['fecha_de_nacimiento'] ?? null),
                'sexo_id'                        => $row['sexo'] ?? null,
                'pais_residency_id'              => $row['pais_de_residencia'] ?? null,
                'municipio_residency_id'         => $row['municipio_de_residencia'] ?? null,
                'zona_version2_id'               => $row['zona_territorial_de_residencia'] ?? null,
                'incapacity'                     => $row['incapacidad'] ?? null,
                'pais_origin_id'                 => $row['pais_de_origen'] ?? null,
                'first_name'                     => $row['primer_nombre'] ?? null,
                'second_name'                    => $row['segundo_nombre'] ?? null,
                'first_surname'                  => $row['primer_apellido'] ?? null,
                'second_surname'                 => $row['segundo_apellido'] ?? null,
                'company_id'                     => $this->companyId,
            ];

            // Validaciones: si hay errores, NO inserta
            if ($this->validations($row, $processed, $data)) {
                event(new ImportProgressEvent(
                    $this->batchId,
                    "{$processed}/{$total}",
                    'Procesando…',
                    ErrorCollector::countErrors($this->batchId),
                    'active',
                    "Fila {$processed} de {$total} con novedades"
                ));
                return null;
            }

            // Normaliza incapacidad (SI/SÍ/YES/1 -> 1)
            $incap = $row['incapacidad'] ?? '';
            $data['incapacity'] = in_array(mb_strtoupper(trim($incap)), ['SI', 'SÍ', 'YES', '1'], true) ? 1 : 0;

            Patient::create($data);

            // Marca éxito
            Redis::incrby("import:{$this->batchId}:success", 1);

            event(new ImportProgressEvent(
                $this->batchId,
                "{$processed}/{$total}",
                'Procesando…',
                ErrorCollector::countErrors($this->batchId),
                'active',
                "Fila {$processed} de {$total}"
            ));

            return null;
        });
    }

    /** ==== Auxiliares ==== */

    private function castExcelDate($value): ?string
    {
        if ($value === null || $value === '') return null;

        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }

        if ($this->isValidDate($value)) {
            try {
                return (new \DateTime($value))->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        return null; // fuerza null si no es fecha válida
    }

    protected function isValidDate($value): bool
    {
        $v = trim((string)$value);
        if ($v === '') return false;

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y'];
        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $v);
            if ($d && $d->format($format) === $v) return true;
        }
        return false;
    }

    protected array $nacionalitiesMap = [
        '0196d975-d025-736a-8d98-1caaf180d672' => 'CO', // Colombia
        '0196d975-d249-72ed-a011-682197c69476' => 'VE', // Venezuela
    ];

    protected array $documentTypesMap = [
        '0196d975-cf46-729a-bfe0-f7c9cf7ffe2f' => 'CC',
        '0196d975-cf69-70cf-b75f-b3378e34eea4' => 'TI',
        '0196d975-cf62-71d5-bb19-0c6aee438915' => 'RC',
        '0196d975-cf4d-70b0-938b-0cd6e930e505' => 'CN',
        '0196d975-cf5d-733d-aace-7754f3adc299' => 'PE',
        '0196d975-cf4b-717f-ae50-fabe2296153a' => 'CE',
        '0196d975-cf48-7004-80fa-79f0d63f3cea' => 'CD',
        '0196d975-cf5a-722e-b87d-88f60de540ad' => 'PA',
        '0196d975-cf64-7343-8af0-6ec9608209ee' => 'SC',
    ];

    /**
     * Guarda error usando los nombres que llegaron originalmente en el Excel:
     * - columnName = $columnKey (clave de $row).
     * - originalData = $row completo (no $data interno).
     */
    protected function pushError(
        int $processedRowIndex,
        string $columnKey,
        $value,
        array $originalRow,
        string $message,
        string $code
    ): void {
        // Con WithHeadingRow, la primera fila de datos en Excel es la 2.
        $excelRowNumber = $processedRowIndex + 1; // processed=1 -> Excel row 2

        ErrorCollector::addError(
            batchId: $this->batchId,
            rowNumber: $excelRowNumber,
            columnName: $columnKey, // p.ej. 'tipo_de_documento', 'fecha_de_nacimiento'
            errorMessage: $message,
            errorType: $code, // p.ej. 'PATIENT_ROW_002'
            errorValue: is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE),
            originalData: json_encode($originalRow, JSON_UNESCAPED_UNICODE) // <-- ROW ORIGINAL DEL EXCEL
        );
    }

    /** Validaciones de negocio */
    public function validations(array $row, int $processed, array $data): bool
    {
        $error = false;

        // tipo de documento
        if (!$this->tipoIdPisis($row['tipo_de_documento'] ?? null, 'id')) {
            $this->pushError($processed, 'tipo_de_documento', $row['tipo_de_documento'] ?? null, $row, 'El ID de Tipo de Documento no existe.', 'PATIENT_ROW_001');
            $error = true;
        }

        // documento
        $doc = ($row['documento'] ?? '');
        if (trim($doc) === '') {
            $this->pushError($processed, 'documento', $doc, $row, 'El documento es obligatorio.', 'PATIENT_ROW_002');
            $error = true;
        } elseif (strlen($doc) < 4 || strlen($doc) > 20) {
            $this->pushError($processed, 'documento', $doc, $row, 'El documento debe tener entre 4 y 20 caracteres.', 'PATIENT_ROW_003');
            $error = true;
        }

        // tipo de usuario
        if (!$this->ripsTipoUsuario($row['tipo_de_usuario'] ?? null, 'id')) {
            $this->pushError($processed, 'tipo_de_usuario', $row['tipo_de_usuario'] ?? null, $row, 'Tipo de usuario no válido.', 'PATIENT_ROW_004');
            $error = true;
        }

        // fecha de nacimiento + reglas de documento
        $tipoId = $row['tipo_de_documento'] ?? null;
        $birthDate = $this->castExcelDate($row['fecha_de_nacimiento'] ?? null);
        $paisOriginId = $row['pais_de_origen'] ?? null;
        $paisResidencyId = $row['pais_de_residencia'] ?? null;

        if ($birthDate) {
            $birth = new \DateTime($birthDate);
            $today = new \DateTime();
            $age = $today->diff($birth)->y;

            $tipoIdCode = $this->documentTypesMap[$tipoId] ?? null;
            $originCode = $this->nacionalitiesMap[$paisOriginId] ?? null;
            $residencyCode = $this->nacionalitiesMap[$paisResidencyId] ?? null;

            $allowedForeignTransientDocs = ['CE', 'CD', 'PA', 'SC'];

            if ($age >= 18 && $originCode === 'CO' && $tipoIdCode !== 'CC') {
                $this->pushError($processed, 'tipo_de_documento', $tipoId, $row, 'Mayores de edad colombianos deben usar CC.', 'PATIENT_ROW_005');
                $error = true;
            }
            if ($tipoIdCode === 'TI' && ($age < 7 || $age > 17)) {
                $this->pushError($processed, 'tipo_de_documento', $tipoId, $row, 'TI válida entre 7 y 17 años.', 'PATIENT_ROW_006');
                $error = true;
            }
            if (in_array($tipoIdCode, ['RC', 'CN'], true) && $age >= 7) {
                $this->pushError($processed, 'tipo_de_documento', $tipoId, $row, 'RC o CN solo para menores de 7 años.', 'PATIENT_ROW_007');
                $error = true;
            }
            if ($tipoIdCode === 'CN' && $age > 3) {
                $this->pushError($processed, 'tipo_de_documento', $tipoId, $row, 'CN recomendado solo hasta 3 años.', 'PATIENT_ROW_008');
                $error = true;
            }
            if ($originCode === 'VE' && $tipoIdCode !== 'PE') {
                $this->pushError($processed, 'tipo_de_documento', $tipoId, $row, 'Venezolanos deben usar PE.', 'PATIENT_ROW_009');
                $error = true;
            }
            $isForeigner = $originCode && $originCode !== 'CO';
            $isTransient = $residencyCode === 'CO';
            if ($isForeigner && $isTransient && !in_array($tipoIdCode, $allowedForeignTransientDocs, true)) {
                $this->pushError($processed, 'tipo_de_documento', $tipoId, $row, 'Extranjeros de paso: CE, CD, PA o SC.', 'PATIENT_ROW_010');
                $error = true;
            }
        } else {
            $this->pushError($processed, 'fecha_de_nacimiento', $row['fecha_de_nacimiento'] ?? null, $row, 'La fecha no es válida.', 'PATIENT_ROW_011');
            $error = true;
        }

        // sexo
        if (!$this->sexos($row['sexo'] ?? null, 'id')) {
            $this->pushError($processed, 'sexo', $row['sexo'] ?? null, $row, 'Sexo no válido.', 'PATIENT_ROW_012');
            $error = true;
        }

        // país residencia
        if (!$this->paises($row['pais_de_residencia'] ?? null, 'id')) {
            $this->pushError($processed, 'pais_de_residencia', $row['pais_de_residencia'] ?? null, $row, 'País de residencia no válido.', 'PATIENT_ROW_013');
            $error = true;
        }

        // municipio (opcional)
        if (!empty($row['municipio_de_residencia']) && !$this->municipios($row['municipio_de_residencia'], 'id')) {
            $this->pushError($processed, 'municipio_de_residencia', $row['municipio_de_residencia'], $row, 'Municipio de residencia no válido.', 'PATIENT_ROW_014');
            $error = true;
        }

        // zona (opcional)
        if (!empty($row['zona_territorial_de_residencia']) && !$this->zonas($row['zona_territorial_de_residencia'], 'id')) {
            $this->pushError($processed, 'zona_territorial_de_residencia', $row['zona_territorial_de_residencia'], $row, 'Zona no válida.', 'PATIENT_ROW_015');
            $error = true;
        }

        // incapacidad (opcional: SI/SÍ/NO)
        if (!empty($row['incapacidad'])) {
            $val = mb_strtoupper(trim((string)$row['incapacidad']));
            if (!in_array($val, ['SI', 'SÍ', 'NO'], true)) {
                $this->pushError($processed, 'incapacidad', $row['incapacidad'], $row, 'Incapacidad solo acepta SI/SÍ o NO.', 'PATIENT_ROW_016');
                $error = true;
            }
        }

        // país origen
        if (!$this->paises($row['pais_de_origen'] ?? null, 'id')) {
            $this->pushError($processed, 'pais_de_origen', $row['pais_de_origen'] ?? null, $row, 'País de origen no válido.', 'PATIENT_ROW_017');
            $error = true;
        }

        // nombres/apellidos
        $firstName = ($row['primer_nombre'] ?? '');
        if (trim($firstName) === '') {
            $this->pushError($processed, 'primer_nombre', $firstName, $row, 'El primer nombre es obligatorio.', 'PATIENT_ROW_018');
            $error = true;
        } elseif (mb_strlen($firstName) > 255) {
            $this->pushError($processed, 'primer_nombre', $firstName, $row, 'El primer nombre excede 255 caracteres.', 'PATIENT_ROW_019');
            $error = true;
        }

        if (!empty($row['segundo_nombre']) && mb_strlen((string)$row['segundo_nombre']) > 255) {
            $this->pushError($processed, 'segundo_nombre', $row['segundo_nombre'], $row, 'El segundo nombre excede 255 caracteres.', 'PATIENT_ROW_020');
            $error = true;
        }

        $firstSurname = ($row['primer_apellido'] ?? '');
        if (trim($firstSurname) === '') {
            $this->pushError($processed, 'primer_apellido', $firstSurname, $row, 'El primer apellido es obligatorio.', 'PATIENT_ROW_021');
            $error = true;
        } elseif (mb_strlen($firstSurname) > 255) {
            $this->pushError($processed, 'primer_apellido', $firstSurname, $row, 'El primer apellido excede 255 caracteres.', 'PATIENT_ROW_022');
            $error = true;
        }

        if (!empty($row['segundo_apellido']) && mb_strlen((string)$row['segundo_apellido']) > 255) {
            $this->pushError($processed, 'segundo_apellido', $row['segundo_apellido'], $row, 'El segundo apellido excede 255 caracteres.', 'PATIENT_ROW_023');
            $error = true;
        }

        return $error;
    }

    /** === Búsquedas en catálogos (Collections) === */

    public function tipoIdPisis($value, $field)
    {
        $cache = $this->tipoIdPisis;
        return $cache->first(function ($item) use ($value, $field) {
            return isset($item[$field]) && strtoupper($item[$field]) === strtoupper((string)$value);
        });
    }

    public function ripsTipoUsuario($value, $field)
    {
        $cache = $this->ripsTipoUsuarioVersion2;
        return $cache->first(function ($item) use ($value, $field) {
            return isset($item[$field]) && strtoupper($item[$field]) === strtoupper((string)$value);
        });
    }

    public function sexos($value, $field)
    {
        $cache = $this->sexos;
        return $cache->first(function ($item) use ($value, $field) {
            return isset($item[$field]) && strtoupper($item[$field]) === strtoupper((string)$value);
        });
    }

    public function paises($value, $field)
    {
        $cache = $this->paises;
        return $cache->first(function ($item) use ($value, $field) {
            return isset($item[$field]) && strtoupper($item[$field]) === strtoupper((string)$value);
        });
    }

    public function municipios($value, $field)
    {
        $cache = $this->municipios;
        return $cache->first(function ($item) use ($value, $field) {
            return isset($item[$field]) && strtoupper($item[$field]) === strtoupper((string)$value);
        });
    }

    public function zonas($value, $field)
    {
        $cache = $this->zonaVersion2;
        return $cache->first(function ($item) use ($value, $field) {
            return isset($item[$field]) && strtoupper($item[$field]) === strtoupper((string)$value);
        });
    }
}
