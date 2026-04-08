<?php
// ============================================================
//  SIRAJ — Lightweight SMTP Mailer
//  Works with Gmail, Outlook, Yahoo, and any SMTP provider
//  No external libraries required — pure PHP sockets
// ============================================================

class SirajMailer {

    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private        $socket;
    private array  $log = [];

    public function __construct(
        string $host,
        int    $port,
        string $username,
        string $password,
        string $fromEmail,
        string $fromName = 'SIRAJ Lighting'
    ) {
        $this->host      = $host;
        $this->port      = $port;
        $this->username  = $username;
        $this->password  = $password;
        $this->fromEmail = $fromEmail;
        $this->fromName  = $fromName;
    }

    // ── Public send method ────────────────────────────────
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
        try {
            $this->connect();
            $this->authenticate();
            $this->sendMail($toEmail, $toName, $subject, $htmlBody);
            $this->disconnect();
            return true;
        } catch (Exception $e) {
            $this->log[] = 'ERROR: ' . $e->getMessage();
            $this->disconnect();
            return false;
        }
    }

    public function getLog(): array { return $this->log; }

    // ── Connect & TLS handshake ───────────────────────────
    private function connect(): void {
        $this->log[] = "Connecting to {$this->host}:{$this->port}";

        // Use SSL wrapper for port 465, STARTTLS for 587/25
        if ($this->port === 465) {
            $this->socket = fsockopen("ssl://{$this->host}", $this->port, $errno, $errstr, 15);
        } else {
            $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 15);
        }

        if (!$this->socket) {
            throw new Exception("Cannot connect to SMTP server: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, 15);
        $this->expect('220');

        // EHLO
        $this->send_command("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $this->expect('250');

        // STARTTLS for port 587
        if ($this->port === 587) {
            $this->send_command("STARTTLS");
            $this->expect('220');
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("TLS handshake failed");
            }
            $this->send_command("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $this->expect('250');
        }
    }

    // ── SMTP Authentication ───────────────────────────────
    private function authenticate(): void {
        $this->send_command("AUTH LOGIN");
        $this->expect('334');
        $this->send_command(base64_encode($this->username));
        $this->expect('334');
        $this->send_command(base64_encode($this->password));
        $this->expect('235', 'Authentication failed. Check your email/password.');
        $this->log[] = "Authenticated as {$this->username}";
    }

    // ── Build & send email ────────────────────────────────
    private function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): void {
        $boundary = md5(uniqid());
        $plainText = strip_tags(str_replace(['<br>', '<br/>', '</p>', '</div>'], "\n", $htmlBody));

        // MAIL FROM
        $this->send_command("MAIL FROM: <{$this->fromEmail}>");
        $this->expect('250');

        // RCPT TO
        $this->send_command("RCPT TO: <{$toEmail}>");
        $this->expect('250');

        // DATA
        $this->send_command("DATA");
        $this->expect('354');

        // Build multipart email
        $toFormatted   = $toName ? "\"$toName\" <$toEmail>" : $toEmail;
        $fromFormatted = "\"{$this->fromName}\" <{$this->fromEmail}>";
        $date          = date('r');
        $messageId     = '<' . uniqid() . '@siraj.city>';

        $message  = "Date: $date\r\n";
        $message .= "From: $fromFormatted\r\n";
        $message .= "To: $toFormatted\r\n";
        $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $message .= "Message-ID: $messageId\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $message .= "\r\n";

        // Plain text part
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($plainText)) . "\r\n";

        // HTML part
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";

        $message .= "--$boundary--\r\n";
        $message .= ".";

        $this->send_command($message);
        $this->expect('250');
        $this->log[] = "Email sent to $toEmail";
    }

    private function disconnect(): void {
        if ($this->socket) {
            @$this->send_command("QUIT");
            fclose($this->socket);
            $this->socket = null;
        }
    }

    // ── Low-level helpers ─────────────────────────────────
    private function send_command(string $cmd): void {
        fwrite($this->socket, $cmd . "\r\n");
    }

    private function expect(string $code, string $customError = ''): string {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            // Last line of response has no dash after code
            if (substr($line, 3, 1) === ' ') break;
        }
        $this->log[] = trim($response);
        if (substr(trim($response), 0, 3) !== $code) {
            $err = $customError ?: "Expected $code, got: " . trim($response);
            throw new Exception($err);
        }
        return $response;
    }
}


// ============================================================
//  Email Templates
// ============================================================
function buildResetCodeEmail(string $userName, string $code, int $expiryMinutes = 10): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>SIRAJ Password Reset</title>
</head>
<body style="margin:0;padding:0;background:#0A1428;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#0A1428;padding:40px 20px;">
  <tr>
    <td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

        <!-- Header -->
        <tr>
          <td align="center" style="padding:0 0 32px;">
            <div style="display:inline-block;">
              <span style="font-size:32px;">✦</span>
              <span style="font-family:Georgia,serif;font-size:24px;font-weight:700;
                           color:#7EC8E3;letter-spacing:4px;vertical-align:middle;
                           margin-left:8px;">SIRAJ</span>
            </div>
            <div style="font-size:12px;color:rgba(255,255,255,0.3);
                        margin-top:6px;letter-spacing:2px;text-transform:uppercase;">
              Smart Street Lighting
            </div>
          </td>
        </tr>

        <!-- Card -->
        <tr>
          <td style="background:#132030;border-radius:20px;padding:48px 48px;
                     border:1px solid rgba(255,255,255,0.08);">

            <!-- Icon -->
            <div style="text-align:center;margin-bottom:24px;">
              <div style="display:inline-block;width:64px;height:64px;border-radius:50%;
                          background:rgba(74,144,184,0.15);border:2px solid rgba(74,144,184,0.4);
                          line-height:64px;font-size:28px;text-align:center;">🔑</div>
            </div>

            <!-- Title -->
            <h1 style="font-family:Georgia,serif;font-size:26px;font-weight:700;
                       color:#ffffff;text-align:center;margin:0 0 12px;">
              Password Reset Request
            </h1>
            <p style="color:rgba(255,255,255,0.55);text-align:center;
                      font-size:15px;margin:0 0 36px;line-height:1.6;">
              Hi <strong style="color:#7EC8E3;">$userName</strong>, use the code below
              to reset your Siraj account password.
            </p>

            <!-- Code Box -->
            <div style="background:rgba(74,144,184,0.1);border:2px solid rgba(74,144,184,0.35);
                        border-radius:16px;padding:28px;text-align:center;margin-bottom:32px;">
              <div style="font-size:12px;letter-spacing:3px;text-transform:uppercase;
                          color:rgba(255,255,255,0.4);margin-bottom:14px;">
                Your Verification Code
              </div>
              <div style="font-family:Georgia,'Courier New',monospace;font-size:52px;
                          font-weight:700;color:#7EC8E3;letter-spacing:16px;
                          text-shadow:0 0 30px rgba(126,200,227,0.4);">
                $code
              </div>
              <div style="font-size:13px;color:rgba(255,255,255,0.3);margin-top:14px;">
                 Expires in <strong style="color:#F5C542;">$expiryMinutes minutes</strong>
              </div>
            </div>

           

            <!-- Warning -->
            <div style="border-left:3px solid #E05C5C;padding-left:16px;">
              <p style="color:rgba(224,92,92,0.8);font-size:13px;margin:0;line-height:1.6;">
                If you did not request a password reset, please ignore this email.
                Your account remains secure.
              </p>
            </div>

          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:28px 0 0;text-align:center;">
            <p style="color:rgba(255,255,255,0.2);font-size:12px;margin:0;line-height:1.8;">
              © 2026 Siraj Lighting &nbsp;·&nbsp;
              <a href="mailto:Sirajteam.official@gmail.com" style="color:rgba(126,200,227,0.5);text-decoration:none;">
                Sirajteam.official@gmail.com
              </a> &nbsp;·&nbsp; Saudi Made
            </p>
            <p style="color:rgba(255,255,255,0.1);font-size:11px;margin:8px 0 0;">
              This is an automated email. Please do not reply.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
HTML;
}


// ============================================================
//  Helper: send the reset code email
// ============================================================
function sendResetCodeEmail(string $toEmail, string $toName, string $code): bool {
    $mailer = new SirajMailer(
        host:      SMTP_HOST,
        port:      SMTP_PORT,
        username:  SMTP_USER,
        password:  SMTP_PASS,
        fromEmail: FROM_EMAIL,
        fromName:  FROM_NAME
    );

    $subject = 'SIRAJ — Your Password Reset Code: ' . $code;
    $html    = buildResetCodeEmail($toName, $code);

    return $mailer->send($toEmail, $toName, $subject, $html);
}
