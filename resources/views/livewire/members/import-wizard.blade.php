<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('Import Members') }}</h1>

    {{-- Step 1: Upload --}}
    @if ($step === 1)
        <div class="card">
            <h2 class="card-title">{{ __('Step 1: Upload CSV') }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ __('Upload a CSV file with member data. Required columns: first_name, last_name.') }}</p>
            <div>
                <label class="label">{{ __('CSV File') }}</label>
                <input wire:model="csvFile" type="file" accept=".csv,.txt" class="input" />
                @error('csvFile') <p class="error-text">{{ $message }}</p> @enderror
            </div>
            <div wire:loading wire:target="csvFile" class="mt-2 text-sm text-gray-500">
                {{ __('Parsing file…') }}
            </div>
        </div>
    @endif

    {{-- Step 2: Column mapping + unit selection --}}
    @if ($step === 2)
        <div class="space-y-4">
            <div class="card">
                <h2 class="card-title">{{ __('Step 2: Map Columns') }}</h2>
                <p class="text-sm text-gray-500 mb-4">{{ __('Total rows: :n', ['n' => $totalRows]) }}</p>
                <table class="min-w-full divide-y divide-gray-200 mb-4">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="th">{{ __('CSV Column') }}</th>
                            <th class="th">{{ __('Maps to') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($headers as $header)
                            <tr>
                                <td class="td font-mono text-sm">{{ $header }}</td>
                                <td class="td">
                                    <select wire:model="mapping.{{ $header }}" class="input">
                                        <option value="">{{ __('— skip —') }}</option>
                                        @foreach ($availableColumns as $col)
                                            <option value="{{ $col }}">{{ $col }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mb-4">
                    <label class="label">{{ __('Assign to Unit') }} *</label>
                    <select wire:model="orgUnitId" class="input w-64 @error('orgUnitId') input-error @enderror">
                        <option value="">{{ __('Select unit…') }}</option>
                        @foreach ($orgUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    @error('orgUnitId') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <button wire:click="goToConfirm" class="btn-primary">{{ __('Preview →') }}</button>
            </div>

            {{-- 20-row preview --}}
            <div class="card overflow-x-auto">
                <h2 class="card-title">{{ __('Preview (first 20 rows)') }}</h2>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach ($headers as $h)
                                <th class="th">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($preview as $row)
                            <tr>
                                @foreach ($headers as $h)
                                    <td class="td">{{ $row[$h] ?? '' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Step 3: Confirm --}}
    @if ($step === 3)
        <div class="card">
            <h2 class="card-title">{{ __('Step 3: Confirm Import') }}</h2>
            <p class="mb-4">{{ __('Ready to import :n rows. Duplicates (by email/phone) will be skipped.', ['n' => $totalRows]) }}</p>
            <div class="flex gap-3">
                <button wire:click="startImport" class="btn-primary">{{ __('Start Import') }}</button>
                <button wire:click="$set('step', 2)" class="btn-secondary">{{ __('← Back') }}</button>
            </div>
        </div>
    @endif

    {{-- Step 4: Progress --}}
    @if ($step === 4)
        <div class="card" wire:poll.2s="$refresh">
            <h2 class="card-title">{{ __('Importing…') }}</h2>
            @php $p = $progress; @endphp
            <div class="mb-2">
                <div class="bg-gray-200 rounded-full h-4">
                    <div class="bg-indigo-600 h-4 rounded-full transition-all"
                        style="width: {{ $p['total'] > 0 ? round($p['processed'] / $p['total'] * 100) : 0 }}%"></div>
                </div>
                <p class="text-sm mt-1">{{ $p['processed'] }} / {{ $p['total'] }} {{ __('rows processed') }}</p>
            </div>
            @if ($p['done'])
                <p class="text-green-600 font-semibold">{{ __('Import complete.') }}</p>
                @if ($p['errors'])
                    <details class="mt-2">
                        <summary class="cursor-pointer text-sm text-red-600">{{ count($p['errors']) }} {{ __('errors') }}</summary>
                        <ul class="text-sm mt-1 space-y-1">
                            @foreach ($p['errors'] as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                <a href="{{ route('members.index') }}" class="btn-primary mt-4 inline-block">{{ __('Go to Members') }}</a>
            @endif
        </div>
    @endif
</div>
