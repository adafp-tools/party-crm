<div>
    <h1 class="text-2xl font-bold mb-6">{{ __('Arrears Report') }}</h1>

    <div class="mb-4 flex items-center gap-3">
        <select wire:model.live="filterUnit" class="input w-64">
            <option value="">{{ __('All units') }}</option>
            @foreach ($orgUnits as $unit)
                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
            @endforeach
        </select>
        <span class="text-sm text-gray-500">{{ __('Sorted by lowest dues rate first.') }}</span>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">{{ __('Unit') }}</th>
                    <th class="th text-right">{{ __('Active Members') }}</th>
                    <th class="th text-right">{{ __('Paid') }}</th>
                    <th class="th text-right">{{ __('Owing') }}</th>
                    <th class="th">{{ __('Dues Rate') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($arrears as $row)
                    <tr>
                        <td class="td font-medium">{{ $row['unit'] }}</td>
                        <td class="td text-right">{{ $row['total'] }}</td>
                        <td class="td text-right text-green-600">{{ $row['paid'] }}</td>
                        <td class="td text-right text-red-600">{{ $row['owing'] }}</td>
                        <td class="td">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $row['pct'] < 50 ? 'bg-red-500' : ($row['pct'] < 80 ? 'bg-orange-400' : 'bg-green-500') }}"
                                        style="width: {{ $row['pct'] }}%"></div>
                                </div>
                                <span class="text-sm font-mono w-10 text-right">{{ $row['pct'] }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td text-center text-gray-500">{{ __('No data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
