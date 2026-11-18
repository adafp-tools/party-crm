<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">{{ __('Tasks') }}</h1>
        @can('create', \App\Models\Task::class)
            <a href="{{ route('tasks.create') }}" class="btn-primary">{{ __('New Task') }}</a>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap gap-3 mb-4 items-center">
        <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input wire:model.live="myTasksOnly" type="checkbox" class="checkbox" />
            {{ __('My tasks only') }}
        </label>
        <select wire:model.live="filterStatus" class="input w-40">
            <option value="">{{ __('All statuses') }}</option>
            <option value="pending">{{ __('Pending') }}</option>
            <option value="in_progress">{{ __('In Progress') }}</option>
            <option value="done">{{ __('Done') }}</option>
            <option value="cancelled">{{ __('Cancelled') }}</option>
        </select>
        <select wire:model.live="filterUnit" class="input w-56">
            <option value="">{{ __('All units') }}</option>
            @foreach ($orgUnits as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">{{ __('Title') }}</th>
                    <th class="th">{{ __('Assignee') }}</th>
                    <th class="th">{{ __('Unit') }}</th>
                    <th class="th">{{ __('Due') }}</th>
                    <th class="th">{{ __('Status') }}</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($tasks as $task)
                    <tr class="{{ $task->isOverdue() ? 'bg-red-50' : '' }}">
                        <td class="td font-medium">
                            {{ $task->title }}
                            @if ($task->isOverdue())
                                <span class="ml-1 text-xs text-red-600 font-semibold">{{ __('OVERDUE') }}</span>
                            @endif
                        </td>
                        <td class="td text-sm">{{ $task->assignee?->name ?? '—' }}</td>
                        <td class="td text-sm">{{ $task->orgUnit?->name ?? '—' }}</td>
                        <td class="td text-sm {{ $task->isOverdue() ? 'text-red-600 font-semibold' : '' }}">
                            {{ $task->due_at ? $task->due_at->format('d M Y') : '—' }}
                        </td>
                        <td class="td">
                            <span class="status-badge
                                {{ match($task->status) {
                                    'done' => 'status-active',
                                    'cancelled' => 'status-lapsed',
                                    'in_progress' => 'bg-blue-100 text-blue-700',
                                    default => 'bg-yellow-100 text-yellow-700'
                                } }}">
                                {{ __(ucfirst(str_replace('_', ' ', $task->status))) }}
                            </span>
                        </td>
                        <td class="td text-right text-sm flex gap-2 justify-end">
                            @can('update', $task)
                                <a href="{{ route('tasks.edit', $task) }}" class="link">{{ __('Edit') }}</a>
                                @if ($task->status !== 'done')
                                    <button wire:click="updateStatus('{{ $task->id }}', 'done')"
                                        class="text-green-600 hover:underline">{{ __('Done') }}</button>
                                @endif
                            @endcan
                            @can('delete', $task)
                                <button wire:click="delete('{{ $task->id }}')"
                                    wire:confirm="{{ __('Delete this task?') }}"
                                    class="link text-red-600">{{ __('Delete') }}</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="td text-center text-gray-500">{{ __('No tasks found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t">{{ $tasks->links() }}</div>
    </div>
</div>
