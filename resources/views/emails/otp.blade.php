@extends('emails.layout')

@section('content')

  {{-- Icon --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
    <tr>
      <td align="left">
        <table cellpadding="0" cellspacing="0" role="presentation">
          <tr>
            <td width="52" height="52" align="center" valign="middle"
                style="background-color:#fef9c3;border-radius:12px;border:1px solid #fde047;">
              {{-- Lucide: ShieldCheck --}}
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                   fill="none" stroke="#ca8a04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
                <path d="m9 12 2 2 4-4"/>
              </svg>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- Title --}}
  <h1 style="margin:0 0 6px;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:26px;font-weight:800;color:#18181b;letter-spacing:-0.8px;line-height:1.2;">
    Kode Verifikasi
  </h1>
  <p style="margin:0 0 28px;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:14px;color:#71717a;line-height:1.6;">
    Reset password untuk akun Self Note kamu
  </p>

  {{-- Greeting --}}
  <p style="margin:0 0 28px;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:15px;color:#3f3f46;line-height:1.75;">
    Hei, <strong style="color:#18181b;">{{ $name }}</strong> — gunakan kode di bawah ini untuk mereset password akun Self Note kamu.
  </p>

  {{-- OTP Display --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
    <tr>
      <td align="center">
        <table cellpadding="0" cellspacing="0" role="presentation">
          <tr>
            <td style="background-color:#18181b;border-radius:14px;padding:24px 40px;">
              <p style="margin:0;font-family:'Plus Jakarta Sans',-apple-system,monospace;font-size:48px;font-weight:800;color:#ffffff;letter-spacing:18px;line-height:1;text-align:center;">{{ $otp }}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- Callout: expiry --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:24px;">
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
                Kode ini hanya berlaku <strong style="color:#18181b;">10 menit</strong> dan hanya dapat digunakan satu kali.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  {{-- Disclaimer --}}
  <p style="margin:0;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:13px;color:#71717a;line-height:1.6;">
    Jika kamu tidak meminta reset password, abaikan email ini. Kode akan kedaluwarsa otomatis.
  </p>

@endsection
