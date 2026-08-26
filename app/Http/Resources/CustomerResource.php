<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'tax_id' => $this->tax_id,
            'company_name' => $this->company_name,
            'website' => $this->website,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'total_invoices' => $this->whenCounted('invoices'),
            'total_paid' => (float) $this->payments_sum_amount ?? 0,
            'outstanding_balance' => $this->when(
                $this->relationLoaded('invoices'),
                function() {
                    return $this->invoices()
                        ->whereNotIn('status', ['paid', 'cancelled', 'refunded'])
                        ->sum('balance_due');
                }
            ),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}