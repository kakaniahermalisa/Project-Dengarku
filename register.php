<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'koneksi.php'; // pastikan file ini mengatur $pdo

// Cek koneksi PDO
if (!$pdo) {
    die("Koneksi ke database gagal.");
}

// Debug input POST (sementara, bisa dihapus setelah testing)
file_put_contents('debug_post.txt', print_r($_POST, true));

// Fungsi-fungsi validasi
function cleanInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
function validatePassword($password) {
    return strlen($password) >= 6;
}
function validateBirthDate($day, $month, $year) {
    return checkdate((int)$month, (int)$day, (int)$year);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    $success = false;

    // Ambil input
    $email = cleanInput($_POST['email'] ?? '');
    $confirmEmail = cleanInput($_POST['confirm_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = cleanInput($_POST['name'] ?? '');
    $birthDay = cleanInput($_POST['birth_day'] ?? '');
    $birthMonth = cleanInput($_POST['birth_month'] ?? '');
    $birthYear = cleanInput($_POST['birth_year'] ?? '');
    $gender = cleanInput($_POST['gender'] ?? '');
    $marketing = isset($_POST['marketing']) ? 1 : 0;

    // Validasi
    if (empty($email) || !validateEmail($email)) $errors[] = "Email tidak valid.";
    if ($email !== $confirmEmail) $errors[] = "Email dan konfirmasi tidak cocok.";
    if (empty($password) || !validatePassword($password)) $errors[] = "Password minimal 6 karakter.";
    if (empty($name)) $errors[] = "Nama tidak boleh kosong.";
    if (!validateBirthDate($birthDay, $birthMonth, $birthYear)) $errors[] = "Tanggal lahir tidak valid.";
    if (!in_array($gender, ['pria', 'wanita', 'no-binder'])) $errors[] = "Gender tidak valid.";

    // Cek email sudah terdaftar?
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email sudah terdaftar.";
        }
    }

    // Simpan data
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $birthDate = sprintf("%04d-%02d-%02d", $birthYear, $birthMonth, $birthDay);
            $stmt = $pdo->prepare("
                INSERT INTO users (email, password, name, birth_date, gender, marketing_consent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$email, $hashedPassword, $name, $birthDate, $gender, $marketing]);

            $success = true;
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

        } catch (PDOException $e) {
            $errors[] = "Gagal menyimpan data.";
            error_log("INSERT error: " . $e->getMessage());
        }
    }

    // JSON untuk AJAX
    if ($_POST['ajax'] ?? '' === '1') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $success ? "Pendaftaran berhasil! Selamat datang, $name!" : null,
            'errors' => $errors,
            'redirect' => $success ? 'ProjectDengarku.html' : null
        ]);
        exit;
    }

    // Jika bukan AJAX
    if ($success) {
        header("Location: ProjectDengarku.html?success=1&name=" . urlencode($name));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - DengarKu</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', Arial, sans-serif;
      background-color: #f8f8f8;
      margin: 0;
      padding: 20px;
      line-height: 1.6;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
      background: white;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    h1 {
      text-align: center;
      font-size: 32px;
      font-weight: 700;
      margin-bottom: 40px;
      color: #333;
      letter-spacing: 1px;
    }

    .error-messages {
      background-color: #ffebee;
      border: 1px solid #f44336;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
      color: #d32f2f;
    }

    .error-messages ul {
      margin: 0;
      padding-left: 20px;
    }

    .signup-form {
      display: flex;
      gap: 40px;
      flex-wrap: wrap;
    }

    .form-left, .form-right {
      flex: 1;
      min-width: 300px;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
      font-size: 16px;
    }

    input[type="email"], 
    input[type="password"], 
    input[type="text"], 
    select {
      width: 100%;
      padding: 15px;
      border: 2px solid #f8bbd0;
      border-radius: 8px;
      font-size: 16px;
      margin-bottom: 20px;
      background-color: #f8bbd0;
      color: #333;
      box-sizing: border-box;
    }

    input::placeholder {
      color: #999;
    }

    input:focus, select:focus {
      outline: none;
      border-color: #f48fb1;
      background-color: #f48fb1;
      color: white;
    }

    input:focus::placeholder {
      color: rgba(255,255,255,0.7);
    }

    small {
      display: block;
      color: #666;
      font-size: 14px;
      margin-top: -15px;
      margin-bottom: 20px;
    }

    .birth-row {
      display: flex;
      gap: 10px;
      margin-bottom: 25px;
    }

    .birth-input {
      flex: 1;
      margin-bottom: 0;
    }

    select {
      flex: 2;
      margin-bottom: 0;
      cursor: pointer;
    }

    .gender-row {
      display: flex;
      gap: 20px;
      margin-bottom: 25px;
      flex-wrap: wrap;
    }

    .gender-row label {
      display: flex;
      align-items: center;
      font-weight: 400;
      cursor: pointer;
      margin-bottom: 0;
    }

    .gender-row input[type="radio"] {
      width: auto;
      margin-right: 8px;
      margin-bottom: 0;
      accent-color: #f48fb1;
    }

    .checkbox-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 30px;
    }

    .checkbox-row input[type="checkbox"] {
      width: auto;
      margin: 0;
      accent-color: #f48fb1;
      margin-top: 3px;
    }

    .checkbox-row label {
      font-weight: 400;
      font-size: 14px;
      margin: 0;
      cursor: pointer;
      line-height: 1.4;
    }

    .form-footer {
      width: 100%;
      text-align: center;
      margin-top: 30px;
    }

    .form-footer p {
      font-size: 14px;
      color: #666;
      line-height: 1.5;
      margin-bottom: 30px;
    }

    .form-footer a {
      color: #f48fb1;
      text-decoration: none;
    }

    .form-footer a:hover {
      text-decoration: underline;
    }

    .btn-daftar {
      background: #f48fb1;
      color: white;
      border: none;
      padding: 15px 80px;
      font-size: 18px;
      font-weight: 600;
      border-radius: 50px;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-bottom: 20px;
    }

    .btn-daftar:hover {
      background: #e91e63;
      transform: translateY(-2px);
    }

    .btn-daftar:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }

    .login-link {
      font-size: 16px;
      color: #333;
    }

    .login-link a {
      color: #f48fb1;
      font-weight: 600;
    }

    @media (max-width: 768px) {
      .signup-form {
        flex-direction: column;
        gap: 20px;
      }
      
      .form-left, .form-right {
        min-width: auto;
      }
      
      .container {
        padding: 20px;
      }
      
      h1 {
        font-size: 24px;
      }
      
      .gender-row {
        flex-direction: column;
        gap: 10px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>SIGN UP WITH YOUR EMAIL ADDRESS</h1>
    
    <?php if (!empty($errors)): ?>
    <div class="error-messages">
      <strong>Terjadi kesalahan:</strong>
      <ul>
        <?php foreach ($errors as $error): ?>
          <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
    
    <form class="signup-form" method="POST" action="">
      <div class="form-left">
        <label for="email">Apa email kamu?</label>
        <input type="email" id="email" name="email" placeholder="Masukan email kamu" 
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        
        <label for="confirm-email">Konfirmasi email kamu</label>
        <input type="email" id="confirm-email" name="confirm_email" placeholder="Konfirmasi ulang email kamu" 
               value="<?php echo isset($_POST['confirm_email']) ? htmlspecialchars($_POST['confirm_email']) : ''; ?>" required>
        
        <label for="password">Buat kata sandi</label>
        <input type="password" id="password" name="password" placeholder="Buat kata sandi" required>
        
        <label for="name">Siapa namamu?</label>
        <input type="text" id="name" name="name" placeholder="Masukan nama profil" 
               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
        <small>Ini akan muncul di profil kamu.</small>
      </div>
      
      <div class="form-right">
        <label>Kapan tanggal lahir kamu?</label>
        <div class="birth-row">
          <input type="text" name="birth_day" maxlength="2" placeholder="DD" class="birth-input" 
                 value="<?php echo isset($_POST['birth_day']) ? htmlspecialchars($_POST['birth_day']) : ''; ?>" required>
          <select name="birth_month" required>
            <option value="">Bulan</option>
            <?php
            $months = [
              1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
              5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
              9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            $selectedMonth = $_POST['birth_month'] ?? '';
            foreach ($months as $value => $name):
            ?>
              <option value="<?php echo $value; ?>" <?php echo $selectedMonth == $value ? 'selected' : ''; ?>>
                <?php echo $name; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="birth_year" maxlength="4" placeholder="YYYY" class="birth-input" 
                 value="<?php echo isset($_POST['birth_year']) ? htmlspecialchars($_POST['birth_year']) : ''; ?>" required>
        </div>
        
        <label>Apa gender kamu?</label>
        <div class="gender-row">
          <label><input type="radio" name="gender" value="pria" 
                        <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'pria') ? 'checked' : ''; ?> required> Pria</label>
          <label><input type="radio" name="gender" value="wanita" 
                        <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'wanita') ? 'checked' : ''; ?>> Wanita</label>
          <label><input type="radio" name="gender" value="no-binder" 
                        <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'no-binder') ? 'checked' : ''; ?>> No-binder</label>
        </div>
        
        <div class="checkbox-row">
          <input type="checkbox" id="marketing" name="marketing" 
                 <?php echo isset($_POST['marketing']) ? 'checked' : ''; ?>>
          <label for="marketing">Bagikan data pendaftaran saya kepada penyedia konten. Dengarku untuk keperluan pemasaran</label>
        </div>
      </div>
      
      <div class="form-footer">
        <p>
          Dengan mengekklik Daftar, kamu menyetujui 
          <a href="#">Syarat dan Ketentuan Pengguna</a> Dengarku<br>
          Untuk mengetahui selengkapnya tentang cara Spotify mengumpulkan, memakai, membagikan, dan melindungi data pribadi kamu, silakan baca 
          <a href="#">Kebijakan Privasi</a> Dengarku
        </p>
        <button type="submit" class="btn-daftar" id="btn-submit">DAFTAR</button>
        <p class="login-link">Punya akun? <a href="ProjectDengarku.html">Masuk.</a></p>
      </div>
    </form>
  </div>

  <script>
    // Form validation dengan AJAX
    document.querySelector('.signup-form').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const submitBtn = document.getElementById('btn-submit');
      const originalText = submitBtn.textContent;
      
      // Disable button dan ubah text
      submitBtn.disabled = true;
      submitBtn.textContent = 'MENDAFTAR...';
      
      // Prepare form data
      const formData = new FormData(this);
      formData.append('ajax', '1');
      
      // Send AJAX request
      fetch('RegisterDengarku.html', {
  method: 'POST',
  body: formData
})
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(data.message);
          window.location.href = data.redirect;
        } else {
          let errorMsg = 'Terjadi kesalahan:\n';
          data.errors.forEach(error => {
            errorMsg += '• ' + error + '\n';
          });
          alert(errorMsg);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('YEYYYY KAMU BERHASIL DAFTAR, DI CATAT YA PASSWORD NYA! JANGAN SAMPE LUPA (╥﹏╥)˖⁺‧₊˚♡˚₊‧⁺˖ .');
        // Submit form normally jika AJAX gagal
        this.submit();
      })
      .finally(() => {
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      });
    });

    // Number-only input for birth date
    document.querySelectorAll('.birth-input').forEach(input => {
      input.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
      });
    });

    // Real-time email confirmation validation
    document.getElementById('confirm-email').addEventListener('input', function() {
      const email = document.getElementById('email').value;
      const confirmEmail = this.value;
      
      if (confirmEmail && email !== confirmEmail) {
        this.style.borderColor = '#f44336';
      } else {
        this.style.borderColor = '#f8bbd0';
      }
    });
  </script>
</body>
</html>