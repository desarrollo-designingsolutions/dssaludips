<?php

namespace App\Http\Resources\RipInvoiceUser;

use App\Http\Resources\Municipio\MunicipioSelectResource;
use App\Http\Resources\Pais\PaisSelectResource;
use App\Http\Resources\RipsTipoUsuarioVersion2\RipsTipoUsuarioVersion2SelectResource;
use App\Http\Resources\Sexo\SexoSelectResource;
use App\Http\Resources\TipoIdPisis\TipoIdPisisSelectResource;
use App\Http\Resources\ZonaVersion2\ZonaVersion2SelectResource;
use App\Models\Municipio;
use App\Models\Pais;
use App\Models\RipsTipoUsuarioVersion2;
use App\Models\Sexo;
use App\Models\TipoIdPisis;
use App\Models\ZonaVersion2;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class RipInvoiceUserPaginateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consecutivo' => $this->consecutivo,
            'tipoDocumentoIdentificacion' => $this->tipoDocumentoIdentificacion ? new TipoIdPisisSelectResource($this->tipoDocumento) : '',
            'numDocumentoIdentificacion' => $this->numDocumentoIdentificacion,
            'tipoUsuario' => $this->tipoUsuario ? new RipsTipoUsuarioVersion2SelectResource($this->tipoUsuarioRelation) : '',
            'fechaNacimiento' => $this->fechaNacimiento,
            'codSexo' => $this->codSexo ? new SexoSelectResource($this->sexoRelation) : '',
            'codPaisResidencia' => $this->codPaisResidencia ? new PaisSelectResource($this->paisResidencia) : '',
            'codMunicipioResidencia' => $this->codMunicipioResidencia ? new MunicipioSelectResource($this->municipioResidencia) : '',
            'codZonaTerritorialResidencia' => $this->codZonaTerritorialResidencia ? new ZonaVersion2SelectResource($this->zonaResidencia) : '',
            'incapacidad' => $this->incapacidad,
            'codPaisOrigen' => $this->codPaisOrigen ? new PaisSelectResource($this->paisOrigen) : '',
        ];
    }
}
