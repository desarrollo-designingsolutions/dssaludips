<?php

namespace App\Helpers\Rips;

use App\Models\IpsCodHabilitacion;
use App\Models\Municipio;
use App\Models\Pais;
use App\Models\RipInvoiceUser;
use App\Models\RipServiceQuery;
use App\Models\RipsTipoUsuarioVersion2;
use App\Models\ServiceVendor;
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
                        $user = RipInvoiceUser::with([
                            'queries',
                            'procedures',
                            'urgencies',
                            'hospitalizations',
                            'newlyBorns',
                            'medicines',
                            'otherServices'
                        ])->where('id', $incomingId)->where('rip_invoice_id', $invoiceModel->id)->first();
                    } elseif (!empty($numDoc)) {
                        $user = RipInvoiceUser::with([
                            'queries',
                            'procedures',
                            'urgencies',
                            'hospitalizations',
                            'newlyBorns',
                            'medicines',
                            'otherServices'
                        ])->where('rip_invoice_id', $invoiceModel->id)->where('numDocumentoIdentificacion', $numDoc)->first();
                    }

                    if (!empty($user)) {
                        // si tus relaciones están definidas como hasMany, ->delete() borrará las filas relacionadas
                        $user->queries()->delete();
                        $user->procedures()->delete();
                        $user->urgencies()->delete();
                        $user->hospitalizations()->delete();
                        $user->newlyBorns()->delete();
                        $user->medicines()->delete();
                        $user->otherServices()->delete();

                        // y por último el usuario
                        $user->delete();

                        Log::info("RipInvoiceUser {$user->id} y relaciones borradas (Eloquent).");
                    } else {
                        Log::warning("No se encontró RipInvoiceUser para borrar: id={$incomingId} numDoc={$numDoc}");
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
            foreach (['consultas', 'procedimientos', 'medicamentos', 'urgencias', 'otrosservicios', 'hospitalizacion', 'reciennacidos'] as $serviceKey) {
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
            'numDocumentoIdObligado' => $existingJson['numDocumentoIdObligado'] ?? $rip->nit ?? null,
            'numFactura' => $existingJson['numFactura'] ?? $invoiceModel->invoice_number ?? null,
            'tipoNota' => $existingJson['tipoNota'] ?? $invoiceModel->tipoNota ?? null,
            'numNota' => $existingJson['numNota'] ?? $invoiceModel->numNota ?? null,
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

    public static function saveServicesToInvoiceAndDbMapped(
        $invoiceModel,
        array $incomingServices,
        string $company_id,
        ?string $ripInvoiceUserId,
        $rip,
        ?string $typeService = null, // <- tipo que llega desde el front (ej: 'Procedimientos' o 'procedimientos')
        string $type = '' // tu RipTypeEnum u otro uso
    ) {
        $serviceVendor = ServiceVendor::where('nit', $rip->nit)->first();
        $codPrestador = $serviceVendor->ips_cod_habilitacion?->codigo;

        // leer JSON existente
        $existingJson = [];
        if (!empty($invoiceModel->path_json) && Storage::disk('public')->exists($invoiceModel->path_json)) {
            try {
                $existingJson = openFileJson($invoiceModel->path_json) ?: [];
            } catch (\Throwable $e) {
                Log::warning("No se pudo leer JSON existente ({$invoiceModel->path_json}): " . $e->getMessage());
                $existingJson = [];
            }
        }
        if (!isset($existingJson['usuarios']) || !is_array($existingJson['usuarios'])) $existingJson['usuarios'] = [];

        // service keys soportadas (usa claves en minúscula)
        $serviceKeys = ['consultas', 'procedimientos', 'medicamentos', 'urgencias', 'otrosservicios', 'hospitalizacion', 'reciennacidos'];

        // índice por documento en JSON
        $usuariosIndex = [];
        foreach ($existingJson['usuarios'] as $i => $u) {
            $nd = $u['numDocumentoIdentificacion'] ?? null;
            if ($nd) $usuariosIndex[$nd] = $i;
            if (!isset($existingJson['usuarios'][$i]['servicios']) || !is_array($existingJson['usuarios'][$i]['servicios'])) {
                $existingJson['usuarios'][$i]['servicios'] = [];
            }
            foreach ($serviceKeys as $sk) {
                if (!isset($existingJson['usuarios'][$i]['servicios'][$sk]) || !is_array($existingJson['usuarios'][$i]['servicios'][$sk])) {
                    $existingJson['usuarios'][$i]['servicios'][$sk] = [];
                }
            }
        }

        // mapa para relacionar posición JSON <-> id en BD
        $serviceDbIdMap = [];

        // cargar rip_invoice_user si se pasó id
        $ripInvoiceUser = null;
        if (!empty($ripInvoiceUserId)) {
            $ripInvoiceUser = DB::table('rip_invoice_users')->where('id', $ripInvoiceUserId)->first();
        }

        // Normalizamos typeService (front puede enviar 'Procedimientos' o 'procedimientos')
        $typeServiceNormalized = $typeService;

        // procesar cada servicio entrante
        foreach ($incomingServices as $svcRaw) {
            $svc = is_object($svcRaw) ? (array)$svcRaw : $svcRaw;

            // normalizar numAutoriacion -> numAutorizacion
            if (isset($svc['numAutoriacion']) && !isset($svc['numAutorizacion'])) {
                $svc['numAutorizacion'] = $svc['numAutoriacion'];
                unset($svc['numAutoriacion']);
            }

            // --- DETERMINAR userIndex (idéntico a tu lógica previa) ---
            $userIndex = null;
            if (!empty($ripInvoiceUser) && ($ripInvoiceUserId !== null)) {
                $nd = $ripInvoiceUser->numDocumentoIdentificacion ?? null;
                if ($nd !== null && isset($usuariosIndex[$nd])) {
                    $userIndex = $usuariosIndex[$nd];
                } else {
                    $newUser = [
                        'tipoDocumentoIdentificacion' => $ripInvoiceUser->tipoDocumentoIdentificacion ?? '',
                        'numDocumentoIdentificacion' => $ripInvoiceUser->numDocumentoIdentificacion ?? null,
                        'tipoUsuario' => $ripInvoiceUser->tipoUsuario ?? '',
                        'fechaNacimiento' => $ripInvoiceUser->fechaNacimiento ?? null,
                        'codSexo' => $ripInvoiceUser->codSexo ?? '',
                        'codPaisResidencia' => $ripInvoiceUser->codPaisResidencia ?? '',
                        'codMunicipioResidencia' => $ripInvoiceUser->codMunicipioResidencia ?? '',
                        'codZonaTerritorialResidencia' => $ripInvoiceUser->codZonaTerritorialResidencia ?? '',
                        'incapacidad' => $ripInvoiceUser->incapacidad ?? null,
                        'codPaisOrigen' => $ripInvoiceUser->codPaisOrigen ?? '',
                        'consecutivo' => $ripInvoiceUser->consecutivo ?? null,
                        'servicios' => []
                    ];
                    foreach ($serviceKeys as $sk) $newUser['servicios'][$sk] = [];
                    $existingJson['usuarios'][] = $newUser;
                    $userIndex = array_key_last($existingJson['usuarios']);
                    if (!empty($newUser['numDocumentoIdentificacion'])) {
                        $usuariosIndex[$newUser['numDocumentoIdentificacion']] = $userIndex;
                    }
                }
            } else {
                $numDoc = $svc['numDocumentoIdentificacion'] ?? null;
                if ($numDoc && isset($usuariosIndex[$numDoc])) {
                    $userIndex = $usuariosIndex[$numDoc];
                } else {
                    // buscar primer usuario sin documento
                    $found = false;
                    foreach ($existingJson['usuarios'] as $i => $u) {
                        if (empty($u['numDocumentoIdentificacion'])) {
                            $userIndex = $i;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $newUser = [
                            'tipoDocumentoIdentificacion' => $ripInvoiceUser->tipoDocumentoIdentificacion ?? ($svc['tipoDocumentoIdentificacion'] ?? ''),
                            'numDocumentoIdentificacion' => $ripInvoiceUser->numDocumentoIdentificacion ?? ($svc['numDocumentoIdentificacion'] ?? null),
                            'tipoUsuario' => $svc['tipoUsuario'] ?? '',
                            'fechaNacimiento' => $svc['fechaNacimiento'] ?? null,
                            'codSexo' => $svc['codSexo'] ?? '',
                            'codPaisResidencia' => $svc['codPaisResidencia'] ?? '',
                            'codMunicipioResidencia' => $svc['codMunicipioResidencia'] ?? '',
                            'codZonaTerritorialResidencia' => $svc['codZonaTerritorialResidencia'] ?? '',
                            'incapacidad' => $svc['incapacidad'] ?? null,
                            'codPaisOrigen' => $svc['codPaisOrigen'] ?? '',
                            'consecutivo' => null,
                            'servicios' => []
                        ];
                        foreach ($serviceKeys as $sk) $newUser['servicios'][$sk] = [];
                        $existingJson['usuarios'][] = $newUser;
                        $userIndex = array_key_last($existingJson['usuarios']);
                        if (!empty($newUser['numDocumentoIdentificacion'])) {
                            $usuariosIndex[$newUser['numDocumentoIdentificacion']] = $userIndex;
                        }
                    }
                }
            }
            // --- FIN userIndex ---
            // normalizar llave a minúscula para coincidir con config
            $serviceType = mb_strtolower($typeService);

            if (!isset($existingJson['usuarios'][$userIndex]['servicios']) || !is_array($existingJson['usuarios'][$userIndex]['servicios'])) {
                $existingJson['usuarios'][$userIndex]['servicios'] = [];
            }
            foreach ($serviceKeys as $sk) {
                if (!isset($existingJson['usuarios'][$userIndex]['servicios'][$sk]) || !is_array($existingJson['usuarios'][$userIndex]['servicios'][$sk])) {
                    $existingJson['usuarios'][$userIndex]['servicios'][$sk] = [];
                }
            }

            // --------- BORRADO si delete=true ----------
            $shouldDelete = false;
            if (isset($svc['delete'])) {
                $val = filter_var($svc['delete'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $shouldDelete = ($val === true);
            }
            if ($shouldDelete) {
                $table = ServiceMapper::tableFor($serviceType);
                $incomingId = $svc['id'] ?? null;
                try {
                    if (!empty($incomingId)) {
                        DB::table($table)->where('id', $incomingId)->delete();
                    } else {
                        $numAut = $svc['numAutorizacion'] ?? null;
                        if (!empty($numAut) && !empty($ripInvoiceUserId)) {
                            DB::table($table)->where('rip_invoice_user_id', $ripInvoiceUserId)->where('numAutorizacion', $numAut)->delete();
                        } elseif (!empty($svc['consecutivo']) && !empty($ripInvoiceUserId)) {
                            DB::table($table)->where('rip_invoice_user_id', $ripInvoiceUserId)->where('consecutivo', (int)$svc['consecutivo'])->delete();
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error("Error borrando en tabla {$table} por delete flag: " . $e->getMessage());
                }

                // quitar del JSON
                $removed = false;
                $con = $svc['consecutivo'] ?? null;
                $numAut = $svc['numAutorizacion'] ?? null;
                foreach ($existingJson['usuarios'][$userIndex]['servicios'][$serviceType] as $k => $entry) {
                    if ($con !== null && isset($entry['consecutivo']) && $entry['consecutivo'] == (int)$con) {
                        unset($existingJson['usuarios'][$userIndex]['servicios'][$serviceType][$k]);
                        $removed = true;
                        break;
                    }
                    if (!$removed && $numAut !== null && isset($entry['numAutorizacion']) && $entry['numAutorizacion'] == $numAut) {
                        unset($existingJson['usuarios'][$userIndex]['servicios'][$serviceType][$k]);
                        $removed = true;
                        break;
                    }
                }
                $existingJson['usuarios'][$userIndex]['servicios'][$serviceType] = array_values($existingJson['usuarios'][$userIndex]['servicios'][$serviceType]);
                if (isset($serviceDbIdMap[$userIndex][$serviceType]) && $removed) {
                    $serviceDbIdMap[$userIndex][$serviceType] = array_values($serviceDbIdMap[$userIndex][$serviceType]);
                }
                continue;
            }

            // ---------- PREPARAR payload DB mediante ServiceMapper (centralizado por tipo) ----------
            $dbPayload = ServiceMapper::buildDbPayload($serviceType, $svc, $ripInvoiceUser, $codPrestador, $invoiceModel);

            // elegir tabla según serviceType
            $table = ServiceMapper::tableFor($serviceType);

            // insert/update (no asignamos consecutivo aún)
            $incomingId = $svc['id'] ?? null;
            if (!empty($incomingId)) {
                $exists = DB::table($table)->where('id', $incomingId)->first();
                if ($exists) {
                    DB::table($table)->where('id', $incomingId)->update($dbPayload);
                    $dbId = $incomingId;
                } else {
                    $dbId = (string) Str::uuid();
                    $dbPayload['id'] = $dbId;
                    $dbPayload['consecutivo'] = null;
                    DB::table($table)->insert($dbPayload);
                }
            } else {
                $dbId = (string) Str::uuid();
                $dbPayload['id'] = $dbId;
                $dbPayload['consecutivo'] = null;
                DB::table($table)->insert($dbPayload);
            }

            // construir JSON mínimo para lista (sin consecutivo)
            $jsonSvc = ServiceMapper::formatForJson($dbPayload, $serviceType);
            // garantizar consecutivo nulo por ahora si no lo tienes
            if (!array_key_exists('consecutivo', $jsonSvc) || $jsonSvc['consecutivo'] === null) {
                $jsonSvc['consecutivo'] = $dbPayload['consecutivo'] ?? null;
            }

            // reemplazar por consecutivo o numAutorizacion, si no append
            $replaced = false;
            foreach ($existingJson['usuarios'][$userIndex]['servicios'][$serviceType] as $k => $entry) {
                if (!empty($entry['consecutivo']) && !empty($svc['consecutivo']) && $entry['consecutivo'] == (int)$svc['consecutivo']) {
                    $existingJson['usuarios'][$userIndex]['servicios'][$serviceType][$k] = $jsonSvc;
                    $serviceDbIdMap[$userIndex][$serviceType][$k] = $dbId;
                    $replaced = true;
                    break;
                }
                if (!$replaced && !empty($svc['numAutorizacion']) && !empty($entry['numAutorizacion']) && $entry['numAutorizacion'] == $svc['numAutorizacion']) {
                    $existingJson['usuarios'][$userIndex]['servicios'][$serviceType][$k] = $jsonSvc;
                    $serviceDbIdMap[$userIndex][$serviceType][$k] = $dbId;
                    $replaced = true;
                    break;
                }
            }
            if (!$replaced) {
                $existingJson['usuarios'][$userIndex]['servicios'][$serviceType][] = $jsonSvc;
                $serviceDbIdMap[$userIndex][$serviceType][] = $dbId;
            }

            // rellenar datos del usuario si disponemos de ripInvoiceUser
            if ($ripInvoiceUser) {
                $existingJson['usuarios'][$userIndex]['tipoDocumentoIdentificacion'] = $ripInvoiceUser->tipoDocumentoIdentificacion ?? $existingJson['usuarios'][$userIndex]['tipoDocumentoIdentificacion'] ?? '';
                $existingJson['usuarios'][$userIndex]['numDocumentoIdentificacion'] = $ripInvoiceUser->numDocumentoIdentificacion ?? $existingJson['usuarios'][$userIndex]['numDocumentoIdentificacion'] ?? null;
                $existingJson['usuarios'][$userIndex]['tipoUsuario'] = $ripInvoiceUser->tipoUsuario ?? $existingJson['usuarios'][$userIndex]['tipoUsuario'] ?? '';
                $existingJson['usuarios'][$userIndex]['fechaNacimiento'] = $ripInvoiceUser->fechaNacimiento ?? $existingJson['usuarios'][$userIndex]['fechaNacimiento'] ?? null;
                $existingJson['usuarios'][$userIndex]['codSexo'] = $ripInvoiceUser->codSexo ?? $existingJson['usuarios'][$userIndex]['codSexo'] ?? '';
                $existingJson['usuarios'][$userIndex]['codPaisResidencia'] = $ripInvoiceUser->codPaisResidencia ?? $existingJson['usuarios'][$userIndex]['codPaisResidencia'] ?? '';
                $existingJson['usuarios'][$userIndex]['codMunicipioResidencia'] = $ripInvoiceUser->codMunicipioResidencia ?? $existingJson['usuarios'][$userIndex]['codMunicipioResidencia'] ?? '';
                $existingJson['usuarios'][$userIndex]['codZonaTerritorialResidencia'] = $ripInvoiceUser->codZonaTerritorialResidencia ?? $existingJson['usuarios'][$userIndex]['codZonaTerritorialResidencia'] ?? '';
                $existingJson['usuarios'][$userIndex]['incapacidad'] = $ripInvoiceUser->incapacidad ?? $existingJson['usuarios'][$userIndex]['incapacidad'] ?? null;
                $existingJson['usuarios'][$userIndex]['codPaisOrigen'] = $ripInvoiceUser->codPaisOrigen ?? $existingJson['usuarios'][$userIndex]['codPaisOrigen'] ?? '';
            }
        } // end foreach incomingServices

        // ---------- REENUMERAR consecutivos para cada usuario y serviceType (SIEMPRE 1..N) ----------
        foreach ($existingJson['usuarios'] as $uIdx => &$user) {
            foreach ($serviceKeys as $sk) {
                if (!isset($user['servicios'][$sk]) || !is_array($user['servicios'][$sk])) {
                    $user['servicios'][$sk] = [];
                }
                $pos = 0;
                foreach ($user['servicios'][$sk] as $oldIndex => $svcEntry) {
                    $pos++;
                    $user['servicios'][$sk][$oldIndex]['consecutivo'] = $pos;

                    // actualizar DB usando el id mapeado (si existe)
                    $dbId = $serviceDbIdMap[$uIdx][$sk][$oldIndex] ?? null;
                    $table = ServiceMapper::tableFor($sk);
                    if ($dbId) {
                        try {
                            DB::table($table)->where('id', $dbId)->update(['consecutivo' => $pos, 'updated_at' => now()]);
                        } catch (\Throwable $e) {
                            Log::warning("No se pudo actualizar consecutivo DB para id {$dbId} en tabla {$table}: " . $e->getMessage());
                        }
                    } else {
                        // fallback por numAutorizacion
                        $numAut = $svcEntry['numAutorizacion'] ?? null;
                        $ripUserId = $ripInvoiceUserId ?? null;
                        if (!empty($numAut) && !empty($ripUserId)) {
                            try {
                                DB::table($table)
                                    ->where('rip_invoice_user_id', $ripUserId)
                                    ->where('numAutorizacion', $numAut)
                                    ->update(['consecutivo' => $pos, 'updated_at' => now()]);
                            } catch (\Throwable $e) {
                                Log::warning("No se pudo fallback-update consecutivo por numAutorizacion {$numAut} en tabla {$table}: " . $e->getMessage());
                            }
                        }
                    }
                }
                $user['servicios'][$sk] = array_values($user['servicios'][$sk]);
                if (empty($user['servicios'][$sk])) {
                    unset($user['servicios'][$sk]);
                }
            }

            // asegurar consecutivo global del usuario
            if (empty($user['consecutivo'])) {
                $maxUserCon = 0;
                foreach ($existingJson['usuarios'] as $uu) {
                    if (!empty($uu['consecutivo']) && is_numeric($uu['consecutivo'])) $maxUserCon = max($maxUserCon, (int)$uu['consecutivo']);
                }
                $user['consecutivo'] = $maxUserCon + 1;
            }
        }
        unset($user);

        // --- REORDENAR servicios por usuario y eliminar keys vacías ---
        foreach ($existingJson['usuarios'] as $i => $user) {
            // Si no tiene 'servicios' saltamos
            if (!isset($existingJson['usuarios'][$i]['servicios']) || !is_array($existingJson['usuarios'][$i]['servicios'])) {
                continue;
            }

            // Reordena las keys de servicios según el orden definido en ServiceMapper
            $existingJson['usuarios'][$i]['servicios'] = ServiceMapper::reorderServices($existingJson['usuarios'][$i]['servicios']);

            // Si tras reordenar quedó vacío, eliminar la key 'servicios'
            if (empty($existingJson['usuarios'][$i]['servicios'])) {
                unset($existingJson['usuarios'][$i]['servicios']);
            }
        }

        // limpiar usuarios que tengan 'servicios' vacíos o no tengan 'servicios'
        foreach ($existingJson['usuarios'] as $i => $u) {
            if (!isset($existingJson['usuarios'][$i]['servicios']) || count($existingJson['usuarios'][$i]['servicios']) == 0) {
                // si no tiene servicios, remueve la key (ya fue manejado) y si quieres eliminar usuarios sin servicios: descomenta la línea siguiente
                // unset($existingJson['usuarios'][$i]);
                // En tu caso original sólo eliminabas la key 'servicios', así que dejamos tal cual.
                unset($existingJson['usuarios'][$i]['servicios']);
            }
        }

        // reindexar usuarios (asegura índices 0..N-1)
        $existingJson['usuarios'] = array_values($existingJson['usuarios']);

        // transformar keys de 'servicios' por usuario para el JSON final
        foreach ($existingJson['usuarios'] as $i => $user) {
            if (isset($existingJson['usuarios'][$i]['servicios']) && is_array($existingJson['usuarios'][$i]['servicios'])) {
                $existingJson['usuarios'][$i]['servicios'] = self::mapServiceKeysForJson($existingJson['usuarios'][$i]['servicios']);
            }
        }

        // ---------- Guardar JSON y actualizar invoice ----------
        $infoJson = [
            'numDocumentoIdObligado' => $existingJson['numDocumentoIdObligado'] ?? $rip->nit ?? null,
            'numFactura' => $existingJson['numFactura'] ?? $invoiceModel->invoice_number ?? null,
            'tipoNota' => $existingJson['tipoNota'] ?? $invoiceModel->tipoNota ?? null,
            'numNota' => $existingJson['numNota'] ?? $invoiceModel->numNota ?? null,
            'usuarios' => $existingJson['usuarios'],
        ];

        $numFactura = $infoJson['numFactura'] ?? 'factura_' . ($invoiceModel->id ?? Str::uuid());
        $nameFile = $numFactura . '.json';
        $ruta = 'companies/company_' . $company_id . '/rips/' . $type . '/rip_' . $rip->id . '/invoices/' . $numFactura . '/' . $nameFile;

        Storage::disk('public')->makeDirectory(dirname($ruta));
        Storage::disk('public')->put($ruta, json_encode($infoJson, JSON_UNESCAPED_UNICODE));

        try {
            if (!empty($invoiceModel->path_json) && $invoiceModel->path_json !== $ruta && Storage::disk('public')->exists(dirname($invoiceModel->path_json))) {
                Storage::disk('public')->deleteDirectory(dirname($invoiceModel->path_json));
            }
        } catch (\Throwable $e) {
            Log::warning("No se pudo eliminar carpeta antigua de invoice {$invoiceModel->id}: " . $e->getMessage());
        }

        $invoiceModel->path_json = $ruta;
        $invoiceModel->count_users = count($existingJson['usuarios']);
        $invoiceModel->save();

        GenerateRipInfo::generateDataJsonAndExcel($invoiceModel->rip_id, $type);

        return $invoiceModel->fresh();
    }

    private static function mapServiceKeysForJson(array $servicios): array
    {
        $map = [
            'reciennacidos' => 'recienNacidos',
            'otrosservicios' => 'otrosServicios',
        ];

        $out = [];
        foreach ($servicios as $k => $v) {
            if (isset($map[$k])) {
                $out[$map[$k]] = $v;
            } else {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}
