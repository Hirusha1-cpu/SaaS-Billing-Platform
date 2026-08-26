<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'billing_cycle' => $this->billing_cycle,
            'billing_cycle_label' => $this->getBillingCycleLabel(),
            'billing_period' => $this->billing_period,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'next_billing_date' => $this->next_billing_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'trial_ends_at' => $this->trial_ends_at?->format('Y-m-d'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'is_active' => (bool) $this->is_active,
            'meta_data' => $this->meta_data,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}