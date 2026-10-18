<!doctype html>
<html lang="en">
<body style="margin:0;background:#f4f6f2;font-family:Inter,Segoe UI,Arial,sans-serif;color:#0b0d0c">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 12px">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border:1px solid #e2e7df;border-radius:18px;padding:32px">
            <tr><td>
                <div style="font-size:11px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#6d756e">EazyPOS invitation</div>
                <h1 style="font-size:26px;margin:8px 0 12px">Join {{ $business->name }}</h1>
                <p style="font-size:15px;line-height:1.6;margin:0 0 20px;color:#3b423c">
                    Hello {{ $invitation->full_name ?: 'there' }}, you have been invited to use EazyPOS as
                    <b>{{ ucfirst($invitation->role) }}</b>. Set your password to finish your account.
                </p>
                <a href="{{ $url }}" style="display:inline-block;background:#d3ff12;color:#0b0d0c;text-decoration:none;font-weight:900;padding:14px 20px;border-radius:12px">Accept invitation &rarr;</a>
                <p style="font-size:12px;line-height:1.6;color:#6d756e;margin:22px 0 0">
                    This link expires on {{ $invitation->expires_at->timezone('Asia/Colombo')->format('d M Y, h:i A') }}.
                    If you were not expecting this email you can ignore it.
                </p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
