@extends('emails.layout')

@section('content')

  {{-- Icon --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    <tr>
      <td align="center">
        <div style="background-color:#ede9fe;border-radius:50%;width:56px;height:56px;display:inline-flex;align-items:center;justify-content:center;font-size:24px;line-height:56px;text-align:center;">
          🔐
        </div>
      </td>
    </tr>
  </table>

  {{-- Title --}}
  <h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#0f172a;text-align:center;letter-spacing:-0.5px;">
    Reset Password
  </h1>
  <p style="margin:0 0 28px;font-size:14px;color:#64748b;text-align:center;line-height:1.6;">
    Hei, <strong style="color:#0f172a;">{{ $name }}</strong>! Kami menerima permintaan reset password untuk akun kamu.
  </p>

  {{-- Divider --}}
  <div style="border-top:1px solid #f1f5f9;margin-bottom:28px;"></div>

  {{-- Body --}}
  <p style="margin:0 0 24px;font-size:14px;color:#475569;line-height:1.7;">
    Klik tombol di bawah untuk membuat password baru. Link ini hanya berlaku selama <strong>60 menit</strong>.
  </p>

  {{-- CTA Button --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
    <tr>
      <td align="center">
        <a href="{{ $resetUrl }}"
           style="display:inline-block;background-color:#6366f1;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;padding:13px 32px;border-radius:8px;letter-spacing:0.1px;">
          Reset Password Sekarang
        </a>
      </td>
    </tr>
  </table>

  {{-- Divider --}}
  <div style="border-top:1px solid #f1f5f9;margin-bottom:24px;"></div>

  {{-- Fallback URL --}}
  <p style="margin:0 0 8px;font-size:12px;color:#94a3b8;line-height:1.6;">
    Tombol tidak berfungsi? Copy dan paste URL berikut ke browser:
  </p>
  <p style="margin:0;font-size:11px;color:#6366f1;word-break:break-all;background-color:#f8f7ff;padding:10px 12px;border-radius:6px;border:1px solid #e0e7ff;">
    {{ $resetUrl }}
  </p>

@endsection
