<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'reference'           => $this->reference,
            'type'                => $this->type,
            'category'            => $this->category,
            'amount'              => number_format($this->amount, 2),
            'balance_before'      => number_format($this->balance_before, 2),
            'balance_after'       => number_format($this->balance_after, 2),
            'status'              => $this->status,
            'description'         => $this->description,
            'paystack_reference'  => $this->paystack_reference,
            'created_at'          => $this->created_at->toDateTimeString(),
        ];
    }
}
