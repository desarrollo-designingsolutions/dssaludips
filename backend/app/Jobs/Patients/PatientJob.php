<?php

namespace App\Jobs\Patients;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Helpers\Common\ImportXlsxValidator;
use App\Imports\Patient\PatientMasiveImport;
use App\Repositories\MunicipioRepository;
use App\Repositories\PaisRepository;
use App\Repositories\RipsTipoUsuarioVersion2Repository;
use App\Repositories\SexoRepository;
use App\Repositories\TipoIdPisisRepository;
use App\Repositories\ZonaVersion2Repository;
use App\Traits\ImportHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Facades\Excel;

class PatientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ImportHelper;

    protected $batchId;

    protected $filePath;

    protected $company_id;

    protected TipoIdPisisRepository $tipoIdPisisRepository;

    protected RipsTipoUsuarioVersion2Repository $ripsTipoUsuarioVersion2Repository;

    protected SexoRepository $sexoRepository;

    protected PaisRepository $paisRepository;

    protected MunicipioRepository $municipioRepository;

    protected ZonaVersion2Repository $zonaVersion2Repository;

    public function __construct(string $batchId, string $filePath, string $company_id)
    {
        $this->batchId = $batchId;
        $this->filePath = $filePath;
        $this->company_id = $company_id;
        $this->onQueue('import_patients');
    }

    public function handle(
        TipoIdPisisRepository $tipoIdPisisRepository,
        RipsTipoUsuarioVersion2Repository $ripsTipoUsuarioVersion2Repository,
        SexoRepository $sexoRepository,
        PaisRepository $paisRepository,
        MunicipioRepository $municipioRepository,
        ZonaVersion2Repository $zonaVersion2Repository
    ) {
        $this->startBenchmark($this->batchId);

        event(new ImportProgressEvent($this->batchId, 0, 'Inicializando validación de estructura', ErrorCollector::countErrors($this->batchId), 'active', 'Validando cabeceras'));

        ImportXlsxValidator::validate($this->batchId, $this->filePath);

        if (ErrorCollector::countErrors($this->batchId) > 0) {
            $errors = ErrorCollector::countErrors($this->batchId);
            ErrorCollector::saveErrorsToDatabase($this->batchId, 'failed');
            event(new ImportProgressEvent($this->batchId, 0, 'Finalizacion validación de estructura', $errors, 'failed', 'Validación completada con errores'));
            $this->endBenchmark($this->batchId);
            return;
        }

        event(new ImportProgressEvent($this->batchId, 0, 'Inicializando validación de registros', ErrorCollector::countErrors($this->batchId), 'active', 'Validando registros'));

        $tipoIdPisis             = $tipoIdPisisRepository->paginate(["typeData" => "all"]);
        $ripsTipoUsuarioVersion2 = $ripsTipoUsuarioVersion2Repository->paginate(["typeData" => "all"]);
        $sexos                   = $sexoRepository->paginate(["typeData" => "all"]);
        $paises                  = $paisRepository->paginate(["typeData" => "all"]);
        $municipios              = $municipioRepository->paginate(["typeData" => "all"]);
        $zonaVersion2            = $zonaVersion2Repository->paginate(["typeData" => "all"]);

        // Aquí iría la lógica para procesar el archivo XLSX y guardar los pacientes.
        $importer = new PatientMasiveImport(
            $this->batchId,
            $this->company_id,
            $tipoIdPisis,
            $ripsTipoUsuarioVersion2,
            $sexos,
            $paises,
            $municipios,
            $zonaVersion2
        );

        Excel::import($importer, $this->filePath);

        $this->endBenchmark($this->batchId);
    }
}
