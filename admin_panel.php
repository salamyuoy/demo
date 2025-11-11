<?php
// admin_panel.php
session_start();

// Только администратор имеет доступ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: авторизация.php');
    exit;
}

// Подключение к БД
@include_once __DIR__ . '/db.php';
if (!isset($con) || !$con instanceof mysqli) {
    $con = new mysqli("MySQL-5.7", "root", "", "demoexam");
    if ($con->connect_errno) {
        die("Ошибка подключения к БД: " . htmlspecialchars($con->connect_error));
    }
    $con->set_charset('utf8mb4');
}

// Если пришёл AJAX-запрос на обновление — обработаем и вернём JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_request') {
    // ожидаем id, status, admin_message
    $id = (int)($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $admin_message = trim($_POST['admin_message'] ?? '');

    // простая валидация статуса (чтобы не слать случайные значения)
    $allowed_status = ['в ожидании','одобрено','отклонено','выполнено'];
    if ($id <= 0 || !in_array($status, $allowed_status, true)) {
        echo json_encode(['success' => false, 'error' => 'Неверные данные']);
        exit;
    }

    $stmt = $con->prepare("UPDATE requests SET status = ?, admin_message = ? WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Ошибка подготовки запроса: ' . $con->error]);
        exit;
    }
    $stmt->bind_param("ssi", $status, $admin_message, $id);
    $ok = $stmt->execute();
    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Получаем заявки (всех пользователей)
$sql = "SELECT r.id, r.address, r.contacts, r.driver_license, r.date, r.car, r.payment, r.status, r.admin_message, r.user_id, u.fullname
        FROM requests r
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.date DESC";
$res = $con->query($sql);
$requests = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$username_safe = htmlspecialchars($_SESSION['username'] ?? 'Администратор', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Панель администратора — Avtomir</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root {
      --sidebar-width: 250px;
      --sidebar-collapsed-width: 70px;
      --accent-color: #a49a97;
      --accent-dark: #8e8380;
      --bg-color: #b7aba9;
      --panel-bg: rgba(0,0,0,0.55);
    }
    
    body {
      margin: 0; 
      font-family: "Segoe UI", sans-serif; 
      background: var(--bg-color); 
      color: #fff;
      transition: margin-left 0.4s ease;
    }
    
    /* Сайдбар */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      width: var(--sidebar-collapsed-width);
      background: var(--panel-bg);
      backdrop-filter: blur(10px);
      border-right: 2px solid var(--accent-color);
      overflow: hidden;
      transition: all 0.4s ease;
      z-index: 1000;
    }
    
    .sidebar.expanded {
      width: var(--sidebar-width);
    }
    
    .sidebar-toggle {
      position: absolute;
      top: 20px;
      right: 15px;
      background: none;
      border: none;
      color: white;
      font-size: 24px;
      cursor: pointer;
      transition: transform 0.3s ease;
    }
    
    .sidebar.expanded .sidebar-toggle {
      transform: rotate(180deg);
    }
    
    .menu-items {
      margin-top: 70px;
      padding: 0 15px;
    }
    
    .menu-item {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      margin-bottom: 8px;
      border-radius: 8px;
      color: white;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.3s ease;
      opacity: 0;
      transform: translateX(-20px);
      white-space: nowrap;
      overflow: hidden;
    }
    
    .sidebar.expanded .menu-item {
      opacity: 1;
      transform: translateX(0);
    }
    
    .menu-item:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateX(5px);
    }
    
    .menu-item i {
      font-size: 20px;
      margin-right: 15px;
      min-width: 24px;
      text-align: center;
    }
    
    .menu-item span {
      opacity: 0;
      transition: opacity 0.3s ease 0.1s;
    }
    
    .sidebar.expanded .menu-item span {
      opacity: 1;
    }
    
    /* Задержки для анимации пунктов меню */
    .menu-item:nth-child(1) { transition-delay: 0.05s; }
    .menu-item:nth-child(2) { transition-delay: 0.1s; }
    .menu-item:nth-child(3) { transition-delay: 0.15s; }
    .menu-item:nth-child(4) { transition-delay: 0.2s; }
    .menu-item:nth-child(5) { transition-delay: 0.25s; }
    
    /* Основной контент */
    .main-content {
      margin-left: var(--sidebar-collapsed-width);
      transition: margin-left 0.4s ease;
    }
    
    .sidebar.expanded ~ .main-content {
      margin-left: var(--sidebar-width);
    }
    
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 50px;
      border-bottom: 3px solid var(--accent-color);
      position: relative;
    }
    
    .logo img {
      width: 150px;
      height: 150px;
      object-fit: contain;
      margin-top: -40px;
    }
    
    .top-buttons {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 15px;
    }
    
    .top-buttons button {
      background: var(--accent-color);
      border: none;
      padding: 10px 30px;
      border-radius: 20px;
      color: #fff;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    
    .top-buttons button:hover {
      background: var(--accent-dark);
    }
    
    .username {
      font-size: 16px;
      color: #fff;
      font-weight: 500;
      position: absolute;
      right: 40px;
      top: 40px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .content {
      padding: 40px 60px;
    }
    
    h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .filter {
      margin-bottom: 15px;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      background: var(--panel-bg);
      border-radius: 12px;
      overflow: hidden;
    }
    
    thead th {
      background: rgba(0,0,0,0.8);
      padding: 12px 10px;
      text-align: left;
    }
    
    td {
      padding: 10px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      vertical-align: middle;
    }
    
    tr:hover td {
      background: rgba(255,255,255,0.02);
    }
    
    .status-select {
      padding: 6px;
      border-radius: 8px;
      border: none;
    }
    
    .admin-message {
      padding: 6px;
      border-radius: 8px;
      border: none;
      width: 100%;
    }
    
    .save-btn {
      background: var(--accent-color);
      border: none;
      padding: 8px 10px;
      border-radius: 8px;
      color: #fff;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    
    .save-btn:hover {
      background: var(--accent-dark);
    }
    
    .success-popup {
      position: fixed;
      top: 20px;
      right: 20px;
      background: rgba(0,128,0,0.9);
      padding: 10px 18px;
      border-radius: 10px;
      display: none;
      z-index: 1000;
    }
    
    footer {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      padding: 20px 50px;
      border-top: 3px solid var(--accent-color);
    }
    
    /* адаптив */
    @media (max-width: 900px) {
      .sidebar {
        width: 0;
      }
      
      .sidebar.expanded {
        width: 100%;
      }
      
      .main-content {
        margin-left: 0;
      }
      
      .content {
        padding: 20px;
      }
      
      table, thead, tbody, th, td, tr {
        display: block;
      }
      
      thead {
        display: none;
      }
      
      tr {
        margin-bottom: 12px;
        background: rgba(0,0,0,0.45);
        padding: 10px;
        border-radius: 10px;
      }
      
      td {
        display: flex;
        justify-content: space-between;
        padding: 8px;
      }
      
      td.label {
        width: 45%;
        font-weight: 600;
        color: #ddd;
      }
      
      td.value {
        width: 55%;
        text-align: right;
      }
    }
  </style>
</head>
<body>

<!-- Боковое меню -->
<div class="sidebar" id="sidebar">
  <button class="sidebar-toggle" id="sidebarToggle">❮</button>
  <div class="menu-items">
    <div class="menu-item" onclick="location.href='главная.php'">
      <i>🏠</i>
      <span>Главная</span>
    </div>
    <div class="menu-item" onclick="location.href='просмотрзаявок.php'">
      <i>📋</i>
      <span>Все заявки</span>
    </div>
    <div class="menu-item" onclick="location.href='#'">
      <i>👥</i>
      <span>Пользователи</span>
    </div>
    <div class="menu-item" onclick="location.href='#'">
      <i>🚗</i>
      <span>Автомобили</span>
    </div>
    <div class="menu-item" onclick="location.href='logout.php'">
      <i>🚪</i>
      <span>Выйти</span>
    </div>
  </div>
</div>

<!-- Основной контент -->
<div class="main-content">
  <header>
    <div class="logo"><img src="логотип1.png" alt="Логотип"></div>
    <div class="top-buttons">
      <button onclick="location.href='главная.php'">Главная</button>
      <button onclick="location.href='logout.php'">Выйти</button>
    </div>
    <div class="username">👑 <?= $username_safe ?></div>
  </header>

  <section class="content">
    <h2>Все заявки пользователей</h2>

    <div class="filter">
      <label>Фильтр по статусу:
        <select id="filterStatus" onchange="applyFilter()">
          <option value="">Все</option>
          <option value="в ожидании">в ожидании</option>
          <option value="одобрено">одобрено</option>
          <option value="отклонено">отклонено</option>
          <option value="выполнено">выполнено</option>
        </select>
      </label>
    </div>

    <div style="overflow-x:auto">
    <table id="requestsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Пользователь</th>
          <th>Адрес</th>
          <th>Контакты</th>
          <th>ВУ</th>
          <th>Дата</th>
          <th>Автомобиль</th>
          <th>Оплата</th>
          <th>Статус</th>
          <th>Комментарий администратора</th>
          <th>Сохранить</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $req): ?>
          <tr data-status="<?= htmlspecialchars($req['status']) ?>">
            <td><?= htmlspecialchars($req['id']) ?></td>
            <td><?= htmlspecialchars($req['fullname'] ?? 'Пользователь #' . $req['user_id']) ?></td>
            <td><?= htmlspecialchars($req['address']) ?></td>
            <td><?= htmlspecialchars($req['contacts']) ?></td>
            <td><?= htmlspecialchars($req['driver_license']) ?></td>
            <td><?= htmlspecialchars($req['date']) ?></td>
            <td><?= htmlspecialchars($req['car']) ?></td>
            <td><?= htmlspecialchars($req['payment']) ?></td>
            <td>
              <select class="status-select" id="status_<?= (int)$req['id'] ?>">
                <option value="в ожидании" <?= $req['status']==='в ожидании'?'selected':'' ?>>в ожидании</option>
                <option value="одобрено" <?= $req['status']==='одобрено'?'selected':'' ?>>одобрено</option>
                <option value="отклонено" <?= $req['status']==='отклонено'?'selected':'' ?>>отклонено</option>
                <option value="выполнено" <?= $req['status']==='выполнено'?'selected':'' ?>>выполнено</option>
              </select>
            </td>
            <td><input class="admin-message" id="msg_<?= (int)$req['id'] ?>" value="<?= htmlspecialchars($req['admin_message'] ?? '') ?>" placeholder="Комментарий"></td>
            <td><button class="save-btn" onclick="saveRequest(<?= (int)$req['id'] ?>)">💾</button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </section>

  <div class="success-popup" id="popup">✅ Изменения сохранены</div>

  <footer>
    <div class="socials">
      <img src="вк.png" alt="VK" style="width:30px;height:30px;filter:invert(1);margin-right:10px">
      <img src="тг.png" alt="Telegram" style="width:30px;height:30px;filter:invert(1)">
    </div>
    <div class="footer-logo">
      <img src="логотип1.png" alt="Логотип" style="width:100px;height:100px;object-fit:contain;margin-bottom:5px">
      <p style="margin:0;color:#fff">TestDrive</p>
      <p style="margin:0;color:#fff">©2025</p>
    </div>
  </footer>
</div>

<script>
// Управление боковым меню
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');

sidebarToggle.addEventListener('click', function() {
  sidebar.classList.toggle('expanded');
});

// AJAX save
function saveRequest(id){
    const status = document.getElementById('status_' + id).value;
    const admin_message = document.getElementById('msg_' + id).value;
    const data = new URLSearchParams();
    data.append('action','update_request');
    data.append('id', id);
    data.append('status', status);
    data.append('admin_message', admin_message);

    fetch('admin_panel.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: data.toString()
    }).then(r => r.json())
      .then(resp => {
        if(resp && resp.success){
            const p = document.getElementById('popup');
            p.style.display = 'block';
            setTimeout(()=> p.style.display = 'none', 2000);
            // обновим data-status на строке
            const row = document.querySelector(`tr[data-status][id="row_${id}"]`) || 
                       document.querySelector(`tr[data-status]:nth-child(${Array.from(document.querySelectorAll('tr[data-status]')).findIndex(tr => tr.querySelector(`button[onclick="saveRequest(${id})"]`)) + 1})`);
            if (row) {
                row.setAttribute('data-status', status);
            }
        } else {
            alert('Ошибка сохранения: ' + (resp.error || 'неизвестная ошибка'));
        }
    }).catch(e => {
        alert('Ошибка запроса: ' + e.message);
    });
}

// Фильтр по статусу (клиентская фильтрация)
function applyFilter(){
    const f = document.getElementById('filterStatus').value;
    const rows = document.querySelectorAll('#requestsTable tbody tr');
    rows.forEach(r => {
        const st = r.getAttribute('data-status') || '';
        if(f === '' || f === st) r.style.display = '';
        else r.style.display = 'none';
    });
}

// Автоматическое открытие меню при загрузке (опционально)
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(() => {
    sidebar.classList.add('expanded');
  }, 300);
});
</script>

</body>
</html>