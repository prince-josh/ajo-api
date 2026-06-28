<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'balance'          => number_format($this->balance, 2),
            'total_saved'      => number_format($this->total_saved, 2),
            'total_withdrawn'  => number_format($this->total_withdrawn, 2),
            'is_locked'        => $this->is_locked,
            'updated_at'       => $this->updated_at->toDateTimeString(),
        ];
    }
}
