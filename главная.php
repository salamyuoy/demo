<?php
session_start();
$logged_in = !empty($_SESSION['user_id']);
// безопасный вывод значений сессии
@include_once __DIR__ . '/db.php'; // подключение при наличии db.php

// Если db.php не создал $con (mysqli), создаём локальное подключение
if (!isset($con) || !$con instanceof mysqli) {
    $db_host = 'MySQL-5.7';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'demoexam';
    $con = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($con->connect_errno) {
        die("Ошибка подключения к базе данных: (" . $con->connect_errno . ") " . htmlspecialchars($con->connect_error));
    }
    $con->set_charset('utf8mb4');
}

$user_id = $_SESSION['user_id'] ?? null;
$username_raw = $_SESSION['username'] ?? '';
$fullname_raw = $_SESSION['fullname'] ?? '';
$username_safe = htmlspecialchars((string)$username_raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$fullname_safe = htmlspecialchars((string)$fullname_raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

if ($user_id && is_int((int)$user_id)) {
    $stmt = $con->prepare("SELECT fullname FROM users WHERE id = ?");
    if ($stmt) {
        $uid = (int)$user_id;
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $fullname_safe = htmlspecialchars($row['fullname'] ?? $fullname_safe, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Главная — Avtomir</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg:#b7aba9;
      --accent:#a49a97;
      --accent-dark:#8e8380;
      --panel-bg: rgba(255,255,255,0.1);
    }
    *{box-sizing:border-box}
    body {
      margin: 0;
      font-family: "Segoe UI", sans-serif;
      background-color: var(--bg);
      color: #fff;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      overflow-x: hidden;
    }

    /* ===== Анимации появления ===== */
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    .animate-logo {
      animation: fadeInDown 1s ease-out forwards;
      opacity: 0;
    }

    .animate-header {
      animation: fadeInDown 0.8s ease-out 0.3s forwards;
      opacity: 0;
    }

    .animate-slider {
      animation: fadeIn 1s ease-out 0.6s forwards;
      opacity: 0;
    }

    .animate-footer {
      animation: fadeIn 1s ease-out 0.9s forwards;
      opacity: 0;
    }

    /* ===== Верхняя панель ===== */
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 20px;
      border-bottom: 3px solid var(--accent);
      position: relative;
    }

    .logo { display:flex; align-items:center; gap:12px; }
    .logo img { width:110px; height:110px; object-fit:contain; margin-top:-20px; cursor: pointer; transition: transform 0.3s ease; }
    .logo:hover img { transform: scale(1.05); }

    .top-buttons {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 18px;
    }

    .top-buttons button {
      background-color: var(--accent);
      border: none;
      padding: 8px 20px;
      border-radius: 20px;
      font-size: 15px;
      color: #fff;
      cursor: pointer;
      transition: 0.25s;
    }
    .top-buttons button:hover { background-color: var(--accent-dark); }

    .username {
      font-size: 15px;
      color: #fff;
      font-weight: 500;
      margin-left: 12px;
    }

    /* ===== Центральный блок (слайдер) ===== */
    .main-section {
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 30px 0;
      background: url("фон.jpg") no-repeat center/cover;
      border-bottom: 3px solid var(--accent);
    }

    .slider {
      position: relative;
      width: 88%;
      max-width: 1200px;
      height: 460px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--panel-bg);
      border-radius: 25px;
      backdrop-filter: blur(6px);
      box-shadow: 0 0 25px rgba(0, 0, 0, 0.28);
      overflow: hidden;
    }

    .slides { width:100%; height:100%; position:relative; display:flex; align-items:center; justify-content:center; }
    .slide {
      position:absolute;
      inset:0;
      display:flex;
      gap:28px;
      align-items:center;
      justify-content:center;
      padding:20px 28px;
      opacity:0;
      transform:scale(0.98);
      transition: opacity .6s ease, transform .6s ease;
    }
    .slide.active { opacity:1; transform:scale(1); z-index:2; }

    .image-block { flex:1; display:flex; justify-content:center; align-items:center; }
    .image-block img { width:100%; max-width:520px; height:320px; object-fit:cover; border-radius:12px; }

    .title-desc {
      flex:1;
      display:flex;
      flex-direction:column;
      gap:12px;
      background:rgba(0,0,0,0.45);
      padding:20px;
      border-radius:12px;
    }
    .title-desc h2 { margin:0; font-size:22px; }
    .title-desc p { margin:0; font-size:15px; line-height:1.5; color:#eee; text-align:justify; }

    /* ===== Стрелки ===== */
    .arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      font-size: 30px;
      color: #333;
      cursor: pointer;
      background-color: rgba(255,255,255,0.8);
      border-radius: 50%;
      padding: 6px 12px;
      user-select: none;
      transition: 0.2s;
      z-index:5;
    }
    .arrow:hover{ transform: translateY(-50%) scale(1.03); background-color: rgba(255,255,255,1); }
    .arrow.left{ left:18px }
    .arrow.right{ right:18px }

    /* ===== Точки ===== */
    .dots { display:flex; gap:10px; justify-content:center; margin-top:16px; }
    .dot { width:12px; height:12px; border-radius:50%; background:#777; cursor:pointer; transition:.2s; }
    .dot.active { background:#fff; transform:scale(1.15); }

    /* ===== Нижняя панель ===== */
    footer { display:flex; justify-content:space-between; align-items:flex-end; padding:16px 20px; border-top:3px solid var(--accent); margin-top:18px;}
    .socials{display:flex; gap:14px; align-items:center}
    .socials img{width:30px;height:30px;filter:invert(1);cursor:pointer}
    .footer-logo img{width:90px;height:90px;object-fit:contain}

    /* ===== Адаптив ===== */
    @media (max-width: 1000px){
      .slider{height:420px}
      .image-block img{height:260px}
    }
    @media (max-width: 800px){
      .slider{height:auto; padding:18px; flex-direction:column; gap:18px}
      .slide{position:static; display:flex; flex-direction:column; align-items:center; gap:14px; opacity:0; transform:none}
      .slide.active{opacity:1}
      .image-block img{max-width:90%; height:auto}
      .title-desc { width:90%; padding:16px }
      .top-buttons{left:50%;transform:translateX(-50%);gap:10px}
      header{padding:10px}
    }
    @media (max-width:420px){
      .top-buttons button{padding:8px 12px; font-size:14px}
      .logo img{width:80px;height:80px;margin-top:-10px}
      .title-desc h2{font-size:18px}
      .title-desc p{font-size:14px}
    }
  </style>
</head>
<body>

  <header>
    <div class="logo animate-logo">
      <a href="главная.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
        <img src="логотип1.png" alt="Логотип">
      </a>
    </div>

    <div class="top-buttons animate-header" role="navigation" aria-label="Главное меню">
      <?php if ($logged_in): ?>
        <button onclick="location.href='регистрация.php'">Регистрация</button>
        <button onclick="location.href='авторизация.php'">Авторизация</button>
        <button onclick="location.href='формирование.php'">Формирование заявки</button>
        <button onclick="location.href='просмотрзаявок.php'">Просмотр заявки</button>
        <button onclick="location.href='logout.php'">Выйти</button>
      <?php else: ?>
        <button onclick="location.href='регистрация.php'">Регистрация</button>
        <button onclick="location.href='авторизация.php'">Авторизация</button>
      <?php endif; ?>
    </div>

    <?php if (!empty($fullname_safe)): ?>
      <div class="username animate-header">👤 <?= $fullname_safe ?></div>
    <?php elseif (!empty($username_safe)): ?>
      <div class="username animate-header">👤 <?= $username_safe ?></div>
    <?php endif; ?>
  </header>

  <section class="main-section">
    <div class="slider animate-slider" id="slider">
      <div class="arrow left" id="prevBtn" aria-label="Предыдущий">&#10094;</div>
      <div class="arrow right" id="nextBtn" aria-label="Следующий">&#10095;</div>

      <div class="slides">
        <!-- Слайд 1 -->
        <div class="slide active" data-index="1">
          <div class="image-block"><img src="ладавеста.jpg" alt="LADA VESTA 2025"></div>
          <div class="title-desc">
            <h2>LADA VESTA (2025)</h2>
            <p>LADA VESTA 2025 года — это полностью обновленная модель с новым дизайном, современными мультимедиа и улучшенным салоном. Комфортный и технологичный седан для города.</p>
          </div>
        </div>

        <!-- Слайд 2 -->
        <div class="slide" data-index="2">
          <div class="image-block"><img src="чери.png" alt="Chery Tiggo 7 Pro Max"></div>
          <div class="title-desc">
            <h2>CHERY TIGGO 7 PRO MAX</h2>
            <p>Современный компактный кроссовер с ярким дизайном, просторным салоном и богатым набором опций — выгодное предложение в своём классе.</p>
          </div>
        </div>

        <!-- Слайд 3 -->
        <div class="slide" data-index="3">
          <div class="image-block"><img src="порш.png" alt="Porsche 911"></div>
          <div class="title-desc">
            <h2>PORSCHE 911</h2>
            <p>Культовый спортивный автомобиль с мощными двигателями и непревзойденной управляемостью, сочетает престиж и высокие технологии.</p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <div class="dots animate-slider" id="dots" aria-hidden="false">
    <div class="dot active" data-to="1"></div>
    <div class="dot" data-to="2"></div>
    <div class="dot" data-to="3"></div>
  </div>

  <footer class="animate-footer">
    <div class="socials">
      <img src="гео.png" alt="Гео">
      <img src="вк.png" alt="VK">
      <img src="тг.png" alt="TG">
    </div>

    <div class="footer-logo">
      <img src="логотип1.png" alt="Логотип">
      <p style="margin:0;color:#fff">TestDrive</p>
      <p style="margin:0;color:#fff">©2025</p>
    </div>
  </footer>

  <script>
    (function(){
      const slides = Array.from(document.querySelectorAll('.slide'));
      const dots = Array.from(document.querySelectorAll('.dot'));
      const prevBtn = document.getElementById('prevBtn');
      const nextBtn = document.getElementById('nextBtn');
      let idx = 0;
      const total = slides.length;
      let timer = null;
      const INTERVAL = 3000; // 3 секунды

      function show(n){
        slides.forEach((s,i)=> s.classList.toggle('active', i === n));
        dots.forEach((d,i)=> d.classList.toggle('active', i === n));
        idx = n;
      }

      function next(){
        show((idx + 1) % total);
      }
      function prev(){
        show((idx - 1 + total) % total);
      }

      // стрелки
      prevBtn.addEventListener('click', ()=>{ prev(); resetTimer(); });
      nextBtn.addEventListener('click', ()=>{ next(); resetTimer(); });

      // точки
      dots.forEach(d => d.addEventListener('click', e => {
        const to = Number(e.currentTarget.dataset.to) - 1;
        show(to);
        resetTimer();
      }));

      // автоплей
      function startTimer(){
        timer = setInterval(next, INTERVAL);
      }
      function stopTimer(){
        if(timer){ clearInterval(timer); timer = null; }
      }
      function resetTimer(){
        stopTimer();
        startTimer();
      }

      // пауза при наведении
      const slider = document.getElementById('slider');
      slider.addEventListener('mouseenter', stopTimer);
      slider.addEventListener('mouseleave', startTimer);

      // запустить
      show(0);
      startTimer();
    })();
  </script>

</body>
</html>