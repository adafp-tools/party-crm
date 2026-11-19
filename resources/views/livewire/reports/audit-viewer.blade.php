<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">{{ __('Audit Log') }}</h1>
        <a href="{{ route('reports.audit-export') }}" class="btn-secondary text-sm">
            ↓ {{ __('Download CSV') }}
        </a>
    </div>

    {{-- Filters --}}
    <div class="card p-4 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label class="form-label text-xs">{{ __('User') }}</label>
                <select wire:model.live="filterUser" class="form-input mt-1 text-sm">
                    <option value="">— {{ __('All users') }} —</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label text-xs">{{ __('Action') }}</label>
                <input wire:model.live.debounce.400ms="filterAction" type="text"
                       class="form-input mt-1 text-sm" placeholder="{{ __('e.g. member.created') }}">
            </div>
            <div>
                <label class="form-label text-xs">{{ __('From') }}</label>
                <input wire:model.live="filterDateFrom" type="date" class="form-input mt-1 text-sm">
            </div>
            <div>
                <label class="form-label text-xs">{{ __('To') }}</label>
                <input wire:model.live="filterDateTo" type="date" class="form-input mt-1 text-sm">
            </div>
        </div>
    </div>

    {{-- Log table --}}
    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Time') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('User') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Action') }}</th>
                    <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Subject') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500 whitespace-nowrap">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-4 py-3 text-gray-900">
                            {{ $log->user?->name ?? __('System') }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-800">
                            {{ $log->action }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            @if ($log->subject_type)
                                <span class="font-mono">{{ class_basename($log->subject_type) }}</span>
                                @if ($log->subject_id)
                                    <span class="text-gray-400">{{ Str::limit($log->subject_id, 8, '') }}</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">{{ __('No log entries found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t">
            {{ $logs->links() }}
        </div>
    </div>
</div>
