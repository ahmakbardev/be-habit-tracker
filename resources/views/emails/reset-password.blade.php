@extends('emails.layout')

@section('hero-label', 'Keamanan Akun')
@section('hero-title', 'Reset Password')

@section('content')

  {{-- Greeting --}}
  <p style="margin:0 0 24px;font-size:15px;color:#475569;line-height:1.7;">
    Hei, <strong style="color:#0f172a;">{{ $name }}</strong> 👋 — kami menerima permintaan untuk mereset password akun Self Note kamu.
  </p>

  {{-- Info box --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
    <tr>
      <td style="background-color:#f8f7ff;border:1px solid #e0e7ff;border-left:3px solid #6366f1;border-radius:8px;padding:14px 16px;">
        <p style="margin:0;font-size:13px;color:#4338ca;line-height:1.6;">
          🔐 &nbsp; Link ini hanya berlaku selama <strong>60 menit</strong> dan hanya bisa digunakan sekali.
        </p>
      </td>
    </tr>
  </table>

  {{-- Body --}}
  <p style="margin:0 0 28px;font-size:14px;color:#64748b;line-height:1.8;">
    Klik tombol di bawah untuk membuat password baru. Jika kamu tidak meminta reset ini, abaikan email ini dan password kamu tidak akan berubah.
  </p>

  {{-- CTA Button --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:28px;">
    <tr>
      <td>
        <a href="{{ $resetUrl }}"
           style="display:block;background-color:#6366f1;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;padding:14px 24px;border-radius:8px;text-align:center;letter-spacing:0.1px;">
          Buat Password Baru &rarr;
        </a>
      </td>
    </tr>
  </table>

  {{-- Divider --}}
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:20px;">
    <tr>
      <td style="border-top:1px solid #f1f5f9;font-size:0;line-height:0;">&nbsp;</td>
    </tr>
  </table>

  {{-- Fallback --}}
  <p style="margin:0 0 8px;font-size:12px;color:#94a3b8;">
    Tombol tidak berfungsi? Salin dan tempel link berikut ke browser kamu:
  </p>
  <p style="margin:0;font-size:11px;color:#6366f1;word-break:break-all;background-color:#fafafa;border:1px solid #e2e8f0;padding:10px 12px;border-radius:6px;line-height:1.6;">
    {{ $resetUrl }}
  </p>

@endsection
