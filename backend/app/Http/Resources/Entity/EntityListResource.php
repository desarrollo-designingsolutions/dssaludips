<?php

namespace App\Http\Resources\Entity;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityListResource extends JsonResource
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
            'corporate_name' => $this->corporate_name,
            'nit' => $this->nit,
            'email' => $this->email,
            'type_entity_name' => $this->typeEntity?->name,
            'is_active' => $this->is_active,
        ];
    }
}
