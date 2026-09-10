<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $email = $_SESSION['reset_email'];
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = "Password kam az kam 6 characters ka ho.";
    } elseif ($password !== $confirm) {
        $error = "Password match nahi kar rahe.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_code = ? AND reset_expiry > NOW()");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Code galat hai ya expire ho gaya.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expiry = NULL WHERE email = ?");
            $stmt->bind_param("ss", $hash, $email);
            $stmt->execute();
            unset($_SESSION['reset_email']);
            $success = "Password update ho gaya!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Inventory Manager</title>
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
        <h1 class="font-sora font-bold text-lg text-[#1C2333]">Reset Password</h1>
        <p class="text-xs text-[#767C74] mt-0.5">Email pe jo 6-digit code aaya tha woh daalo.</p>
      </div>

      <?php if ($success): ?>
        <div class="mb-4 p-3 rounded-lg text-xs font-medium bg-[#E9F3EC] text-[#4C8F63]"><?php echo htmlspecialchars($success); ?>
          <a href="login.php" class="font-semibold underline ml-1">Login Now</a>
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="mb-4 p-3 rounded-lg text-xs font-medium bg-[#FBE9E2] text-[#BD5B3D]"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <p class="text-xs font-semibold text-[#4C8F63] mb-4"><?php echo htmlspecialchars($_SESSION['reset_email']); ?></p>

      <form method="POST" action="reset_password.php" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-[#232722] mb-1.5 uppercase tracking-wide">Reset Code</label>
          <input type="text" name="code" required maxlength="6" placeholder="123456" class="w-full px-3.5 py-2.5 bg-[#FCFBF8] border border-[#E4E1D8] rounded-lg text-sm text-center tracking-[0.4em] font-bold font-mono focus:outline-none focus:border-[#1C2333]"/>
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#232722] mb-1.5 uppercase tracking-wide">New Password</label>
          <input type="password" name="password" required placeholder="Min 6 characters" class="w-full px-3.5 py-2.5 bg-[#FCFBF8] border border-[#E4E1D8] rounded-lg text-sm focus:outline-none focus:border-[#1C2333]"/>
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#232722] mb-1.5 uppercase tracking-wide">Confirm Password</label>
          <input type="password" name="confirm" required placeholder="Repeat password" class="w-full px-3.5 py-2.5 bg-[#FCFBF8] border border-[#E4E1D8] rounded-lg text-sm focus:outline-none focus:border-[#1C2333]"/>
        </div>
        <button type="submit" class="w-full py-2.5 bg-[#1C2333] hover:bg-[#283248] text-white font-sora font-semibold text-sm rounded-lg transition-all active:scale-[0.99]">Update Password</button>
      </form>
    </div>
  </div>
</body>
</html>