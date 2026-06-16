<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
  <title>{{ $subject ?? 'Self Note' }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    body { margin:0;padding:0;background-color:#f4f4f5; }
    * { box-sizing:border-box; }
  </style>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f4f4f5;padding:48px 20px 56px;">
    <tr>
      <td align="center" valign="top">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:560px;">

          {{-- ── CARD ── --}}
          <tr>
            <td style="background-color:#ffffff;border-radius:16px;border:1px solid #e4e4e7;overflow:hidden;">
              <table width="100%" cellpadding="0" cellspacing="0" role="presentation">

                {{-- Logo bar --}}
                <tr>
                  <td style="padding:24px 32px 22px;">
                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                      <tr>
                        <td valign="middle">
                          {{-- Logo mark --}}
                          <table cellpadding="0" cellspacing="0" role="presentation" style="display:inline-table;">
                            <tr>
                              <td valign="middle" style="background-color:#18181b;border-radius:8px;padding:6px 8px;line-height:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/></svg>
                              </td>
                              <td valign="middle" style="padding-left:8px;">
                                <span style="font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:15px;font-weight:700;color:#18181b;letter-spacing:-0.3px;">Self Note</span>
                              </td>
                            </tr>
                          </table>
                        </td>
                        <td valign="middle" align="right">
                          <span style="font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:12px;color:#a1a1aa;">{{ date('d M Y') }}</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                {{-- Top divider --}}
                <tr><td style="border-top:1px solid #f4f4f5;font-size:0;line-height:0;">&nbsp;</td></tr>

                {{-- Main content --}}
                <tr>
                  <td style="padding:40px 32px 36px;">
                    @yield('content')
                  </td>
                </tr>

                {{-- Bottom divider --}}
                <tr><td style="border-top:1px solid #f4f4f5;font-size:0;line-height:0;">&nbsp;</td></tr>

                {{-- Card footer --}}
                <tr>
                  <td style="padding:20px 32px 24px;">
                    <p style="margin:0;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:12px;color:#a1a1aa;line-height:1.6;">
                      Jika kamu tidak merasa melakukan permintaan ini, abaikan email ini. Tidak ada perubahan yang akan terjadi pada akunmu.
                    </p>
                  </td>
                </tr>

              </table>
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding-top:28px;">
              <p style="margin:0;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:12px;color:#71717a;line-height:1.8;">
                <strong style="color:#52525b;">Self Note</strong> &nbsp;·&nbsp; Your personal space to think and grow
              </p>
              <p style="margin:4px 0 0;font-family:'Plus Jakarta Sans',-apple-system,sans-serif;font-size:11px;color:#a1a1aa;">
                © {{ date('Y') }} Self Note. All rights reserved.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
