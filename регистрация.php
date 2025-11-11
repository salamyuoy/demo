<?php
// регистрация.php
session_start();

$success = false;
$error = '';
$host = 'MySQL-5.7';
$dbname = 'demoexam';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // В разработке можно показывать ошибку, в продакшене — логировать
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Простая валидация
    if ($login === '' || $pass === '' || $fullname === '' || $phone === '' || $email === '') {
        $error = 'Все поля обязательны для заполнения!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный email.';
    } else {
        // Можно дополнительно проверить уникальность логина
        $check = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $check->execute([$login]);
        if ($check->fetch()) {
            $error = 'Пользователь с таким логином уже существует.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (login, password, fullname, phone, email) VALUES (?, ?, ?, ?, ?)");
            $success = $stmt->execute([$login, $pass, $fullname, $phone, $email]);
            if (!$success) {
                $error = 'Ошибка при сохранении в базе данных.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Регистрация — Avtomir</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg:#b7aba9;
      --accent:#a49a97;
      --accent-dark:#8e8380;
      --accent-darker:#7a706d;
      --panel-bg: rgba(0,0,0,0.55);
      --white: #fff;
      --error-color: #ff6b6b;
      font-size: clamp(14px, 1.1vw + 0.6rem, 18px);
    }
    *{box-sizing:border-box}
    html,body{height:100%;margin:0;padding:0}
    body{
      font-family:"Segoe UI",system-ui,-apple-system,Arial,sans-serif;
      background:var(--bg);
      color:var(--white);
      -webkit-font-smoothing:antialiased;
    }

    /* Header */
    header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding: clamp(8px,1.2vh,22px) clamp(12px,4vw,50px);
      border-bottom:3px solid var(--accent);
      position:relative;
    }
    .logo img{ width: clamp(64px,9vw,150px); height:auto; object-fit:contain; margin-top: clamp(-10px,-1.5vh,-30px); }

    .top-buttons{
      position:absolute;
      left:50%;
      transform:translateX(-50%);
    }
    .top-buttons button{
      background:var(--accent);
      border:none;
      padding: clamp(6px,.8vh,12px) clamp(10px,2vw,30px);
      border-radius:20px;
      color:#fff;
      cursor:pointer;
      font-size: .95rem;
      transition: background 0.3s ease;
    }
    .top-buttons button:hover{ background:var(--accent-dark) }

    /* Main */
    .main-section{
      display:flex;
      align-items:center;
      justify-content:center;
      background: url("фон.jpg") no-repeat center/cover;
      padding: clamp(30px,4vh,60px) 0;
      min-height: calc(100vh - 220px);
      border-bottom:3px solid var(--accent);
    }

    .register-form{
      width: clamp(300px, 46vw, 460px);
      background: var(--panel-bg);
      border-radius: clamp(12px, 1.4vw, 25px);
      padding: clamp(20px, 3.2vw, 36px);
      box-shadow: 0 8px 28px rgba(0,0,0,0.45);
      display:flex;
      flex-direction:column;
      gap: clamp(10px, 1.6vh, 16px);
    }

    .register-form h2{
      margin:0;
      text-align:center;
      font-size: clamp(1.1rem, 2.2vw, 1.6rem);
    }

    .register-form label{
      font-size: .95rem;
      color:#f2f2f2;
    }

    .register-form input{
      width:100%;
      padding: clamp(10px,1.2vh,14px);
      border: 2px solid transparent;
      border-radius:10px;
      font-size:1rem;
      background:#fff;
      color:#333;
      outline:none;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .register-form input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(164, 154, 151, 0.3);
    }

    .register-form input.error {
      border-color: var(--error-color);
      box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.3);
      animation: shake 0.5s ease-in-out;
    }

    /* Анимация кнопки */
    .register-form button{
      margin-top:6px;
      background:var(--accent);
      border:none;
      padding: clamp(10px,1.2vh,14px);
      border-radius:20px;
      font-size:1rem;
      color:#fff;
      cursor:pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .register-form button:hover{ 
      background:var(--accent-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .register-form button:active{
      animation: buttonClick 0.3s ease;
    }

    /* Анимации */
    @keyframes buttonClick {
      0% {
        transform: scale(1);
        background: var(--accent);
      }
      50% {
        transform: scale(0.95);
        background: var(--accent-darker);
      }
      100% {
        transform: scale(1);
        background: var(--accent-dark);
      }
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    /* Messages */
    .msg{
      position:fixed;
      top:18px;
      right:18px;
      padding:12px 18px;
      border-radius:10px;
      font-size:1rem;
      color:#fff;
      z-index:1000;
      opacity:0;
      transform:translateY(-10px);
      transition: all .28s;
    }
    .msg.show{ opacity:1; transform:translateY(0); }
    .msg.success{ background: rgba(0,128,0,0.9); }
    .msg.error{ background: rgba(200,0,0,0.9); }

    /* Footer */
    footer{
      display:flex;
      justify-content:space-between;
      align-items:flex-end;
      padding: clamp(12px,1.8vh,20px) clamp(12px,4vw,50px);
      border-top:3px solid var(--accent);
    }
    .socials img{ width: clamp(22px,2.8vw,30px); filter:invert(1); margin-right:10px }
    .footer-logo img{ width: clamp(60px,8vw,100px) }

    /* Responsive */
    @media (max-width:800px){
      .register-form{ width: 88%; padding:16px; }
      .top-buttons{ left:50%; transform:translateX(-50%); }
      header{ padding:12px }
    }
    @media (max-width:420px){
      :root{ font-size: clamp(13px, 2.4vw, 16px) }
      .logo img{ width: clamp(50px, 18vw, 80px) }
      .register-form{ padding:12px }
    }
  </style>
</head>
<body>

  <header>
    <div class="logo">
      <img src="логотип1.png" alt="Логотип">
    </div>

    <div class="top-buttons">
      <button onclick="location.href='главная.php'">Главная</button>
      <button onclick="location.href='авторизация.php'">Авторизация</button>
    </div>
  </header>

  <section class="main-section" aria-labelledby="regTitle">
    <form id="regForm" class="register-form" method="POST" action="">
      <h2 id="regTitle">Регистрация</h2>

      <label for="login">Введите логин</label>
      <input id="login" name="login" type="text" required placeholder="Логин" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">

      <label for="password">Введите пароль</label>
      <input id="password" name="password" type="password" required placeholder="Минимум 6 символов">

      <label for="fullname">Введите ФИО</label>
      <input id="fullname" name="fullname" type="text" required placeholder="Фамилия Имя Отчество" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">

      <label for="phone">Введите телефон</label>
      <input id="phone" name="phone" type="tel" required placeholder="+7(XXX)-XXX-XX-XX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

      <label for="email">Введите эл. почту</label>
      <input id="email" name="email" type="email" required placeholder="name@gmail.com / name@mail.ru" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

      <button type="submit" id="submitBtn">Зарегистрироваться</button>
    </form>
  </section>

  <?php if ($success): ?>
    <div class="msg success show" id="successMessage">✅ Регистрация прошла успешно!</div>
  <?php elseif ($error): ?>
    <div class="msg error show" id="errorMessage">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <footer>
    <div class="socials" aria-hidden="false">
      <img src="вк.png" alt="VK">
      <img src="гео.png" alt="WhatsApp">
      <img src="тг.png" alt="Telegram">
    </div>

    <div class="footer-logo">
      <img src="логотип1.png" alt="Логотип">
      <p style="margin:0;color:#fff">TestDrive</p>
      <p style="margin:0;color:#fff">©2025</p>
    </div>
  </footer>

  <script>
    // скрываем сообщения через 3.5 секунды
    setTimeout(() => {
      document.getElementById('successMessage')?.classList?.remove('show');
      document.getElementById('errorMessage')?.classList?.remove('show');
    }, 3500);

    // Анимация кнопки при клике
    document.getElementById('submitBtn')?.addEventListener('click', function(e) {
      // Запускаем анимацию кнопки
      this.style.animation = 'buttonClick 0.3s ease';
      
      // Сбрасываем анимацию после завершения
      setTimeout(() => {
        this.style.animation = '';
      }, 300);
    });

    // Валидация формы
    document.getElementById('regForm')?.addEventListener('submit', function(e){
      let hasErrors = false;
      
      // Сбрасываем предыдущие ошибки
      document.querySelectorAll('input.error').forEach(input => {
        input.classList.remove('error');
      });
      
      // Проверка пароля
      const pwd = document.getElementById('password').value || '';
      if (pwd.length > 0 && pwd.length < 6) {
        hasErrors = true;
        document.getElementById('password').classList.add('error');
      }
      
      // Проверка email
      const email = document.getElementById('email').value || '';
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (email && !emailRegex.test(email)) {
        hasErrors = true;
        document.getElementById('email').classList.add('error');
      }
      
      // Проверка обязательных полей
      const requiredFields = ['login', 'fullname', 'phone'];
      requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
          hasErrors = true;
          field.classList.add('error');
        }
      });
      
      if (hasErrors) {
        e.preventDefault();
        
        // Показываем сообщение об ошибке
        const existingMsg = document.getElementById('errorMessage');
        if (existingMsg) {
          existingMsg.textContent = 'Пожалуйста, исправьте ошибки в форме.';
          existingMsg.classList.add('show');
        } else {
          const div = document.createElement('div');
          div.id = 'errorMessage';
          div.className = 'msg error show';
          div.textContent = 'Пожалуйста, исправьте ошибки в форме.';
          document.body.appendChild(div);
        }
        
        // Скрываем сообщение через 3.5 секунды
        setTimeout(() => {
          document.getElementById('errorMessage')?.classList?.remove('show');
        }, 3500);
      }
    });

    // Убираем класс ошибки при фокусе на поле
    document.querySelectorAll('input').forEach(input => {
      input.addEventListener('focus', function() {
        this.classList.remove('error');
      });
    });
  </script>

</body>
</html>