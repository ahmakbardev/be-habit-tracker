<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
  <title>{{ $subject ?? 'Self Note' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased;">

  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f8fafc;padding:48px 16px;">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:580px;">

          {{-- ===== CARD ===== --}}
          <tr>
            <td style="background-color:#ffffff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">

              {{-- Hero Image with overlay --}}
              <tr>
                <td style="padding:0;position:relative;line-height:0;">
                  <div style="position:relative;line-height:0;">
                    <img
                      src="https://images.unsplash.com/photo-1557682250-33bd709cbe85?w=580&h=200&fit=crop&auto=format&q=80"
                      width="580"
                      alt=""
                      style="width:100%;max-width:580px;height:200px;object-fit:cover;display:block;"
                    />
                    {{-- Dark overlay --}}
                    <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(135deg,rgba(15,23,42,0.72) 0%,rgba(99,102,241,0.45) 100%);"></div>
                    {{-- Logo on image --}}
                    <div style="position:absolute;top:28px;left:32px;">
                      <span style="color:#ffffff;font-size:18px;font-weight:700;letter-spacing:-0.5px;text-shadow:0 1px 3px rgba(0,0,0,0.3);">
                        ✦ Self Note
                      </span>
                    </div>
                    {{-- Tagline on image --}}
                    <div style="position:absolute;bottom:28px;left:32px;right:32px;">
                      <p style="margin:0;color:rgba(255,255,255,0.7);font-size:12px;letter-spacing:0.8px;text-transform:uppercase;font-weight:500;">
                        @yield('hero-label', 'Notification')
                      </p>
                      <h2 style="margin:4px 0 0;color:#ffffff;font-size:26px;font-weight:700;letter-spacing:-0.8px;line-height:1.2;">
                        @yield('hero-title', 'Hello')
                      </h2>
                    </div>
                  </div>
                </td>
              </tr>

              {{-- Content --}}
              <tr>
                <td style="padding:36px 40px 40px;">
                  @yield('content')
                </td>
              </tr>

              {{-- Card Footer --}}
              <tr>
                <td style="padding:20px 40px 28px;border-top:1px solid #f1f5f9;">
                  <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.7;">
                    Jika kamu tidak merasa melakukan permintaan ini, abaikan email ini — tidak ada yang akan berubah.
                  </p>
                </td>
              </tr>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding-top:28px;">
              <p style="margin:0;font-size:12px;color:#94a3b8;">
                <strong style="color:#64748b;">Self Note</strong>
                &nbsp;&middot;&nbsp;
                Your personal space to think and grow
              </p>
              <p style="margin:6px 0 0;font-size:11px;color:#cbd5e1;">
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
