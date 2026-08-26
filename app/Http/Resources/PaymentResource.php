<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->getMethodLabel(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'transaction_id' => $this->transaction_id,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'stripe_charge_id' => $this->stripe_charge_id,
            'receipt_url' => $this->receipt_url,
            'payment_date' => $this->payment_date?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}