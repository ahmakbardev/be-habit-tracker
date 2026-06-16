@extends('emails.layout')

@section('content')

  {{-- Icon --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
    <tr>
      <td align="left">
        <table cellpadding="0" cellspacing="0" role="presentation">
          <tr>
            <td width="52" height="52" align="center" valign="middle"
                style="background-color:#eff6ff;border-radius:12px;border:1px solid #dbeafe;">
              {{-- Lucide: KeyRound --}}
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                   fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 18v3c0 .6.4 1 1 1h4v-3h3v-3h2l1.4-1.4a6.5 6.5 0 1 0-4-4Z"/>
                <circle cx="16.5" cy="7.5" r=".5" fill="#2563eb"/>
              </svg>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- Title --}}
  <h1 style="margin:0 0 6px;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:26px;font-weight:800;color:#18181b;letter-spacing:-0.8px;line-height:1.2;">
    Reset Password
  </h1>
  <p style="margin:0 0 28px;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:14px;color:#71717a;line-height:1.6;">
    Verifikasi identitas kamu untuk melanjutkan
  </p>

  {{-- Greeting --}}
  <p style="margin:0 0 24px;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:15px;color:#3f3f46;line-height:1.75;">
    Hei, <strong style="color:#18181b;">{{ $name }}</strong> — kami menerima permintaan reset password untuk akun <strong style="color:#18181b;">Self Note</strong> yang terhubung dengan email ini.
  </p>

  {{-- Callout --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
    <tr>
      <td style="background-color:#fafafa;border:1px solid #e4e4e7;border-radius:10px;padding:14px 16px;">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
          <tr>
            <td width="20" valign="top" style="padding-right:10px;padding-top:1px;">
              {{-- Lucide: Clock --}}
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                   fill="none" stroke="#71717a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
            </td>
            <td valign="top">
              <p style="margin:0;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:13px;color:#52525b;line-height:1.6;">
                Link ini hanya berlaku <strong style="color:#18181b;">60 menit</strong> dan hanya dapat digunakan satu kali.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- CTA Button --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:24px;">
    <tr>
      <td>
        <a href="{{ $resetUrl }}"
           style="display:block;background-color:#18181b;color:#ffffff;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:14px;font-weight:600;text-decoration:none;padding:14px 20px;border-radius:10px;text-align:center;letter-spacing:-0.1px;">
          Buat Password Baru
          &nbsp;
          <span style="opacity:0.7;">→</span>
        </a>
      </td>
    </tr>
  </table>

  {{-- Divider --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:20px;">
    <tr>
      <td style="border-top:1px solid #f4f4f5;font-size:0;line-height:0;">&nbsp;</td>
    </tr>
  </table>

  {{-- Fallback --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td valign="top" width="16" style="padding-right:8px;padding-top:2px;">
        {{-- Lucide: Link --}}
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
             fill="none" stroke="#a1a1aa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
          <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
        </svg>
      </td>
      <td valign="top">
        <p style="margin:0 0 6px;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:11px;color:#a1a1aa;line-height:1.5;">
          Tombol tidak berfungsi? Salin link berikut ke browser:
        </p>
        <p style="margin:0;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:11px;color:#6366f1;word-break:break-all;line-height:1.6;">
          {{ $resetUrl }}
        </p>
      </td>
    </tr>
  </table>

@endsection
