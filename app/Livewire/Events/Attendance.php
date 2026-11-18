<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Member;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Attendance extends Component
{
    public Event $event;

    public string $searchMember = '';

    public string $qrResult = '';

    public function mount(Event $event): void
    {
        Gate::authorize('view', $event);
        $this->event = $event;
    }

    public function getAttendanceProperty()
    {
        return $this->event->attendance()
            ->with('member')
            ->orderBy('status')
            ->get();
    }

    public function inviteBySearch(): void
    {
        Gate::authorize('manageAttendance', $this->event);

        $this->validate(['searchMember' => 'required|string|min:2']);

        $members = Member::where(function ($q) {
            $q->where('first_name', 'like', '%'.$this->searchMember.'%')
                ->orWhere('last_name', 'like', '%'.$this->searchMember.'%')
                ->orWhere('membership_no', $this->searchMember);
        })->limit(50)->get();

        foreach ($members as $member) {
            EventAttendance::firstOrCreate([
                'event_id' => $this->event->id,
                'member_id' => $member->id,
            ], ['status' => 'invited']);
        }

        $this->searchMember = '';
        session()->flash('success', __(':n member(s) invited.', ['n' => $members->count()]));
    }

    public function markAttended(string $memberId): void
    {
        Gate::authorize('manageAttendance', $this->event);

        EventAttendance::updateOrCreate(
            ['event_id' => $this->event->id, 'member_id' => $memberId],
            ['status' => 'attended']
        );
    }

    public function markAbsent(string $memberId): void
    {
        Gate::authorize('manageAttendance', $this->event);

        EventAttendance::updateOrCreate(
            ['event_id' => $this->event->id, 'member_id' => $memberId],
            ['status' => 'absent']
        );
    }

    public function handleQrScan(string $membershipNo): void
    {
        Gate::authorize('manageAttendance', $this->event);

        // Strip any URL prefix — accept full verify URL or bare membership number
        $membershipNo = preg_replace('/^.*\/verify\/([^\/\?]+).*$/', '$1', $membershipNo);

        $member = Member::withoutGlobalScopes()
            ->where('membership_no', $membershipNo)
            ->first();

        if (! $member) {
            $this->dispatch('qr-error', message: __('Member not found: :no', ['no' => $membershipNo]));

            return;
        }

        EventAttendance::updateOrCreate(
            ['event_id' => $this->event->id, 'member_id' => $member->id],
            ['status' => 'attended']
        );

        $this->dispatch('qr-success', name: $member->first_name);
    }

    public function render()
    {
        return view('livewire.events.attendance', [
            'attendance' => $this->attendance,
            'attendanceRate' => $this->event->attendanceRate(),
        ]);
    }
}
