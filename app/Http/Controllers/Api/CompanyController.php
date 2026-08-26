<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Http\Requests\CompanyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Display company details
     */
    public function show()
    {
        $company = Auth::user()->company;
        
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        return response()->json([
            'company' => $company,
            'settings' => $company->settings,
        ]);
    }

    /**
     * Update company details
     */
    public function update(Request $request)
    {
        $company = Auth::user()->company;

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        Gate::authorize('update', $company);

        $validator = validator($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:companies,email,' . $company->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|size:3',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->except(['logo']);

            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo
                if ($company->logo) {
                    Storage::delete($company->logo);
                }
                
                $path = $request->file('logo')->store('company-logos', 'public');
                $data['logo'] = $path;
            }

            $company->update($data);

            DB::commit();

            Log::info('Company updated successfully', [
                'company_id' => $company->id,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Company updated successfully',
                'company' => $company,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Company update failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get company settings
     */
    public function settings()
    {
        $company = Auth::user()->company;

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        return response()->json([
            'settings' => $company->settings ?? [],
            'tax_rate' => $company->tax_rate,
            'currency' => $company->currency,
            'default_settings' => $this->getDefaultSettings(),
        ]);
    }

    /**
     * Update company settings
     */
    public function updateSettings(Request $request)
    {
        $company = Auth::user()->company;

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        Gate::authorize('update', $company);

        $validator = validator($request->all(), [
            'settings' => 'nullable|array',
            'settings.invoice_prefix' => 'nullable|string|max:10',
            'settings.default_due_days' => 'nullable|integer|min:1|max:365',
            'settings.default_tax_rate' => 'nullable|numeric|min:0|max:100',
            'settings.default_currency' => 'nullable|string|size:3',
            'settings.invoice_notes' => 'nullable|string|max:500',
            'settings.invoice_terms' => 'nullable|string|max:500',
            'settings.email_notifications' => 'nullable|array',
            'settings.email_notifications.invoice_sent' => 'nullable|boolean',
            'settings.email_notifications.payment_received' => 'nullable|boolean',
            'settings.email_notifications.overdue_reminder' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $settings = array_merge(
                $company->settings ?? [],
                $request->settings ?? []
            );

            // Update tax rate and currency if provided
            if (isset($settings['default_tax_rate'])) {
                $company->tax_rate = $settings['default_tax_rate'];
            }

            if (isset($settings['default_currency'])) {
                $company->currency = $settings['default_currency'];
            }

            $company->settings = $settings;
            $company->save();

            DB::commit();

            Log::info('Company settings updated', [
                'company_id' => $company->id,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => $company->settings,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Company settings update failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get default settings
     */
    private function getDefaultSettings()
    {
        return [
            'invoice_prefix' => 'INV',
            'default_due_days' => 30,
            'default_tax_rate' => 15,
            'default_currency' => 'LKR',
            'invoice_notes' => 'Thank you for your business!',
            'invoice_terms' => 'Payment due within 30 days',
            'email_notifications' => [
                'invoice_sent' => true,
                'payment_received' => true,
                'overdue_reminder' => true,
            ],
        ];
    }

    /**
     * Get company statistics
     */
    public function getStats()
    {
        $company = Auth::user()->company;

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $stats = [
            'total_users' => $company->users()->count(),
            'total_customers' => $company->customers()->count(),
            'total_invoices' => $company->invoices()->count(),
            'total_payments' => $company->payments()->sum('amount'),
            'pending_invoices' => $company->invoices()
                ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
                ->count(),
            'overdue_invoices' => $company->invoices()
                ->where('status', 'overdue')
                ->count(),
            'total_revenue' => $company->invoices()
                ->where('status', 'paid')
                ->sum('total'),
            'outstanding_balance' => $company->invoices()
                ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
                ->sum('balance_due'),
        ];

        return response()->json($stats);
    }

    /**
     * Delete company logo
     */
    public function deleteLogo()
    {
        $company = Auth::user()->company;

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        Gate::authorize('update', $company);

        DB::beginTransaction();
        try {
            if ($company->logo) {
                Storage::delete($company->logo);
                $company->logo = null;
                $company->save();
            }

            DB::commit();

            return response()->json(['message' => 'Logo deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}