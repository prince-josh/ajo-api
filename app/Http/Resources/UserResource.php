<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'full_name'      => $this->full_name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'account_number' => $this->account_number,
            'role'           => $this->role,
            'avatar'         => $this->avatar,
            'address'        => $this->address,
            'state'          => $this->state,
            'is_active'      => $this->is_active,
            'is_verified'    => $this->is_verified,
            'wallet'         => $this->whenLoaded('wallet', fn() => new WalletResource($this->wallet)),
            'created_at'     => $this->created_at->toDateTimeString(),
        ];
    }
}
