<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftCardResource extends JsonResource
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
            'code' => $this->code,
            'initial_balance' => $this->initial_balance,
            'current_balance' => $this->current_balance,
            'currency' => $this->currency,
            'expires_at' => $this->expires_at,
            'is_active' => $this->is_active,
            'recipient_email' => $this->recipient_email,
            'recipient_name' => $this->recipient_name,
            'message' => $this->message,
            'purchased_by' => $this->purchased_by,
            'purchased_user' => $this->whenLoaded('purchasedUser', function () {
                return [
                    'id' => $this->purchasedUser->id,
                    'name' => $this->purchasedUser->name,
                ];
            }),
            'used_by' => $this->used_by,
            'used_user' => $this->whenLoaded('usedUser', function () {
                return [
                    'id' => $this->usedUser->id,
                    'name' => $this->usedUser->name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}