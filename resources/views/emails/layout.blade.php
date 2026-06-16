<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $subject ?? 'Self Note' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:40px 16px;">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

          {{-- Header --}}
          <tr>
            <td align="center" style="padding-bottom:24px;">
              <table cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background-color:#0f172a;border-radius:12px;padding:12px 24px;">
                    <span style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:-0.5px;">
                      ✦ Self Note
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background-color:#ffffff;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,0.08);overflow:hidden;">

              {{-- Card Top Accent --}}
              <tr>
                <td style="background:linear-gradient(90deg,#6366f1,#8b5cf6);height:4px;display:block;line-height:4px;font-size:4px;">&nbsp;</td>
              </tr>

              {{-- Card Body --}}
              <tr>
                <td style="padding:40px 40px 32px;">
                  @yield('content')
                </td>
              </tr>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding-top:24px;">
              <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                Email ini dikirim otomatis oleh <strong>Self Note</strong>.<br/>
                Jika kamu tidak merasa melakukan permintaan ini, abaikan email ini.
              </p>
              <p style="margin:8px 0 0;font-size:12px;color:#cbd5e1;">
                &copy; {{ date('Y') }} Self Note. All rights reserved.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
