<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<link rel="icon" type="image/png" sizes="192x192" href="/icons/eazypos-192.png">
<title>EazyPOS — Team Console</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/app.css') }}">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('css/polish.css') }}">
<style>
.admin-top{display:flex;align-items:center;justify-content:space-between;gap:12px;background:#090c0a;color:#fff;padding:14px 24px}
.admin-top img{width:140px;height:auto;display:block}
.admin-top form{margin:0}
.notice{background:#e4f7cf;border:1px solid #cce89a;border-radius:12px;padding:12px 14px;margin-bottom:14px;font-weight:800;font-size:13px}
.notice.error{background:#fff1ef;border-color:#ffd5cf;color:#a92d20}
.inline-form{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.inline-form .input,.inline-form .select{width:auto;min-width:140px;padding:8px 10px}
.inline-form .btn{padding:8px 12px;font-size:12px}
details summary{cursor:pointer;font-weight:850;font-size:12px;color:var(--muted)}
.msg{white-space:pre-wrap;max-width:420px;font-size:12px;line-height:1.5}
.admin-top .inline-form{flex-wrap:nowrap}
@media(max-width:640px){.admin-top{padding:12px 14px}.admin-top img{width:108px}.admin-top .small{display:none}.inline-form .input,.inline-form .select{min-width:0;flex:1 1 140px}.msg{max-width:none}}
</style>
</head>
<body>
<div class="admin-top">
    <img src="/assets/eazypos-logo-green.png" alt="EazyPOS">
    <div class="inline-form">
        <span class="small" style="color:#aab3ab">{{ auth()->user()->email }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Log out</button></form>
    </div>
</div>

<div class="page">
    <div class="page-head"><div><div class="eyebrow">EazyPOS team</div><h1>Team Console</h1><div class="muted small" style="margin-top:5px">Create accounts for approved customers, manage access and follow up inquiries.</div></div></div>

    @if (session('status'))
        <div class="notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice error">{{ $errors->first() }}</div>
    @endif

    <div class="grid grid4 stats" style="margin-bottom:14px">
        <div class="card"><div class="stat-label">Businesses</div><div class="stat-value">{{ $businesses->count() }}</div></div>
        <div class="card"><div class="stat-label">Active</div><div class="stat-value">{{ $businesses->where('status', 'active')->count() }}</div></div>
        <div class="card"><div class="stat-label">Suspended / Closed</div><div class="stat-value">{{ $businesses->where('status', '!=', 'active')->count() }}</div></div>
        <div class="card"><div class="stat-label">New inquiries</div><div class="stat-value">{{ $inquiries->where('status', 'new')->count() }}</div></div>
    </div>

    <div class="card" style="margin-bottom:14px">
        <h2>Create a business account</h2>
        <div class="muted small" style="margin-bottom:14px">Use this after the customer has agreed to the payment. The owner signs in with the email and starting password below and can then invite staff.</div>
        <form method="POST" action="{{ route('admin.businesses.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field"><label>BUSINESS NAME *</label><input class="input" name="name" value="{{ old('name') }}" required maxlength="255"></div>
                <div class="field"><label>BUSINESS TYPE</label><input class="input" name="business_type" value="{{ old('business_type') }}" maxlength="120" placeholder="Retail, Grocery, Pharmacy..."></div>
                <div class="field"><label>COUNTRY *</label><input class="input" name="country" value="{{ old('country', 'Sri Lanka') }}" required maxlength="120"></div>
                <div class="field"><label>CURRENCY *</label><select class="select" name="currency">@foreach (['LKR', 'USD', 'GBP', 'EUR'] as $c)<option value="{{ $c }}" @selected(old('currency', 'LKR') === $c)>{{ $c }}</option>@endforeach</select></div>
                <div class="field"><label>BUSINESS PHONE</label><input class="input" name="phone" value="{{ old('phone') }}" maxlength="50"></div>
                <div class="field"><label>BUSINESS ADDRESS</label><input class="input" name="address" value="{{ old('address') }}" maxlength="2000"></div>
                <div class="field"><label>OWNER NAME *</label><input class="input" name="owner_name" value="{{ old('owner_name') }}" required maxlength="120"></div>
                <div class="field"><label>OWNER EMAIL *</label><input class="input" type="email" name="owner_email" value="{{ old('owner_email') }}" required maxlength="255"></div>
                <div class="field"><label>OWNER USERNAME</label><input class="input" name="owner_username" value="{{ old('owner_username') }}" maxlength="32" placeholder="Optional"></div>
                <div class="field"><label>STARTING PASSWORD</label><input class="input" type="password" name="owner_password" autocomplete="new-password" placeholder="At least 8 characters (not needed if the email already has an account)"></div>
            </div>
            <div class="actions" style="margin-top:14px"><button class="btn primary" type="submit">Create Business</button></div>
        </form>
    </div>

    <div class="card" style="margin-bottom:14px">
        <h2>Businesses</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Business</th><th>Owner</th><th>Staff</th><th>Created</th><th>Status</th><th>Manage</th></tr></thead>
            <tbody>
            @forelse ($businesses as $b)
                <tr>
                    <td><b>{{ $b->name }}</b><div class="muted small">{{ $b->business_type ?: '—' }} · {{ $b->country ?: '—' }} · {{ $b->currency }}</div></td>
                    <td>{{ $b->owner?->name ?? '—' }}<div class="muted small">{{ $b->owner?->email }}</div><div class="muted small">Last login: {{ $b->owner?->last_login_at?->timezone('Asia/Colombo')->format('d M Y H:i') ?? 'never' }}</div></td>
                    <td>{{ $b->members_count }}</td>
                    <td>{{ $b->created_at?->timezone('Asia/Colombo')->format('d M Y') }}</td>
                    <td><span class="pill {{ $b->status === 'active' ? 'good' : 'danger' }}">{{ $b->status }}</span></td>
                    <td>
                        <form class="inline-form" method="POST" action="{{ route('admin.businesses.status', $b) }}">
                            @csrf @method('PUT')
                            <select class="select" name="status">@foreach (\App\Models\Business::STATUSES as $s)<option value="{{ $s }}" @selected($b->status === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
                            <button class="btn" type="submit">Save</button>
                        </form>
                        <details style="margin-top:8px">
                            <summary>Reset owner password</summary>
                            <form class="inline-form" style="margin-top:6px" method="POST" action="{{ route('admin.businesses.password', $b) }}">
                                @csrf @method('PUT')
                                <input class="input" type="password" name="password" autocomplete="new-password" placeholder="New password" required minlength="8">
                                <button class="btn" type="submit">Update</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty">No businesses yet.</div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <h2>Inquiries &amp; account requests</h2>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>From</th><th>Topic</th><th>Message</th><th>Received</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($inquiries as $q)
                <tr>
                    <td><b>{{ $q->name }}</b><div class="muted small"><a href="mailto:{{ $q->email }}">{{ $q->email }}</a></div><div class="muted small">{{ $q->business ?: '' }}</div></td>
                    <td>{{ $q->topic ?: '—' }}</td>
                    <td><div class="msg">{{ $q->message }}</div></td>
                    <td>{{ $q->created_at?->timezone('Asia/Colombo')->format('d M Y H:i') }}</td>
                    <td>
                        <form class="inline-form" method="POST" action="{{ route('admin.inquiries.update', $q) }}">
                            @csrf @method('PUT')
                            <select class="select" name="status">@foreach (['new', 'contacted', 'closed'] as $s)<option value="{{ $s }}" @selected($q->status === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
                            <button class="btn" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty">No inquiries yet.</div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
<script src="{{ \App\Support\Asset::url('js/pos/12-ui.js') }}"></script>
<script>labelTables(document)</script>
</body>
</html>
