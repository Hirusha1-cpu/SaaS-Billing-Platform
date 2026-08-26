<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('company_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        $customers = $query->orderBy('name')->paginate(15);

        return CustomerResource::collection($customers);
    }

    public function store(CustomerRequest $request)
    {
        DB::beginTransaction();
        try {
            $customer = Customer::create($request->validated());

            DB::commit();

            return new CustomerResource($customer);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Customer $customer)
    {
        Gate::authorize('view', $customer);
        // $this->authorize('view', $customer);

        return new CustomerResource($customer->load(['invoices', 'payments']));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        Gate::authorize('update', $customer);

        DB::beginTransaction();
        try {
            $customer->update($request->validated());

            DB::commit();

            return new CustomerResource($customer);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Customer $customer)
    {
        Gate::authorize('delete', $customer);

        // Check if customer has invoices
        if ($customer->invoices()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete customer with invoices. Deactivate instead.'
            ], 403);
        }

        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }

    public function getStats(Customer $customer)
    {
        $stats = [
            'total_invoices' => $customer->invoices()->count(),
            'paid_invoices' => $customer->invoices()->where('status', 'paid')->count(),
            'total_paid' => $customer->payments()->where('status', 'completed')->sum('amount'),
            'outstanding_balance' => $customer->invoices()
                ->whereNotIn('status', ['paid', 'cancelled', 'refunded'])
                ->sum('balance_due'),
            'last_invoice' => $customer->invoices()->latest()->first(),
            'last_payment' => $customer->payments()->latest()->first(),
        ];

        return response()->json($stats);
    }

    public function getInvoices(Customer $customer, Request $request)
    {
        $invoices = $customer->invoices()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($invoices);
    }

    public function getPayments(Customer $customer, Request $request)
    {
        $payments = $customer->payments()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($payments);
    }

    public function bulkDelete(Request $request)
    {
        $validator = validator($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deleted = 0;
        foreach ($request->ids as $id) {
            $customer = Customer::find($id);
            if ($customer && $customer->invoices()->count() === 0) {
                $customer->delete();
                $deleted++;
            }
        }

        return response()->json([
            'message' => $deleted . ' customers deleted successfully',
            'deleted_count' => $deleted,
        ]);
    }
}