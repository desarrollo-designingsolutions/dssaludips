<?php

namespace App\Jobs\Rips;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SaveErrorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
        $this->onQueue('import_rips');
    }

    public function handle()
    {
        $status = "failed";
        ErrorCollector::saveErrorsToDatabase($this->batchId, $status);
        event(new ImportProgressEvent($this->batchId, 0, 'Validación completada', count(ErrorCollector::getErrors($this->batchId)), $status, 'final'));
    }
}
