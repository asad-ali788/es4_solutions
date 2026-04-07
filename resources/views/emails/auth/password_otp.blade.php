<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ES4 Solutions – Password Reset OTP</title>

  <!-- Hidden branding & domain metadata for spam filters -->
  <meta name="organization" content="ES4 Solutions">
  <meta name="author" content="ES4 Solutions">
  <style>
    a { color: #2563eb; text-decoration: none; }
  </style>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial,Helvetica,sans-serif;">

  <!-- Preheader (hidden preview text in inbox snippet) -->
  <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
    Your ES4 Solutions OTP is {{ $otp }}. It expires in 10 minutes.
  </div>

  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f3f4f6;">
    <tr>
      <td align="center" style="padding:24px;">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05);">
          
          <!-- Header -->
          <tr>
            <td style="padding:24px 24px 0 24px;">
              <h1 style="margin:0 0 12px 0; font-size:22px; line-height:1.3; color:#111827;">
                Hello {{ $user->name ?? 'User' }},
              </h1>
              <p style="margin:0; font-size:14px; color:#6b7280;">
                We received a request to reset your password for your <strong>ES4 Solutions</strong> account.
              </p>
            </td>
          </tr>

          <!-- OTP Section -->
          <tr>
            <td style="padding:24px;">
              <p style="margin:0 0 8px 0; color:#374151; font-size:14px;">
                Please use the following One-Time Password (OTP) to proceed:
              </p>

              <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; margin-top:10px;">
                <tr>
                  <td align="center" style="
                      padding:18px;
                      border:1px solid #e5e7eb;
                      background:#f9fafb;
                      border-radius:10px;
                      color:#111827;">
                    <div style="font-size:22px; font-weight:700; letter-spacing:2px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                      {{ $otp }}
                    </div>
                  </td>
                </tr>
              </table>

              <p style="margin-top:16px; color:#374151; font-size:14px;">
                This OTP is valid for <strong>10 minutes</strong>.  
                If you didn’t request this, you can safely ignore this email.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:16px 24px 24px 24px; font-size:12px; color:#6b7280; border-top:1px solid #f3f4f6;">
              <p style="margin:0 0 8px 0;">
                Thanks,<br>
                <strong>ES4 Solutions</strong><br>
                <a href="https://es4solutions.com" target="_blank" style="color:#2563eb; text-decoration:none;">es4solutions.com</a>
              </p>

              <p style="margin:0;">
                This automated message was sent by ES4 Solutions.  
                For help, contact <a href="mailto:hr@es4solutions.com" style="color:#2563eb;">hr@es4solutions.com</a>
              </p>
            </td>
          </tr>
        </table>

        <!-- Subtle brand line -->
        <div style="font-size:11px; color:#9ca3af; margin-top:12px;">
          © {{ date('Y') }} ES4 Solutions — All rights reserved.  
          <a href="https://es4solutions.com" style="color:#9ca3af; text-decoration:none;">es4solutions.com</a>
        </div>

        <!-- Invisible domain signature (helps Outlook trust mail) -->
        <div style="display:none; font-size:0;">
          This email is digitally sent from es4solutions.com through verified systems.
        </div>

      </td>
    </tr>
  </table>
</body>
</html>
