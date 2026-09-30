<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Selamat datang di {{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#F8FAFC;font-family:Inter,system-ui,sans-serif;">
    <table role="presentation" style="width:100%;border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:40px 20px;">
                <table role="presentation" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:12px;overflow:hidden;border:1px solid #E2E8F0;">
                    <tr>
                        <td style="background:#4F46E5;padding:32px 40px;">
                            <h1 style="margin:0;color:#FFFFFF;font-size:24px;font-weight:700;">{{ config('app.name') }}</h1>
                            <p style="margin:4px 0 0;color:#C7D2FE;font-size:14px;">Digital Library</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px;">
                            <h2 style="margin:0 0 16px;color:#1E293B;font-size:20px;font-weight:600;">Halo, {{ $user->name }}!</h2>
                            <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.6;">
                                Akun anggota Anda telah berhasil dibuat di <strong>{{ config('app.name') }}</strong>.
                                Anda sekarang dapat menjelajahi katalog buku digital dan meminjam buku.
                            </p>
                            <div style="background:#F1F5F9;border-radius:8px;padding:16px;margin-bottom:24px;">
                                <p style="margin:0 0 8px;color:#64748B;font-size:13px;">Detail akun:</p>
                                <p style="margin:0 0 4px;color:#1E293B;font-size:14px;"><strong>Email:</strong> {{ $user->email }}</p>
                                <p style="margin:0;color:#1E293B;font-size:14px;"><strong>Role:</strong> Anggota</p>
                            </div>
                            <p style="margin:0;color:#475569;font-size:15px;line-height:1.6;">
                                Terima kasih telah bergabung!
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#F8FAFC;padding:24px 40px;border-top:1px solid #E2E8F0;">
                            <p style="margin:0;color:#94A3B8;font-size:12px;text-align:center;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
