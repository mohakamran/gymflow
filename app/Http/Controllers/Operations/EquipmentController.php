<?php

namespace App\Http\Controllers\Operations;

use App\Enums\EquipmentCategory;
use App\Enums\EquipmentCondition;
use App\Enums\EquipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentMaintenance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::enum(EquipmentCategory::class)],
            'status' => ['nullable', Rule::enum(EquipmentStatus::class)],
            'maintenance' => ['nullable', Rule::in(['due'])],
        ]);

        $equipment = Equipment::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($inner) => $inner->whereLike('name', "%{$search}%")->orWhereLike('serial_number', "%{$search}%")->orWhereLike('location', "%{$search}%")))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when(isset($filters['maintenance']), fn ($query) => $query->maintenanceDue())
            ->orderByRaw('case when next_maintenance_on is null then 1 else 0 end')
            ->orderBy('next_maintenance_on')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('equipment.index', [
            'equipment' => $equipment,
            'filters' => $filters,
            'statusCounts' => Equipment::query()->toBase()->selectRaw('status, sum(quantity) as total')->groupBy('status')->pluck('total', 'status'),
            'dueCount' => Equipment::query()->maintenanceDue()->count(),
            'assetValue' => (float) Equipment::query()->whereNot('status', EquipmentStatus::Retired->value)->sum(DB::raw('coalesce(cost, 0) * quantity')),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Equipment::class);

        return view('equipment.form', ['item' => new Equipment(['quantity' => 1, 'status' => EquipmentStatus::Active, 'condition' => EquipmentCondition::New])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Equipment::class);
        $item = Equipment::create($this->validated($request));

        return $this->done(redirect()->route('equipment.show', $item), "{$item->name} added to inventory.");
    }

    public function show(Equipment $equipment): View
    {
        $this->authorize('view', $equipment);

        return view('equipment.show', ['item' => $equipment->load(['maintenances' => fn ($query) => $query->latest('performed_on')]), 'types' => EquipmentMaintenance::TYPES]);
    }

    public function edit(Equipment $equipment): View
    {
        $this->authorize('update', $equipment);

        return view('equipment.form', ['item' => $equipment]);
    }

    public function update(Request $request, Equipment $equipment): RedirectResponse
    {
        $this->authorize('update', $equipment);
        $equipment->update($this->validated($request) + ['maintenance_reminder_sent_at' => null]);

        return $this->done(redirect()->route('equipment.show', $equipment), 'Equipment updated.');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        $this->authorize('delete', $equipment);
        $equipment->delete();

        return $this->done(redirect()->route('equipment.index'), "{$equipment->name} removed from inventory.");
    }

    public function logMaintenance(Request $request, Equipment $equipment): RedirectResponse
    {
        $this->authorize('update', $equipment);

        $data = $request->validate([
            'performed_on' => ['required', 'date', 'before_or_equal:today'],
            'type' => ['required', Rule::in(array_keys(EquipmentMaintenance::TYPES))],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'performed_by' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'next_maintenance_on' => ['nullable', 'date', 'after:performed_on'],
            'status' => ['required', Rule::enum(EquipmentStatus::class)],
            'condition' => ['required', Rule::enum(EquipmentCondition::class)],
        ]);

        DB::transaction(function () use ($equipment, $data, $request): void {
            $equipment->maintenances()->create(collect($data)->only(['performed_on', 'type', 'cost', 'performed_by', 'notes'])->all() + ['recorded_by' => $request->user()->id]);
            $equipment->update([
                'last_maintained_on' => $data['performed_on'],
                'next_maintenance_on' => $data['next_maintenance_on'] ?? null,
                'status' => $data['status'],
                'condition' => $data['condition'],
                'maintenance_reminder_sent_at' => null,
            ]);
        });

        return $this->done(back(), 'Maintenance logged.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::enum(EquipmentCategory::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'purchased_on' => ['nullable', 'date', 'before_or_equal:today'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'condition' => ['required', Rule::enum(EquipmentCondition::class)],
            'status' => ['required', Rule::enum(EquipmentStatus::class)],
            'next_maintenance_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
