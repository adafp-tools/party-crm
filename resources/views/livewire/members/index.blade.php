<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Members') }}</h1>
        <div class="flex gap-2">
            @can('create', \App\Models\Member::class)
                <a href="{{ route('members.create') }}" class="btn-primary">{{ __('Add Member') }}</a>
                <a href="{{ route('members.import') }}" class="btn-secondary">{{ __('Import CSV') }}</a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="{{ __('Search name, ID, email, phone…') }}"
                class="col-span-2 md:col-span-4 input" />

            <select wire:model.live="filterStatus" class="input">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['applicant','active','lapsed','suspended','expelled','deceased','resigned'] as $s)
                    <option value="{{ $s }}">{{ __(ucfirst($s)) }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterCategory" class="input">
                <option value="">{{ __('All categories') }}</option>
                <option value="member">{{ __('Member') }}</option>
                <option value="supporter">{{ __('Supporter') }}</option>
            </select>

            <select wire:model.live="filterGender" class="input">
                <option value="">{{ __('All genders') }}</option>
                <option value="male">{{ __('Male') }}</option>
                <option value="female">{{ __('Female') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </select>

            <select wire:model.live="filterUnit" class="input">
                <option value="">{{ __('All units') }}</option>
                @foreach ($orgUnits as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterWing" class="input">
                <option value="">{{ __('All wings') }}</option>
                <option value="youth">{{ __('Youth Wing') }}</option>
                <option value="women">{{ __("Women's Wing") }}</option>
            </select>

            <input wire:model.live="joinedFrom" type="date" class="input" title="{{ __('Joined from') }}" />
            <input wire:model.live="joinedTo" type="date" class="input" title="{{ __('Joined to') }}" />
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">{{ __('Name') }}</th>
                    <th class="th">{{ __('Membership No') }}</th>
                    <th class="th">{{ __('Unit') }}</th>
                    <th class="th">{{ __('Category') }}</th>
                    <th class="th">{{ __('Status') }}</th>
                    <th class="th">{{ __('Joined') }}</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($members as $member)
                    <tr class="hover:bg-gray-50">
                        <td class="td font-medium">{{ $member->fullName() }}</td>
                        <td class="td font-mono text-sm">{{ $member->membership_no ?? '—' }}</td>
                        <td class="td text-sm">{{ $member->orgUnit?->name }}</td>
                        <td class="td text-sm">{{ __(ucfirst($member->category)) }}</td>
                        <td class="td">
                            <span class="status-badge status-{{ $member->status }}">{{ __(ucfirst($member->status)) }}</span>
                        </td>
                        <td class="td text-sm">{{ $member->joined_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="td text-right text-sm">
                            <a href="{{ route('members.show', $member) }}" class="link">{{ __('View') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="td text-center text-gray-500">{{ __('No members found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t">
            {{ $members->links() }}
        </div>
    </div>
</div>
