<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
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
            'protocol' => $this->protocol,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Inclui as relações carregadas, usando os Resources dependentes
            'user' => new UserResource($this->whenLoaded('user')),
            'type_request' => new TypeRequestResource($this->whenLoaded('typeRequest')),
        ];
    }
}
