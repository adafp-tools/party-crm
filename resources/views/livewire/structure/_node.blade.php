<div
    class="border-b border-gray-100 last:border-0"
    x-data="{ open: true }"
>
    <div
        class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 {{ ! $node['unit']->is_active ? 'opacity-50' : '' }}"
        style="padding-left: {{ 1 + $depth * 1.5 }}rem"
    >
        {{-- Expand / collapse toggle --}}
        @if (count($node['children']) > 0)
            <button @click="open = !open" class="shrink-0 text-gray-400 hover:text-gray-700">
                <svg x-show="open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                </svg>
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                </svg>
            </button>
        @else
            <span class="h-4 w-4 shrink-0"></span>
        @endif

        {{-- Unit info --}}
        <span class="flex-1 text-sm font-medium text-gray-800">
            {{ $node['unit']->name }}
            @if ($node['unit']->code)
                <span class="ml-1 text-xs font-normal text-gray-500">{{ $node['unit']->code }}</span>
            @endif
            @if (! $node['unit']->is_active)
                <span class="ml-1 rounded bg-gray-100 px-1 py-0.5 text-xs text-gray-500">{{ __('Inactive') }}</span>
            @endif
        </span>

        <span class="text-xs text-gray-400">
            {{ $node['unit']->orgLevel?->name_key }}
        </span>
        <span class="text-xs text-gray-400">
            {{ $node['unit']->members_count ?? 0 }} {{ __('members') }}
        </span>

        {{-- Actions --}}
        <div class="flex items-center gap-1 text-xs">
            @can('create', \App\Models\OrgUnit::class)
                <button
                    wire:click="openCreateModal('{{ $node['unit']->id }}')"
                    class="rounded px-1.5 py-0.5 text-primary-600 hover:bg-primary-50"
                    title="{{ __('Add child unit') }}"
                >+</button>
            @endcan
            @can('update', $node['unit'])
                <button
                    wire:click="openEditModal('{{ $node['unit']->id }}')"
                    class="rounded px-1.5 py-0.5 text-gray-500 hover:bg-gray-100"
                >{{ __('Edit') }}</button>
                <button
                    wire:click="toggleActive('{{ $node['unit']->id }}')"
                    class="rounded px-1.5 py-0.5 text-gray-500 hover:bg-gray-100"
                >{{ $node['unit']->is_active ? __('Deactivate') : __('Activate') }}</button>
            @endcan
        </div>
    </div>

    {{-- Children --}}
    @if (count($node['children']) > 0)
        <div x-show="open">
            @foreach ($node['children'] as $child)
                @include('livewire.structure._node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
