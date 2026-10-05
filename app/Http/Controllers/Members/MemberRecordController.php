<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Http\Requests\Members\WorkoutPlanRequest;
use App\Models\Member;
use App\Models\MemberDocument;
use App\Models\MemberNote;
use App\Models\ProgressRecord;
use App\Models\WorkoutPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Coaching records on a member: notes, documents, progress measurements and workout plans.
 */
class MemberRecordController extends Controller
{
    public function storeNote(Request $request, Member $member): RedirectResponse
    {
        $this->authorize('coach', $member);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);

        $member->memberNotes()->create(['body' => $data['body'], 'user_id' => $request->user()->id]);

        return $this->done(redirect()->to(route('members.show', $member).'#notes'), 'Note added.');
    }

    public function destroyNote(Request $request, Member $member, MemberNote $note): RedirectResponse
    {
        $this->authorize('coach', $member);
        abort_unless($note->member_id === $member->id && ($note->user_id === $request->user()->id || $request->user()->can('update', $member)), 403);

        $note->delete();

        return $this->done(back(), 'Note deleted.');
    }

    public function storeDocument(Request $request, Member $member): RedirectResponse
    {
        $this->authorize('update', $member);
        $data = $request->validate([
            'document' => ['required', 'file', 'mimes:'.implode(',', config('gym.documents.mimes')), 'max:'.config('gym.documents.max_kb')],
            'name' => ['nullable', 'string', 'max:150'],
        ]);

        $file = $data['document'];
        $member->documents()->create([
            'uploaded_by' => $request->user()->id,
            'name' => $data['name'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => $file->store('tenants/'.$member->tenant_id.'/documents', 'local'),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        return $this->done(redirect()->to(route('members.show', $member).'#documents'), 'Document uploaded.');
    }

    public function downloadDocument(Member $member, MemberDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);
        abort_unless($document->member_id === $member->id, 404);

        $extension = pathinfo($document->path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($document->path, str($document->name)->slug().'.'.$extension);
    }

    public function destroyDocument(Member $member, MemberDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);
        abort_unless($document->member_id === $member->id, 404);

        $document->delete();

        return $this->done(back(), 'Document deleted.');
    }

    public function storeProgress(Request $request, Member $member): RedirectResponse
    {
        $this->authorize('coach', $member);

        $rules = ['recorded_on' => ['required', 'date', 'before_or_equal:today'], 'notes' => ['nullable', 'string', 'max:1000']];
        foreach (array_keys(ProgressRecord::MEASUREMENTS) as $field) {
            $rules[$field] = ['nullable', 'numeric', 'min:0', 'max:1000'];
        }
        $data = $request->validate($rules);

        abort_if(collect($data)->only(array_keys(ProgressRecord::MEASUREMENTS))->filter(fn ($value) => $value !== null)->isEmpty(), 422, 'Enter at least one measurement.');

        $member->progressRecords()->create($data + ['recorded_by' => $request->user()->id]);

        return $this->done(redirect()->to(route('members.show', $member).'#progress'), 'Progress recorded.');
    }

    public function destroyProgress(Member $member, ProgressRecord $record): RedirectResponse
    {
        $this->authorize('coach', $member);
        abort_unless($record->member_id === $member->id, 404);

        $record->delete();

        return $this->done(back(), 'Measurement removed.');
    }

    public function createWorkout(Member $member): View
    {
        $this->authorize('coach', $member);

        return view('workouts.form', ['member' => $member, 'plan' => new WorkoutPlan(['is_active' => true, 'starts_on' => tenant_today(), 'exercises' => []])]);
    }

    public function storeWorkout(WorkoutPlanRequest $request, Member $member): RedirectResponse
    {
        $member->workoutPlans()->create($request->validated() + ['trainer_id' => $request->user()->id]);

        return $this->done(redirect()->to(route('members.show', $member).'#workouts'), 'Workout plan created.');
    }

    public function showWorkout(Member $member, WorkoutPlan $workout): View
    {
        $this->authorize('view', $member);
        abort_unless($workout->member_id === $member->id, 404);

        return view('workouts.show', ['member' => $member, 'plan' => $workout->load('trainer')]);
    }

    public function editWorkout(Member $member, WorkoutPlan $workout): View
    {
        $this->authorize('update', $workout);
        abort_unless($workout->member_id === $member->id, 404);

        return view('workouts.form', ['member' => $member, 'plan' => $workout]);
    }

    public function updateWorkout(WorkoutPlanRequest $request, Member $member, WorkoutPlan $workout): RedirectResponse
    {
        $this->authorize('update', $workout);
        abort_unless($workout->member_id === $member->id, 404);

        $workout->update($request->validated());

        return $this->done(redirect()->to(route('members.show', $member).'#workouts'), 'Workout plan updated.');
    }

    public function destroyWorkout(Member $member, WorkoutPlan $workout): RedirectResponse
    {
        $this->authorize('delete', $workout);
        abort_unless($workout->member_id === $member->id, 404);

        $workout->delete();

        return $this->done(back(), 'Workout plan deleted.');
    }
}
