<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">{{ $member->fullName() }}</h1>
            <p class="text-gray-500 font-mono text-sm">{{ $member->membership_no ?? __('No membership number') }}</p>
        </div>
        <div class="flex gap-2">
            @can('update', $member)
                <a href="{{ route('members.edit', $member) }}" class="btn-secondary">{{ __('Edit') }}</a>
            @endcan
            @if ($member->membership_no)
                <a href="{{ route('members.card', $member) }}" target="_blank" class="btn-secondary">{{ __('Download Card') }}</a>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert-error mb-4">{{ session('error') }}</div>
    @endif

    {{-- Status banner + approve action --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="status-badge status-{{ $member->status }} text-base">{{ __(ucfirst($member->status)) }}</span>
            <span class="text-gray-500 text-sm">{{ __(ucfirst($member->category)) }}</span>
        </div>
        @can('update', $member)
            @if ($member->status === 'applicant')
                <div class="flex items-center gap-2">
                    <input wire:model="approvalReason" type="text" class="input w-64"
                        placeholder="{{ __('Approval note (optional)') }}" />
                    <button wire:click="approve" class="btn-primary">{{ __('Approve') }}</button>
                </div>
            @else
                <div class="flex gap-2">
                    @foreach (['lapsed','suspended','resigned'] as $s)
                        @if ($member->status !== $s)
                            <button wire:click="changeStatus('{{ $s }}')"
                                class="btn-sm btn-secondary">{{ __(ucfirst($s)) }}</button>
                        @endif
                    @endforeach
                    @if ($member->status !== 'active')
                        <button wire:click="changeStatus('active')" class="btn-sm btn-primary">{{ __('Reactivate') }}</button>
                    @endif
                </div>
            @endif
        @endcan
    </div>

    {{-- Tabs --}}
    <div class="border-b mb-4" x-data="{ tab: @entangle('activeTab') }">
        <div class="flex gap-4">
            @foreach (['profile','history','interactions'] as $t)
                <button @click="tab = '{{ $t }}'"
                    :class="tab === '{{ $t }}' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'text-gray-500'"
                    class="py-2 px-1 text-sm transition">
                    {{ __(ucfirst($t)) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Profile tab --}}
    @if ($activeTab === 'profile')
        <div class="bg-white rounded-lg shadow p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><span class="label">{{ __('Gender') }}</span><p>{{ __(ucfirst($member->gender ?? '—')) }}</p></div>
            <div><span class="label">{{ __('Date of Birth') }}</span><p>{{ $member->date_of_birth?->format('Y-m-d') ?? '—' }}</p></div>
            <div><span class="label">{{ __('Email') }}</span><p>{{ $member->email ?? '—' }}</p></div>
            <div><span class="label">{{ __('Phone') }}</span><p>{{ $member->phone ?? '—' }}</p></div>
            <div><span class="label">{{ __('Address') }}</span><p>{{ $member->address_line ?? '—' }}</p></div>
            <div><span class="label">{{ __('Occupation') }}</span><p>{{ $member->occupation ?? '—' }}</p></div>
            <div><span class="label">{{ __('Unit') }}</span><p>{{ $member->orgUnit?->name ?? '—' }}</p></div>
            <div><span class="label">{{ __('Joined') }}</span><p>{{ $member->joined_date?->format('Y-m-d') ?? '—' }}</p></div>
            <div><span class="label">{{ __('Wings') }}</span>
                <p>{{ collect(['youth' => $member->is_youth_wing, 'women' => $member->is_women_wing])->filter()->keys()->map(fn($w) => __(ucfirst($w)))->join(', ') ?: '—' }}</p>
            </div>
            <div><span class="label">{{ __('Volunteer') }}</span><p>{{ $member->volunteer ? __('Yes') : __('No') }}</p></div>
            @if ($member->skills)
                <div class="md:col-span-2"><span class="label">{{ __('Skills') }}</span><p>{{ implode(', ', $member->skills) }}</p></div>
            @endif
            <div><span class="label">{{ __('Consent') }}</span>
                <p>{{ $member->consent_at ? $member->consent_at->format('Y-m-d') . ' (' . __(ucfirst(str_replace('_', ' ', $member->consent_channel ?? ''))) . ')' : __('Not recorded') }}</p>
            </div>
        </div>
    @endif

    {{-- Status history tab --}}
    @if ($activeTab === 'history')
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th">{{ __('Date') }}</th>
                        <th class="th">{{ __('From') }}</th>
                        <th class="th">{{ __('To') }}</th>
                        <th class="th">{{ __('By') }}</th>
                        <th class="th">{{ __('Reason') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($member->statusHistory as $h)
                        <tr>
                            <td class="td text-sm">{{ $h->changed_at->format('Y-m-d H:i') }}</td>
                            <td class="td text-sm">{{ $h->from_status ? __(ucfirst($h->from_status)) : '—' }}</td>
                            <td class="td text-sm">{{ __(ucfirst($h->to_status)) }}</td>
                            <td class="td text-sm">{{ $h->changedByUser?->name ?? '—' }}</td>
                            <td class="td text-sm">{{ $h->reason ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="td text-center text-gray-500">{{ __('No history.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- Interactions tab --}}
    @if ($activeTab === 'interactions')
        @can('update', $member)
            <div class="bg-white rounded-lg shadow p-4 mb-4">
                <h3 class="font-semibold mb-3">{{ __('Log Interaction') }}</h3>
                <div class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="label">{{ __('Type') }}</label>
                        <select wire:model="newInteractionType" class="input">
                            @foreach (['call','meeting','visit','sms','email','other'] as $t)
                                <option value="{{ $t }}">{{ __(ucfirst($t)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-48">
                        <label class="label">{{ __('Summary') }}</label>
                        <input wire:model="newInteractionSummary" type="text" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">{{ __('Date/Time') }}</label>
                        <input wire:model="newInteractionDate" type="datetime-local" class="input" />
                    </div>
                    <button wire:click="logInteraction" class="btn-primary">{{ __('Log') }}</button>
                </div>
            </div>
        @endcan

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th">{{ __('Date') }}</th>
                        <th class="th">{{ __('Type') }}</th>
                        <th class="th">{{ __('Summary') }}</th>
                        <th class="th">{{ __('By') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($member->interactions as $i)
                        <tr>
                            <td class="td text-sm">{{ $i->occurred_at->format('Y-m-d H:i') }}</td>
                            <td class="td text-sm">{{ __(ucfirst($i->type)) }}</td>
                            <td class="td text-sm">{{ $i->summary ?? '—' }}</td>
                            <td class="td text-sm">{{ $i->byUser?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="td text-center text-gray-500">{{ __('No interactions recorded.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
