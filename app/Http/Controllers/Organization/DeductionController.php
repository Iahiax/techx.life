<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeductionController extends Controller
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

        $query = Deduction::where('org_id', $org->id)
            ->with(['customer', 'contract']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->date_to);
        }

        $deductions = $query->latest('scheduled_date')->paginate(20);

        return view('organization.deductions.index', compact('deductions'));
    }

    public function show($id)
    {
        $org = $this->getOrganization();
        if (!$org) abort(403);

        $deduction = Deduction::where('org_id', $org->id)
            ->with(['customer', 'contract', 'sourceAccount'])
            ->findOrFail($id);

        return view('organization.deductions.show', compact('deduction'));
    }
}
