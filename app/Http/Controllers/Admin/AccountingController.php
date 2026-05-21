<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function accounts(): View
    {
        Gate::authorize('viewAny', JournalEntry::class);

        $companyId = session('active_company_id');
        $accounts = \App\Models\AccountingAccount::where('company_id', $companyId)
            ->orderBy('code')
            ->paginate(30);

        return view('admin.accounting.accounts', compact('accounts'));
    }

    public function journal(Request $request): View
    {
        Gate::authorize('viewAny', JournalEntry::class);

        $companyId = session('active_company_id');
        $search = $request->query('search', '');

        $entries = JournalEntry::with(['creator', 'branch', 'lines.account'])
            ->where('company_id', $companyId)
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('description', 'like', "%{$search}%")->orWhere('entry_number', 'like', "%{$search}%")))
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.accounting.journal', compact('entries', 'search'));
    }

    public function showEntry(JournalEntry $entry): View
    {
        Gate::authorize('view', $entry);

        $entry->load(['lines.account', 'creator', 'branch']);

        $totalDebit = $entry->lines->sum('debit');
        $totalCredit = $entry->lines->sum('credit');

        return view('admin.accounting.entry', compact('entry', 'totalDebit', 'totalCredit'));
    }
}
