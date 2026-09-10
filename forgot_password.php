<?php
session_start();
include 'config/db.php';
include 'config/smtp.php';
require 'lib/PHPMailer/Exception.php';
require 'lib/PHPMailer/PHPMailer.php';
require 'lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = "Is email pe koi account nahi mila.";
    } else {
        $user = $result->fetch_assoc();
        $code = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_expiry = ? WHERE id = ?");
        $stmt->bind_param("ssi", $code, $expiry, $user['id']);
        $stmt->execute();

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($smtp_user, $mail_from_name);
            $mail->addAddress($email, $user['name']);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code - Inventory Manager';
            $mail->Body    = "<h2>Hello {$user['name']},</h2>
                              <p>Aapne password reset request ki thi.</p>
                              <p>Aapka reset code hai:</p>
                              <h1 style='color:#E8A33D'>$code</h1>
                              <p>Ye code 15 minutes ke liye valid hai.</p>";

            $mail->send();
            $_SESSION['reset_email'] = $email;
            header("Location: reset_password.php");
            exit;
        } catch (Exception $e) {
            $error = "Mail bhejne mein masla: " . $mail->ErrorInfo;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Inventory Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
body { font-family: 'Inter', sans-serif; background-color: #F6F4EF; color: #232722; background-image: radial-gradient(#E4E1D8 1.2px, transparent 1.2px); background-size: 24px 24px; }
h1{ font-family: 'Sora', sans-serif; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-[400px]">
    <div class="flex items-center justify-center gap-3 mb-6">
      <div class="w-[34px] h-[34px] bg-[#E8A33D] rounded-[8px] flex items-center justify-center font-sora font-extrabold text-[#1C2333] text-base shadow-sm">IM</div>
      <div>
        <div class="font-sora font-bold text-[#1C2333]">Inventory Manager</div>
        <div class="text-[11px] text-[#767C74] uppercase tracking-wide">Solar Stock Control</div>
      </div>
    </div>

    <div class="bg-white border border-[#E4E1D8] rounded-[10px] shadow-xl p-7">
      <div class="text-center pb-5 mb-5 border-b border-[#E4E1D8]">
        <h1 class="font-sora font-bold text-lg text-[#1C2333]">Forgot Password?</h1>
        <p class="text-xs text-[#767C74] mt-0.5">Apna email daalo — reset code bhej denge.</p>
      </div>

      <?php if ($error): ?>
        <div class="mb-4 p-3 rounded-lg text-xs font-medium bg-[#FBE9E2] text-[#BD5B3D]"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" action="forgot_password.php" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-[#232722] mb-1.5 uppercase tracking-wide">Registered Email</label>
          <input type="email" name="email" required placeholder="you@example.com" class="w-full px-3.5 py-2.5 bg-[#FCFBF8] border border-[#E4E1D8] rounded-lg text-sm focus:outline-none focus:border-[#1C2333] focus:ring-2 focus:ring-[#1C2333]/10"/>
        </div>
        <button type="submit" class="w-full py-2.5 bg-[#1C2333] hover:bg-[#283248] text-white font-sora font-semibold text-sm rounded-lg transition-all active:scale-[0.99]">Send Reset Code</button>
      </form>

      <p class="mt-5 pt-4 border-t border-[#E4E1D8] text-center text-xs text-[#767C74]">
        <a href="login.php" class="text-[#1C2333] font-semibold hover:text-[#E8A33D]">Back to Login</a>
      </p>
    </div>
  </div>
</body>
</html>