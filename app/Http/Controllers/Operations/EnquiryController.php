<?php

namespace App\Http\Controllers\Operations;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Leads captured from the public gym page.
 */
class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize(Permission::MembersManage);
        $status = $request->validate(['status' => ['nullable', Rule::in(array_keys(Enquiry::STATUSES))]])['status'] ?? null;

        return view('enquiries.index', [
            'enquiries' => Enquiry::query()->when($status, fn ($query) => $query->where('status', $status))->latest()->paginate(20)->withQueryString(),
            'status' => $status,
            'counts' => Enquiry::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry): RedirectResponse
    {
        Gate::authorize(Permission::MembersManage);
        $enquiry->update($request->validate(['status' => ['required', Rule::in(array_keys(Enquiry::STATUSES))]]));

        return $this->done(back(), 'Lead updated.');
    }
}
