<div
    x-data="qrScanner()"
    x-on:qr-success.window="onSuccess($event.detail.name)"
    x-on:qr-error.window="onError($event.detail.message)"
>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">{{ $event->title }}</h1>
            <p class="text-sm text-gray-500">
                {{ $event->starts_at->format('d M Y H:i') }}
                @if ($event->location_text) · {{ $event->location_text }} @endif
            </p>
        </div>
        <a href="{{ route('events.index') }}" class="btn-secondary">{{ __('← Events') }}</a>
    </div>

    @if (session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @php
            $counts = $attendance->countBy('status');
        @endphp
        <div class="card text-center">
            <div class="text-3xl font-bold">{{ $attendance->count() }}</div>
            <div class="text-sm text-gray-500">{{ __('Invited') }}</div>
        </div>
        <div class="card text-center">
            <div class="text-3xl font-bold text-green-600">{{ $counts->get('attended', 0) }}</div>
            <div class="text-sm text-gray-500">{{ __('Attended') }}</div>
        </div>
        <div class="card text-center">
            <div class="text-3xl font-bold text-red-500">{{ $counts->get('absent', 0) }}</div>
            <div class="text-sm text-gray-500">{{ __('Absent') }}</div>
        </div>
        <div class="card text-center">
            <div class="text-3xl font-bold text-blue-600">{{ $attendanceRate }}%</div>
            <div class="text-sm text-gray-500">{{ __('Rate') }}</div>
        </div>
    </div>

    @can('manageAttendance', $event)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- Search invite --}}
        <div class="card">
            <h3 class="font-semibold mb-2">{{ __('Invite by name / number') }}</h3>
            <div class="flex gap-2">
                <input wire:model="searchMember" type="text" class="input flex-1"
                    placeholder="{{ __('Name or membership no…') }}"
                    wire:keydown.enter="inviteBySearch" />
                <button wire:click="inviteBySearch" class="btn-primary">{{ __('Add') }}</button>
            </div>
        </div>

        {{-- QR scan --}}
        <div class="card">
            <h3 class="font-semibold mb-2">{{ __('Scan membership card QR') }}</h3>
            <div x-show="!scanning">
                <button x-on:click="startScan()" class="btn-primary">{{ __('Open Camera') }}</button>
            </div>
            <div x-show="scanning">
                <video id="qr-video" class="w-full rounded" autoplay playsinline></video>
                <button x-on:click="stopScan()" class="btn-secondary mt-2">{{ __('Stop') }}</button>
                <p x-show="lastResult" x-text="lastResult" class="mt-2 text-sm font-medium"></p>
            </div>
        </div>
    </div>
    @endcan

    {{-- Attendance list --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">{{ __('Member') }}</th>
                    <th class="th">{{ __('No.') }}</th>
                    <th class="th">{{ __('Status') }}</th>
                    @can('manageAttendance', $event)
                        <th class="th"></th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($attendance as $row)
                    <tr>
                        <td class="td font-medium">{{ $row->member?->fullName() ?? '—' }}</td>
                        <td class="td font-mono text-sm">{{ $row->member?->membership_no ?? '—' }}</td>
                        <td class="td">
                            <span class="status-badge
                                {{ $row->status === 'attended' ? 'status-active' : ($row->status === 'absent' ? 'status-suspended' : 'bg-gray-100 text-gray-700') }}">
                                {{ __(ucfirst($row->status)) }}
                            </span>
                        </td>
                        @can('manageAttendance', $event)
                            <td class="td text-right text-sm flex gap-2 justify-end">
                                @if ($row->status !== 'attended')
                                    <button wire:click="markAttended('{{ $row->member_id }}')"
                                        class="text-green-600 hover:underline">{{ __('Attended') }}</button>
                                @endif
                                @if ($row->status !== 'absent')
                                    <button wire:click="markAbsent('{{ $row->member_id }}')"
                                        class="text-red-500 hover:underline">{{ __('Absent') }}</button>
                                @endif
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="4" class="td text-center text-gray-500">{{ __('No attendees yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
function qrScanner() {
    return {
        scanning: false,
        stream: null,
        animFrame: null,
        lastResult: '',

        async startScan() {
            this.scanning = true;
            this.lastResult = '';
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                const video = document.getElementById('qr-video');
                video.srcObject = this.stream;
                video.play();
                this.scanFrame(video);
            } catch {
                this.scanning = false;
                alert('{{ __("Camera not available.") }}');
            }
        },

        stopScan() {
            this.scanning = false;
            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); }
            if (this.animFrame) { cancelAnimationFrame(this.animFrame); }
        },

        scanFrame(video) {
            if (!this.scanning) return;
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                const imageData = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);
                const code = window.jsQR(imageData.data, imageData.width, imageData.height);
                if (code) {
                    this.stopScan();
                    @this.handleQrScan(code.data);
                    return;
                }
            }
            this.animFrame = requestAnimationFrame(() => this.scanFrame(video));
        },

        onSuccess(name) { this.lastResult = '✓ ' + name; },
        onError(msg) { this.lastResult = '✗ ' + msg; this.scanning = false; },
    };
}
window.qrScanner = qrScanner;
</script>
@endpush
