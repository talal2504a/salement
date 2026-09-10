<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit; }
include 'config/db.php';
require_once 'includes/activity.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        log_activity($conn, 'LOGIN', "User {$user['name']} logged in");
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Inventory Manager | Solar Stock Control</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
body {
  font-family: 'Inter', sans-serif;
  background-color: #F6F4EF;
  color: #232722;
  background-image: radial-gradient(#E4E1D8 1.2px, transparent 1.2px);
  background-size: 24px 24px;
}
h1, h2, h3 { font-family: 'Sora', sans-serif; }
input[type="checkbox"]:checked { background-color: #1C2333; border-color: #1C2333; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased selection:bg-[#E8A33D]/30 selection:text-[#1C2333]">
<?php include 'includes/loader.php'; ?>

  <div class="w-full max-w-[420px]">

    <!-- BRAND AT TOP -->
    <div class="flex items-center justify-center gap-3 mb-6">
      <div class="w-[34px] h-[34px] bg-[#E8A33D] rounded-[8px] flex items-center justify-center font-sora font-extrabold text-[#1C2333] text-base shadow-sm">IM</div>
      <div>
        <div class="font-sora font-bold text-[#1C2333] text-[17px] leading-tight">Inventory Manager</div>
        <div class="text-[11px] text-[#767C74] uppercase tracking-wide">Solar Stock Control</div>
      </div>
    </div>

    <!-- LOGIN CARD (CENTERED) -->
    <div class="bg-white border border-[#E4E1D8] rounded-[10px] shadow-xl p-7 sm:p-8">
      <div class="text-center pb-6 mb-6 border-b border-[#E4E1D8]">
        <h1 class="font-sora font-bold text-lg text-[#1C2333]">System Sign In</h1>
        <p class="text-xs text-[#767C74] mt-0.5">Authorized personnel access only</p>
      </div>

      <?php if ($error): ?>
        <div class="mb-4 p-3 rounded-lg text-xs font-medium flex items-start gap-2 bg-[#FBE9E2] border border-[#BD5B3D]/30 text-[#BD5B3D]">
          <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" class="space-y-4" autocomplete="off">
        <!-- EMAIL -->
        <div>
          <label for="email" class="block text-xs font-semibold text-[#232722] mb-1.5 uppercase tracking-wide">Email Address</label>
          <div class="relative">
            <input type="email" id="email" name="email" placeholder="Enter Email Address" autocomplete="off" required class="w-full px-3.5 py-2.5 bg-[#FCFBF8] border border-[#E4E1D8] rounded-lg text-sm text-[#232722] placeholder-[#767C74]/60 focus:outline-none focus:border-[#1C2333] focus:ring-2 focus:ring-[#1C2333]/10 transition-colors duration-150"/>
            <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-[#767C74]">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/></svg>
            </div>
          </div>
        </div>

        <!-- PASSWORD -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label for="password" class="block text-xs font-semibold text-[#232722] uppercase tracking-wide">Password</label>
            <a href="forgot_password.php" class="text-xs text-[#767C74] hover:text-[#1C2333] hover:underline transition-colors font-medium">Forgot Password?</a>
          </div>
          <div class="relative">
            <input type="password" id="password" name="password" placeholder="Enter Password" autocomplete="new-password" required class="w-full pl-3.5 pr-11 py-2.5 bg-[#FCFBF8] border border-[#E4E1D8] rounded-lg text-sm text-[#232722] placeholder-[#767C74]/60 focus:outline-none focus:border-[#1C2333] focus:ring-2 focus:ring-[#1C2333]/10 transition-colors duration-150"/>
            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-[#767C74] hover:text-[#1C2333] focus:outline-none transition-colors">
              <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg id="eye-slash-icon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
              </svg>
            </button>
          </div>
        </div>

        <!-- REMEMBER ME -->
        <div class="flex items-center justify-between pt-1">
          <label class="flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" id="remember" class="w-4 h-4 rounded border-[#E4E1D8] text-[#1C2333] focus:ring-0"/>
            <span class="text-xs text-[#232722] font-medium">Keep me signed in</span>
          </label>
        </div>

        <!-- LOGIN BUTTON -->
        <div class="pt-2">
          <button type="submit" class="w-full py-2.5 px-4 bg-[#1C2333] hover:bg-[#283248] text-white font-sora font-semibold text-sm rounded-lg shadow-sm transition-all duration-150 flex items-center justify-center gap-2 group active:scale-[0.99]">
            <span>Authenticate &amp; Enter Hub</span>
            <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform text-[#E8A33D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          </button>
        </div>
      </form>

      <!-- REGISTER LINK -->
      <div class="mt-6 pt-5 border-t border-[#E4E1D8] text-center">
        <p class="text-xs text-[#767C74]">Don't have an account?
          <a href="register.php" class="text-[#1C2333] font-semibold hover:text-[#E8A33D] transition-colors ml-1">Register Now</a>
        </p>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="mt-5 text-center text-[11px] text-[#767C74]">
      <p class="font-mono text-[11px] text-[#232722]">SSC-PROD-2025.04</p>
    </div>
  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById('password');
      const isPass = input.type === 'password';
      input.type = isPass ? 'text' : 'password';
      document.getElementById('eye-icon').classList.toggle('hidden', isPass);
      document.getElementById('eye-slash-icon').classList.toggle('hidden', !isPass);
    }
  </script>
</body>
</html>