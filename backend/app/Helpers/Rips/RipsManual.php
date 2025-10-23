<?php

namespace App\Helpers\Rips;

use App\Models\Municipio;
use App\Models\Pais;
use App\Models\RipsTipoUsuarioVersion2;
use App\Models\Sexo;
use App\Models\TipoIdPisis;
use App\Models\ZonaVersion2;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RipsManual
{
    public static function saveInvoiceUsersAndJson($invoiceModel, array $incomingUsers, string $company_id, $rip, string $type)
    {
        // Leer JSON actual (si existe) para conservar 'servicios' de usuarios ya guardados
        $existingJson = [];
        if (!empty($invoiceModel->path_json) && Storage::disk('public')->exists($invoiceModel->path_json)) {
            try {
                $existingJson = openFileJson($invoiceModel->path_json) ?: [];
            } catch (\Throwable $e) {
                Log::warning("No se pudo leer JSON existente ({$invoiceModel->path_json}): " . $e->getMessage());
                $existingJson = [];
            }
        }

        $existingUsuariosIndex = [];
        if (!empty($existingJson['usuarios']) && is_array($existingJson['usuarios'])) {
            foreach ($existingJson['usuarios'] as $u) {
                if (!empty($u['numDocumentoIdentificacion'])) {
                    $existingUsuariosIndex[$u['numDocumentoIdentificacion']] = $u;
                }
            }
        }

        // Mapear y normalizar los incomingUsers a la estructura final que irá en JSON y DB
        $mappedUsers = [];

        foreach ($incomingUsers as $u) {
            // EXTRAER identificadores básicos
            $numDoc = $u['numDocumentoIdentificacion'] ?? null;
            $incomingId = $u['id'] ?? null; // si el frontend envía el id de rip_invoice_users
            $shouldDelete = false;

            // Determinar si viene la bandera delete (acepta true, 'true', 1, '1', 'yes')
            if (isset($u['delete'])) {
                // FILTER_VALIDATE_BOOLEAN devuelve true/false; si no es un valor válido devuelve null (por eso la comparativa con === true)
                $val = filter_var($u['delete'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $shouldDelete = ($val === true);
            }

            // <<<--- BORRADO SI delete: si viene delete=true, borrar de DB y del index del JSON y saltar
            if ($shouldDelete) {
                try {
                    if (!empty($incomingId)) {
                        // Si el frontend nos dio el id (uuid de rip_invoice_users), borramos por id y rip_invoice_id
                        DB::table('rip_invoice_users')
                            ->where('id', $incomingId)
                            ->where('rip_invoice_id', $invoiceModel->id)
                            ->delete();
                        Log::info("rip_invoice_users borrado por id {$incomingId} (rip_invoice_id={$invoiceModel->id})");
                    } elseif (!empty($numDoc)) {
                        // Si no hay id, borramos por rip_invoice_id + numDocumentoIdentificacion
                        DB::table('rip_invoice_users')
                            ->where('rip_invoice_id', $invoiceModel->id)
                            ->where('numDocumentoIdentificacion', $numDoc)
                            ->delete();
                        Log::info("rip_invoice_users borrado por numDocumentoIdentificacion {$numDoc} (rip_invoice_id={$invoiceModel->id})");
                    } else {
                        Log::warning("Se solicitó delete pero no se proporcionó id ni numDocumentoIdentificacion en el payload: " . json_encode($u));
                    }

                    // además quitar del índice de usuarios existentes para que no se reutilicen sus 'servicios'
                    if (!empty($numDoc) && isset($existingUsuariosIndex[$numDoc])) {
                        unset($existingUsuariosIndex[$numDoc]);
                        Log::info("Se eliminó de existingUsuariosIndex el numDocumentoIdentificacion {$numDoc} por delete=true");
                    }
                } catch (\Throwable $e) {
                    Log::error("Error al eliminar rip_invoice_users por delete flag: " . $e->getMessage());
                    // no abortamos la ejecución, simplemente continuamos (puedes lanzar excepción si prefieres rollback completo)
                }

                // saltar el mapeo de este usuario (no se incluirá en mappedUsers ni en el JSON final)
                continue;
            }
            // <<<--- FIN BORRADO SI delete

            // si llegamos aquí => NO viene delete => procesar normalmente
            $tipoDocCode = null;
            $tipoUsuarioCode = null;
            $codSexo = null;
            $codPaisResidencia = null;
            $codMunicipioResidencia = null;
            $codZona = null;
            $codPaisOrigen = null;

            if (!empty($u['tipoDocumentoIdentificacion'])) {
                $tipoDocCode = TipoIdPisis::where('id', $u['tipoDocumentoIdentificacion'])->select('codigo')->first();
                $tipoDocCode = $tipoDocCode?->codigo;
            }

            if (!empty($u['tipoUsuario'])) {
                $tipoUsuarioCode = RipsTipoUsuarioVersion2::where('id', $u['tipoUsuario'])->select('codigo')->first();
                $tipoUsuarioCode = $tipoUsuarioCode?->codigo;
            }

            if (!empty($u['codSexo'])) {
                $codSexo = Sexo::where('id', $u['codSexo'])->select('codigo')->first();
                $codSexo = $codSexo?->codigo;
            }

            if (!empty($u['codPaisResidencia'])) {
                $codPaisResidencia = Pais::where('id', $u['codPaisResidencia'])->select('codigo')->first();
                $codPaisResidencia = $codPaisResidencia?->codigo;
            }

            if (!empty($u['codMunicipioResidencia'])) {
                $codMunicipioResidencia = Municipio::where('id', $u['codMunicipioResidencia'])->select('codigo')->first();
                $codMunicipioResidencia = $codMunicipioResidencia?->codigo;
            }

            if (!empty($u['codZonaTerritorialResidencia'])) {
                $codZona = ZonaVersion2::where('id', $u['codZonaTerritorialResidencia'])->select('codigo')->first();
                $codZona = $codZona?->codigo;
            }

            if (!empty($u['codPaisOrigen'])) {
                $codPaisOrigen = Pais::where('id', $u['codPaisOrigen'])->select('codigo')->first();
                $codPaisOrigen = $codPaisOrigen?->codigo;
            }

            // conservar servicios si ya existían para ese usuario (match por numDocumentoIdentificacion)
            $servicios = [];
            if (!empty($existingUsuariosIndex[$numDoc]) && !empty($existingUsuariosIndex[$numDoc]['servicios'])) {
                $servicios = $existingUsuariosIndex[$numDoc]['servicios'];
            }

            $mappedUsers[] = [
                // campos que va el JSON y que también guardaremos en la tabla
                'tipoDocumentoIdentificacion' => $tipoDocCode,
                'numDocumentoIdentificacion' => $numDoc,
                'tipoUsuario' => $tipoUsuarioCode,
                'fechaNacimiento' => $u['fechaNacimiento'] ?? null,
                'codSexo' => $codSexo,
                'codPaisResidencia' => $codPaisResidencia,
                'codMunicipioResidencia' => $codMunicipioResidencia,
                'codZonaTerritorialResidencia' => $codZona,
                'incapacidad' => $u['incapacidad'] ?? null,
                'codPaisOrigen' => $codPaisOrigen,
                'consecutivo' => null, // se asigna luego
                'servicios' => $servicios,
            ];
        }

        // Asignar consecutivos: 1..N a usuarios y consecutivo interno a cada servicio
        $con = 1;
        foreach ($mappedUsers as &$mu) {
            $mu['consecutivo'] = $con;
            // servicios -> asignar consecutivo por tipo si los hubiera
            foreach (['consultas', 'procedimientos', 'medicamentos', 'urgencias', 'otrosServicios', 'hospitalizacion', 'recienNacidos'] as $serviceKey) {
                if (!empty($mu['servicios'][$serviceKey]) && is_array($mu['servicios'][$serviceKey])) {
                    $j = 1;
                    foreach ($mu['servicios'][$serviceKey] as &$srv) {
                        $srv['consecutivo'] = $j;
                        $j++;
                    }
                }
            }
            $con++;
        }
        unset($mu);

        // Construir el JSON final de la factura (mantener metadatos existentes si quieres)
        $infoJson = [
            'numDocumentoIdObligado' => $existingJson['numDocumentoIdObligado'] ?? $invoiceModel->nit ?? null,
            'numFactura' => $existingJson['numFactura'] ?? $invoiceModel->invoice_number ?? null,
            'TipoNota' => $existingJson['TipoNota'] ?? $invoiceModel->note_type ?? null,
            'numNota' => $existingJson['numNota'] ?? $invoiceModel->note_number ?? null,
            'usuarios' => $mappedUsers,
        ];

        // Generar ruta y guardar JSON (sobrescribe)
        $numFactura = $infoJson['numFactura'] ?? 'factura_' . ($invoiceModel->id ?? Str::uuid());
        $nameFile = $numFactura . '.json';
        $ruta = 'companies/company_' . $company_id . '/rips/' . $type . '/rip_' . $rip->id . '/invoices/' . $numFactura . '/' . $nameFile;

        Storage::disk('public')->makeDirectory(dirname($ruta));
        Storage::disk('public')->put($ruta, json_encode($infoJson, JSON_UNESCAPED_UNICODE));

        // Si la factura tenía un path_json distinto, borrar carpeta vieja para evitar huérfanos
        try {
            if (!empty($invoiceModel->path_json) && $invoiceModel->path_json !== $ruta && Storage::disk('public')->exists(dirname($invoiceModel->path_json))) {
                // borrar directorio antiguo completo
                Storage::disk('public')->deleteDirectory(dirname($invoiceModel->path_json));
            }
        } catch (\Throwable $e) {
            Log::warning("No se pudo eliminar carpeta antigua de invoice {$invoiceModel->id}: " . $e->getMessage());
        }

        GenerateRipInfo::generateDataJsonAndExcel($invoiceModel?->rip_id, $type);

        // Actualizar invoice en BD: path_json, count_users, sumVr opcional
        $invoiceModel->path_json = $ruta;
        $invoiceModel->count_users = count($mappedUsers);
        // si necesitas sumVr u otros campos, actualízalos aquí
        $invoiceModel->save();

        // SINCRONIZAR tabla rip_invoice_users (insert/update)
        foreach ($mappedUsers as $mu) {
            $numDoc = $mu['numDocumentoIdentificacion'];
            // buscar por numDocumentoIdentificacion + rip_invoice_id
            $existingDb = DB::table('rip_invoice_users')
                ->where('rip_invoice_id', $invoiceModel->id)
                ->where('numDocumentoIdentificacion', $numDoc)
                ->first();

            // preparar payload DB (column names de tu captura)
            $dbPayload = [
                'id' => $existingDb->id ?? (string) Str::uuid(),
                'rip_invoice_id' => $invoiceModel->id,
                'tipoDocumentoIdentificacion' => $mu['tipoDocumentoIdentificacion'],
                'numDocumentoIdentificacion' => $mu['numDocumentoIdentificacion'],
                'tipoUsuario' => $mu['tipoUsuario'],
                'fechaNacimiento' => $mu['fechaNacimiento'],
                'codSexo' => $mu['codSexo'],
                'codPaisResidencia' => $mu['codPaisResidencia'],
                'codMunicipioResidencia' => $mu['codMunicipioResidencia'],
                'codZonaTerritorialResidencia' => $mu['codZonaTerritorialResidencia'],
                'incapacidad' => $mu['incapacidad'],
                'codPaisOrigen' => $mu['codPaisOrigen'],
                'consecutivo' => $mu['consecutivo'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($existingDb) {
                // actualizar
                DB::table('rip_invoice_users')->where('id', $existingDb->id)->update(array_merge($dbPayload, ['created_at' => $existingDb->created_at]));
            } else {
                // insertar
                DB::table('rip_invoice_users')->insert($dbPayload);
            }
        }

        // devolver el invoice recargado si lo necesitas
        return $invoiceModel->fresh();
    }
}
