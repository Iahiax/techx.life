<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeductionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('customer');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Deduction::where('customer_id', $user->id)
            ->with('organization');

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

        return view('customer.deductions.index', compact('deductions'));
    }

    public function show($id)
    {
        $user = Auth::user();

        $deduction = Deduction::where('customer_id', $user->id)
            ->with(['organization', 'contract', 'sourceAccount'])
            ->findOrFail($id);

        return view('customer.deductions.show', compact('deduction'));
    }
}
