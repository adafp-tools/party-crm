<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: DejaVu Sans, sans-serif;
    width: 85.6mm;
    height: 54mm;
    background: #1e3a8a;
    color: #fff;
    padding: 6mm;
    font-size: 8pt;
    position: relative;
}
.party-name {
    font-size: 10pt;
    font-weight: bold;
    text-align: center;
    margin-bottom: 3mm;
    letter-spacing: 0.5pt;
}
.member-name {
    font-size: 11pt;
    font-weight: bold;
    margin-bottom: 1mm;
}
.membership-no {
    font-family: DejaVu Sans Mono, monospace;
    font-size: 8pt;
    color: #bfdbfe;
    margin-bottom: 2mm;
}
.meta {
    font-size: 7pt;
    color: #bfdbfe;
    line-height: 1.4;
}
.qr-wrap {
    position: absolute;
    right: 5mm;
    bottom: 5mm;
}
.qr-wrap img {
    width: 18mm;
    height: 18mm;
    background: #fff;
    padding: 1mm;
}
.status-badge {
    display: inline-block;
    background: #16a34a;
    color: #fff;
    font-size: 7pt;
    padding: 1pt 4pt;
    border-radius: 3pt;
    margin-top: 1mm;
}
</style>
</head>
<body>
<div class="party-name">{{ strtoupper(config('app.name', 'PARTY CRM')) }}</div>
<div class="member-name">{{ $member->fullName() }}</div>
<div class="membership-no">{{ $member->membership_no }}</div>
<div class="meta">
    {{ __('Unit') }}: {{ $member->orgUnit?->name ?? '—' }}<br>
    {{ __('Joined') }}: {{ $member->joined_date?->format('Y-m-d') ?? '—' }}<br>
    {{ __(ucfirst($member->category)) }}
</div>
<span class="status-badge">{{ __(ucfirst($member->status)) }}</span>
<div class="qr-wrap">
    <img src="data:image/png;base64,{{ $qrBase64 }}" alt="QR" />
</div>
</body>
</html>
