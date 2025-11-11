<?php
session_start();

$success = false;
$error = "";
$host = 'MySQL-5.7';
$dbname = 'demoexam';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($login === '' || $pass === '') {
        $error = "Пожалуйста, заполните все поля.";
    } else {
        // спец-пользователь: администратор (локально)
        if ($login === 'adminka' && $pass === 'password') {
            // ставим роль admin и перенаправляем сразу
            $_SESSION['user_id'] = 0; // 0 - условный admin id
            $_SESSION['username'] = 'Администратор';
            $_SESSION['role'] = 'admin';
            header("Location: admin_panel.php");
            exit();
        }

        // обычный пользователь
        $stmt = $pdo->prepare("SELECT id, login, fullname FROM users WHERE login = ? AND password = ?");
        $stmt->execute([$login, $pass]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['login'];
            $_SESSION['fullname'] = $user['fullname'] ?? '';
            $_SESSION['role'] = 'user';
            // сразу переходим на формирование заявки
            header("Location: формирование.php");
            exit();
        } else {
            $error = "Неверный логин или пароль!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Авторизация — Avtomir</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg:#b7aba9;
      --accent:#a49a97;
      --accent-dark:#8e8380;
      font-size: clamp(14px, 1.1vw + 0.6rem, 18px);
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:"Segoe UI",system-ui,-apple-system,Arial,sans-serif;background:var(--bg);color:#fff}
    header{display:flex;align-items:center;justify-content:space-between;padding:clamp(8px,1.2vh,22px) clamp(12px,4vw,50px);border-bottom:3px solid var(--accent);position:relative}
    .logo img{width:clamp(64px,9vw,150px);height:auto;object-fit:contain;margin-top:clamp(-10px,-1.5vh,-30px)}
    .top-buttons{position:absolute;left:50%;transform:translateX(-50%)} .top-buttons button{background:var(--accent);border:none;padding:clamp(6px,.8vh,12px) clamp(10px,2vw,30px);border-radius:20px;color:#fff;cursor:pointer}
    .main-section{display:flex;align-items:center;justify-content:center;background:url("фон.jpg") no-repeat center/cover;padding:clamp(30px,4vh,60px) 0;height:auto;border-bottom:3px solid var(--accent)}
    .login-form{width:clamp(300px,40vw,420px);background:rgba(0,0,0,.55);border-radius:18px;padding:clamp(20px,3vw,36px);display:flex;flex-direction:column;gap:12px}
    .login-form h2{text-align:center;margin:0 0 6px 0;font-size:clamp(1.1rem,2.2vw,1.6rem)}
    .login-form label{font-size:.95rem;color:#f2f2f2}
    .login-form input{width:100%;padding:clamp(10px,1.2vh,12px);border:none;border-radius:10px;font-size:1rem;background:#fff;color:#333;outline:none}
    .login-form button{margin-top:8px;background:var(--accent);border:none;padding:12px;border-radius:18px;color:#fff;font-size:1rem;cursor:pointer}
    .msg{position:fixed;top:18px;right:18px;padding:12px 18px;border-radius:10px;font-size:1rem;opacity:0;transform:translateY(-10px);transition:all .3s;z-index:1000}
    .msg.show{opacity:1;transform:translateY(0)}
    .msg.error{background:rgba(200,0,0,.9);color:#fff}
    .msg.success{background:rgba(0,128,0,.9);color:#fff}
    footer{display:flex;justify-content:space-between;align-items:flex-end;padding:clamp(10px,1.8vh,20px) clamp(12px,4vw,50px);border-top:3px solid var(--accent);margin-top:18px}
    @media (max-width:480px){.login-form{width:88%;padding:16px}}
  </style>
</head>
<body>

<header>
  <div class="logo"><img src="логотип1.png" alt="Логотип"></div>
  <div class="top-buttons"><button>Авторизация</button></div>
</header>

<section class="main-section">
  <form class="login-form" method="POST" action="">
    <h2>Авторизация</h2>

    <label for="login">Введите логин</label>
    <input required type="text" name="login" id="login" placeholder="Ваш логин" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">

    <label for="password">Введите пароль</label>
    <input required type="password" name="password" id="password" placeholder="Ваш пароль">

    <button type="submit">Войти</button>
  </form>
</section>

<?php if ($error): ?>
  <div class="msg error show" id="msgError">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<footer>
  <div class="socials">
    <img src="вк.png" alt="VK" style="width:30px;height:30px;filter:invert(1);margin-right:12px">
    <img src="гео.png" alt="WhatsApp" style="width:30px;height:30px;filter:invert(1);margin-right:12px">
    <img src="тг.png" alt="Telegram" style="width:30px;height:30px;filter:invert(1)">
  </div>

  <div class="footer-logo">
    <img src="логотип1.png" alt="Логотип" style="width:90px;height:auto;object-fit:contain">
    <p style="margin:0;color:#fff">TestDrive</p>
    <p style="margin:0;color:#fff">©2025</p>
  </div>
</footer>

<script>
  // скрываем сообщение через 3.5 сек
  setTimeout(() => { document.querySelectorAll('.msg.show').forEach(el => el.classList.remove('show')); }, 3500);
</script>

</body>
</html>
