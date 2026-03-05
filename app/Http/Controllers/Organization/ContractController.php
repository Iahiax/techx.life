<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\User;
use App\Models\Consent;
use App\Models\Notification;
use Barryvdh\Dompdf\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('org-user');
    }

    private function getOrganization()
    {
        $user = Auth::user();
        return $user->organizations()->first();
    }

    public function index(Request $request)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $query = Contract::where('org_id', $org->id)->with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('national_id', 'like', "%{$search}%");
                  });
            });
        }

        $contracts = $query->latest()->paginate(15);
        return view('organization.contracts.index', compact('contracts'));
    }

    public function create()
    {
        return view('organization.contracts.create');
    }

    public function store(Request $request)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $validator = Validator::make($request->all(), [
            'customer_national_id' => 'required|string|exists:users,national_id',
            'contract_number' => 'required|string|unique:contracts,contract_number',
            'contract_type' => 'required|in:funding,leasing,government_fee,utility_bill,subscription,personal_loan,other',
            'principal_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'monthly_installment' => 'nullable|numeric|min:0',
            'installment_count' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'raw_contract_data' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $customer = User::where('national_id', $request->customer_national_id)
            ->where('type', 'customer')
            ->firstOrFail();

        $contract = Contract::create([
            'org_id' => $org->id,
            'customer_id' => $customer->id,
            'contract_number' => $request->contract_number,
            'contract_type' => $request->contract_type,
            'principal_amount' => $request->principal_amount,
            'total_amount' => $request->total_amount,
            'paid_amount' => 0,
            'remaining_amount' => $request->total_amount,
            'monthly_installment' => $request->monthly_installment,
            'installment_count' => $request->installment_count,
            'paid_installments' => 0,
            'remaining_installments' => $request->installment_count,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending_approval',
            'raw_contract_data' => $request->raw_contract_data,
        ]);

        Consent::create([
            'customer_id' => $customer->id,
            'org_id' => $org->id,
            'contract_id' => $contract->id,
            'consent_type' => $this->mapContractType($request->contract_type),
            'status' => 'pending',
        ]);

        Notification::create([
            'user_id' => $customer->id,
            'title' => 'عقد جديد بانتظار موافقتك',
            'body' => 'تم إنشاء عقد جديد من قبل ' . $org->name . ' برقم ' . $contract->contract_number,
            'type' => 'contract_approval',
            'contract_id' => $contract->id,
        ]);

        return redirect()->route('organization.contracts.show', $contract->id)
            ->with('success', 'تم إنشاء العقد بنجاح وهو بانتظار موافقة العميل.');
    }

    private function mapContractType($type)
    {
        $map = [
            'funding' => 'salary_deduction',
            'leasing' => 'salary_deduction',
            'government_fee' => 'general_billing',
            'utility_bill' => 'general_billing',
            'subscription' => 'account_deduction',
            'personal_loan' => 'salary_deduction',
            'other' => 'account_deduction',
        ];
        return $map[$type] ?? 'account_deduction';
    }

    public function show($id)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $contract = Contract::where('org_id', $org->id)
            ->with(['customer', 'deductions'])
            ->findOrFail($id);

        return view('organization.contracts.show', compact('contract'));
    }

    public function edit($id)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $contract = Contract::where('org_id', $org->id)
            ->where('status', 'pending_approval')
            ->findOrFail($id);

        return view('organization.contracts.edit', compact('contract'));
    }

    public function update(Request $request, $id)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $contract = Contract::where('org_id', $org->id)
            ->where('status', 'pending_approval')
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'total_amount' => 'sometimes|numeric|min:0',
            'monthly_installment' => 'sometimes|numeric|min:0',
            'installment_count' => 'sometimes|integer|min:1',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'raw_contract_data' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->has('total_amount')) {
            $contract->total_amount = $request->total_amount;
            $contract->remaining_amount = $request->total_amount - $contract->paid_amount;
        }
        if ($request->has('monthly_installment')) {
            $contract->monthly_installment = $request->monthly_installment;
        }
        if ($request->has('installment_count')) {
            $contract->installment_count = $request->installment_count;
            $contract->remaining_installments = $request->installment_count - $contract->paid_installments;
        }
        if ($request->has('end_date')) {
            $contract->end_date = $request->end_date;
        }
        if ($request->has('raw_contract_data')) {
            $contract->raw_contract_data = $request->raw_contract_data;
        }
        $contract->save();

        return redirect()->route('organization.contracts.show', $contract->id)
            ->with('success', 'تم تحديث العقد بنجاح.');
    }

    public function destroy($id)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $contract = Contract::where('org_id', $org->id)
            ->where('status', 'pending_approval')
            ->findOrFail($id);

        $contract->delete();

        return redirect()->route('organization.contracts.index')
            ->with('success', 'تم حذف العقد بنجاح.');
    }

    public function printPdf($id)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $contract = Contract::where('org_id', $org->id)
            ->with('customer')
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.organization.contract', compact('contract'));
        return $pdf->download('عقد_' . $contract->contract_number . '.pdf');
    }

    public function deductions($id)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $contract = Contract::where('org_id', $org->id)->findOrFail($id);
        $deductions = $contract->deductions()->latest()->paginate(20);

        return view('organization.contracts.deductions', compact('contract', 'deductions'));
    }

    public function stopDeductions($id)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $contract = Contract::where('org_id', $org->id)->findOrFail($id);

        // تحديث الاستقطاعات المستقبلية المعلقة إلى failed
        Deduction::where('contract_id', $contract->id)
            ->where('status', 'pending')
            ->where('scheduled_date', '>=', now()->toDateString())
            ->update([
                'status' => 'failed',
                'failure_reason' => 'تم إيقاف الاستقطاع من قبل الجهة'
            ]);

        return redirect()->route('organization.contracts.show', $contract->id)
            ->with('success', 'تم إيقاف الاستقطاعات المستقبلية لهذا العقد.');
    }
}
