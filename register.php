<?php
session_start();
include 'config/db.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Valid email enter karo.";
    } elseif (strlen($password) < 6) {
        $error = "Password kam az kam 6 characters ka ho.";
    } elseif ($password !== $confirm) {
        $error = "Password aur confirm password match nahi kar rahe.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Ye email pehle se registered hai.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hash);
            $stmt->execute();
            $success = "Account ban gaya! Ab login karo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Inventory Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
body { font-family: 'Inter', sans-serif; background-color: #F6F4EF; color: #232722; background-image: radial-gradient(#E4E1D8 1.2px, transparent 1.2px); background-size: 24px 24px; }
h1{ font-family: 'Sora', sans-serif; }
</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-[420px]">
    <div class="flex items-center justify-center gap-3 mb-6">
      <div class="w-[34px] h-[34px] bg-[#E8A33D] rounded-[8px] flex items-center justify-center font-sora font-extrabold text-[#1C2333] text-base shadow-sm">IM</div>
      <div>
        <div class="font-sora font-bold text-[#1C2333]">Inventory Manager</div>
        <div class="text-[11px] text-[#767C74] uppercase tracking-wide">Solar Stock Control</div>
      </div>
    </div>

    <div class="bg-white border border-[#E4E1D8] rounded-[10px] shadow-xl p-7">
      <div class="text-center pb-5 mb-5 border-b border-[#E4E1D8]">
        <h1 class="font-sora font-bold text-lg text-[#1C2333]">Create Account</h1>
        <p class="text-xs text-[#767C74] mt-0.5">Register a new account</p>
      </div>

      <?php if ($success): ?>
        <div class="mb-4 p-3 rounded-lg text-xs font-medium bg-[#E9F3EC] text-[#4C8F63]"><?php echo htmlspecialchars($success); ?>
          <a href="login.php" class="font-semibold underline ml-1">Go to Login</a>
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="mb-4 p-3 rounded-lg text-xs font-medium bg-[#FBE9E2] text-[#BD5B3D]"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" action="register.php" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-[#232722] mb-1.5 uppercase tracking-wide">Full Name</label>
          <input type="text" name="name" required placeholder="Sarim" class="w-full px-3.5 py-2.5 bg-[#FCFBF8] border border-[#E4E1D8] rounded-lg text-sm focus:outline-none focus:border-[#1C2333] focus:ring-2 focus:ring-[#1C2333]/10" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"/>
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#232722] mb-1.5 uppercase tracking-wide">Email</label>
          <input type="email" name="email" required placeholder="you@example.com" class="w-full px-3.5 py-2.5 bg-[#FCFBF8] border border-[#E4E1D8] rounded-lg text-sm focus:outline-none focus:border-[#1C2333] focus:ring-2 focus:ring-[#1C2333]/10" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"/>
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#232722] mb-1.5 uppercase tracking-wide">Password</label>
          <input type="password" name="password" required placeholder="Min 6 characters" class="w-full px-3.5 py-2.5 bg-[#FCFBF8] border border-[#E4E1D8] rounded-lg text-sm focus:outline-none focus:border-[#1C2333] focus:ring-2 focus:ring-[#1C2333]/10"/>
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#232722] mb-1.5 uppercase tracking-wide">Confirm Password</label>
          <input type="password" name="confirm" required placeholder="Repeat password" class="w-full px-3.5 py-2.5 bg-[#FCFBF8] border border-[#E4E1D8] rounded-lg text-sm focus:outline-none focus:border-[#1C2333] focus:ring-2 focus:ring-[#1C2333]/10"/>
        </div>
        <button type="submit" class="w-full py-2.5 bg-[#1C2333] hover:bg-[#283248] text-white font-sora font-semibold text-sm rounded-lg transition-all active:scale-[0.99]">Create Account</button>
      </form>

      <p class="mt-5 pt-4 border-t border-[#E4E1D8] text-center text-xs text-[#767C74]">
        Already have an account?
        <a href="login.php" class="text-[#1C2333] font-semibold hover:text-[#E8A33D] ml-1">Sign In</a>
      </p>
    </div>
  </div>
</body>
</html>