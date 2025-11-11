<?php
// просмотр_заявок.php
session_start();

// доступ только для авторизованных
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: авторизация.php');
    exit;
}

// подключаем DB (если есть)
@include_once __DIR__ . '/db.php';

// если db.php не определил $con, создаём mysqli (на всякий случай)
if (!isset($con) || !$con instanceof mysqli) {
    $db_host = 'MySQL-5.7'; // заменить на localhost при необходимости
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'demoexam';
    $con = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($con->connect_errno) {
        die("Ошибка подключения к базе данных: (" . $con->connect_errno . ") " . htmlspecialchars($con->connect_error));
    }
    $con->set_charset('utf8mb4');
}

$user_id = (int) $_SESSION['user_id'];

// безопасные имена для шапки
$username_safe = htmlspecialchars((string)($_SESSION['username'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$fullname_safe = htmlspecialchars((string)($_SESSION['fullname'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Получаем заявки пользователя
$requests = [];
$stmt = $con->prepare("SELECT id, address, contacts, driver_license, date, car, payment, status, admin_message FROM requests WHERE user_id = ? ORDER BY date DESC, id DESC");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $requests[] = $row;
    }
    $stmt->close();
} else {
    // подготовка запроса не удалась
    $error = "Ошибка запроса: " . htmlspecialchars($con->error);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Просмотр заявок — Avtomir</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { margin:0; font-family:"Segoe UI",sans-serif; background-color:#b7aba9; color:#fff; overflow-x:hidden; }
    header{display:flex;align-items:center;justify-content:space-between;padding:10px 50px;border-bottom:3px solid #a49a97;position:relative;}
    .logo img{width:150px;height:150px;object-fit:contain;margin-top:-30px;}
    .top-buttons{position:absolute;left:50%;transform:translateX(-50%);display:flex;gap:15px;}
    .top-buttons button{background:#a49a97;border:none;padding:10px 30px;border-radius:20px;color:#fff;cursor:pointer}
    .top-buttons button:hover{background:#8e8380}
    .username{font-size:16px;color:#fff;font-weight:500;position:absolute;right:40px;top:40px;}
    .main-section{display:flex;align-items:flex-start;justify-content:flex-start;background:url("фон.jpg") no-repeat center/cover;padding:40px 80px;height:75vh;border-bottom:3px solid #a49a97;}
    /* левый блок — список заявок */
    .requests-list { width: 520px; background: rgba(0,0,0,0.55); border-radius: 16px; padding: 18px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
    .requests-list h2 { margin:0 0 12px 0; font-size:22px; color:#fff; }
    .request-card { background: rgba(255,255,255,0.06); border-radius:12px; padding:12px; margin-bottom:12px; border:1px solid rgba(255,255,255,0.08); }
    .request-card .row { display:flex; justify-content:space-between; gap:12px; align-items:center; }
    .request-card p { margin:6px 0; color:#eee; font-size:14px; }
    .label { color:#cfcfcf; font-weight:600; width:160px; }
    .value { color:#fff; flex:1; text-align:left; }
    .status-badge { padding:6px 10px; border-radius:18px; font-weight:700; font-size:13px; }
    .status-на_рассмотрении { background: rgba(255,193,7,0.15); color:#ffd54a; border:1px solid rgba(255,193,7,0.2); }
    .status-одобрено { background: rgba(40,167,69,0.12); color:#a8ffb2; border:1px solid rgba(40,167,69,0.2); }
    .status-отклонено { background: rgba(220,53,69,0.12); color:#ff9fa6; border:1px solid rgba(220,53,69,0.2); }

    /* правый блок — сводка / заголовок */
    .summary { margin-left:40px; width:420px; background: rgba(0,0,0,0.55); border-radius:16px; padding:18px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
    .summary h3 { margin:0 0 10px 0; color:#fff; }
    .summary .big { font-size:18px; color:#fff; margin-bottom:12px; display:inline-block; padding:8px 14px; border-radius:14px; background:rgba(255,255,255,0.06); }
    .summary p { color:#ddd; margin:8px 0; }

    footer{display:flex;justify-content:space-between;align-items:flex-end;padding:20px 50px;border-top:3px solid #a49a97}
    .socials img{width:30px;height:30px;filter:invert(1);margin-right:10px}
    .footer-logo img{width:100px;height:100px;object-fit:contain}
  </style>
</head>
<body>

<header>
  <div class="logo"><img src="логотип1.png" alt="Логотип"></div>

  <div class="top-buttons">
    <button onclick="location.href='главная.php'">Главная</button>
    <button onclick="location.href='logout.php'">Выйти</button>
  </div>

  <?php if (!empty($fullname_safe)): ?>
    <div class="username">👤 <?= $fullname_safe ?></div>
  <?php elseif (!empty($username_safe)): ?>
    <div class="username">👤 <?= $username_safe ?></div>
  <?php endif; ?>
</header>

<section class="main-section">
  <div class="requests-list">
    <h2>Активные заявки</h2>

    <?php if (!empty($error)): ?>
      <div style="color:#ffdede; background:rgba(120,0,0,0.2); padding:10px; border-radius:8px; margin-bottom:12px;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if (count($requests) === 0): ?>
      <p style="color:#eee; padding:12px 0;">У вас пока нет заявок. Чтобы создать — перейдите в «Формирование заявки».</p>
    <?php else: ?>
      <?php foreach ($requests as $r): 
        $status = mb_strtolower(trim($r['status'] ?? 'на рассмотрении'));
        // нормализуем класс статуса (без пробелов)
        $status_class = 'status-' . str_replace([' ', '_'], ['-', ''], $status);
      ?>
        <div class="request-card">
          <div class="row">
            <div style="display:flex; align-items:center; gap:12px;">
              <span class="label">Статус</span>
              <span class="status-badge <?= htmlspecialchars($status_class) ?>"><?= htmlspecialchars($r['status'] ?: 'на рассмотрении') ?></span>
            </div>
            <div style="font-size:13px;color:#ddd">Заявка № <?= (int)$r['id'] ?></div>
          </div>

          <p><span class="label">Адрес:</span> <span class="value"><?= htmlspecialchars($r['address']) ?></span></p>
          <p><span class="label">Телефон / e-mail:</span> <span class="value"><?= htmlspecialchars($r['contacts']) ?></span></p>
          <p><span class="label">Дата:</span> <span class="value"><?= htmlspecialchars($r['date']) ?></span></p>
          <p><span class="label">Данные ВУ:</span> <span class="value"><?= htmlspecialchars($r['driver_license']) ?></span></p>
          <p><span class="label">Автомобиль:</span> <span class="value"><?= htmlspecialchars($r['car']) ?></span></p>
          <p><span class="label">Оплата:</span> <span class="value"><?= htmlspecialchars($r['payment']) ?></span></p>

          <?php if (!empty($r['admin_message'])): ?>
            <p style="margin-top:8px;"><span class="label">Сообщение администрации:</span> <span class="value"><?= htmlspecialchars($r['admin_message']) ?></span></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <aside class="summary">
    <h3>Просмотр заявок</h3>
    <div class="big">Статус заказа: <strong>
      <?php
        // если есть хотя бы одна заявка — показываем статус самой свежей
        if (count($requests) > 0) {
            echo htmlspecialchars($requests[0]['status'] ?: 'на рассмотрении');
        } else {
            echo '—';
        }
      ?>
    </strong></div>

    <p>Здесь отображаются все ваши заявки: адрес, контакты, дата, данные водительского удостоверения и статус. <br><br>
    Если у заявки появился комментарий от администратора — он будет виден в карточке.</p>

    <p style="margin-top:12px;"><button onclick="location.href='формирование.php'" style="background:#a49a97;border:none;padding:10px 18px;border-radius:16px;color:#fff;cursor:pointer">Создать новую заявку</button></p>
  </aside>
</section>

<footer>
  <div class="socials">
    <img src="вк.png" alt="VK">
    <img src="гео.png" alt="WhatsApp">
    <img src="тг.png" alt="Telegram">
  </div>

  <div class="footer-logo">
    <img src="логотип1.png" alt="Логотип">
    <p>TestDrive</p>
    <p>©2025</p>
  </div>
</footer>

</body>
</html>
