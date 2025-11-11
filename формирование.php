<?php
// формирование.php
session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: авторизация.php');
    exit;
}

@include_once __DIR__ . '/db.php';

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

$success_message = null;
$error_message = null;

$user_id = (int)($_SESSION['user_id'] ?? 0); // явно приводим к int
if ($user_id <= 0) {
    // на всякий случай
    header('Location: авторизация.php');
    exit;
}

// Проверим, что пользователь есть в таблице users (чтобы избежать ошибки внешнего ключа)
$user_exists = false;
if ($stmt = $con->prepare("SELECT id, fullname FROM users WHERE id = ? LIMIT 1")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_exists = true;
        $fullname_safe = htmlspecialchars($row['fullname'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $username_safe = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    $stmt->close();
}
if (!$user_exists) {
    // Явная и понятная ошибка пользователю
    $error_message = "Ошибка: текущий пользователь не найден в базе. Пожалуйста, выполните вход ещё раз.";
    // опционально: уничтожить сессию чтобы избежать повторных попыток
    // session_unset(); session_destroy();
}

// Обработка формы (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    $address = trim($_POST['address'] ?? '');
    $contacts = trim($_POST['contacts'] ?? '');
    $driver_license = trim($_POST['driver_license'] ?? '');
    $date_raw = trim($_POST['date'] ?? '');
    $car = trim($_POST['car'] ?? '');
    $payment = trim($_POST['payment'] ?? '');

    // Приведение даты
    $date = '';
    if ($date_raw !== '') {
        $date = str_replace('T', ' ', $date_raw);
        if (strlen($date) === 16) $date .= ':00';
    }

    // Валидация
    if ($address === '' || $contacts === '' || $driver_license === '' || $date === '' || $car === '' || $payment === '') {
        $error_message = "Пожалуйста, заполните все поля.";
    } else {
        $is_email = filter_var($contacts, FILTER_VALIDATE_EMAIL);
        $is_phone = preg_match('/^\+7\(?\d{3}\)?[- ]?\d{3}[- ]?\d{2}[- ]?\d{2}$/', $contacts);
        if (!$is_email && !$is_phone) {
            $error_message = "Контакты должны быть email или телефоном в формате +7(XXX)XXX-XX-XX.";
        } else {
            // Дополнительная проверка: car должен соответствовать ENUM в БД (если вы оставили ENUM)
            $allowed_cars = [
                'Porsche 911',
                'Toyota Supra A80',
                'Ford Mustang Shelby GT500',
                'Land Rover Defender 110'
            ];
            if (!in_array($car, $allowed_cars, true)) {
                $error_message = "Неверная марка/модель автомобиля. Выберите из списка.";
            } else {
                // Вставляем запись (не указываем status чтобы не было проблем с ENUM)
                $sql = "INSERT INTO requests (address, contacts, driver_license, `date`, car, payment, user_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $con->prepare($sql);
                if ($stmt === false) {
                    $error_message = "Ошибка подготовки запроса: " . htmlspecialchars($con->error);
                } else {
                    $uid = $user_id;
                    if (!$stmt->bind_param("ssssssi", $address, $contacts, $driver_license, $date, $car, $payment, $uid)) {
                        $error_message = "Ошибка привязки параметров: " . htmlspecialchars($stmt->error);
                    } else {
                        if ($stmt->execute()) {
                            $success_message = "✅ Ваша заявка успешно отправлена! Ожидайте подтверждения администратора.";
                            // очистим ввод
                            $_POST = [];
                        } else {
                            // если внешний ключ опять почему-то не прошёл — вернём понятное сообщение
                            $error_message = "Ошибка при сохранении заявки: " . htmlspecialchars($stmt->error);
                        }
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Формирование заявки — Avtomir</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    /* ваш CSS (оставляем как раньше) */
    body { margin:0; font-family:"Segoe UI",sans-serif; background-color:#b7aba9; color:#fff; overflow-x:hidden; }
    header{display:flex;align-items:center;justify-content:space-between;padding:10px 50px;border-bottom:3px solid #a49a97;position:relative;}
    .logo img{width:150px;height:150px;object-fit:contain;margin-top:-40px;}
    .top-buttons{position:absolute;left:50%;transform:translateX(-50%);display:flex;gap:15px;}
    .top-buttons button{background:#a49a97;border:none;padding:10px 30px;border-radius:20px;color:#fff;cursor:pointer}
    .username{font-size:16px;color:#fff;font-weight:500;position:absolute;right:40px;top:40px;}
    .main-section{display:flex;align-items:center;justify-content:flex-start;background:url("фон.jpg") no-repeat center/cover;padding:50px 100px;height:75vh;border-bottom:3px solid #a49a97;}
    .request-form{width:420px;background:rgba(0,0,0,0.55);border-radius:25px;padding:35px 40px;display:flex;flex-direction:column;gap:15px;box-shadow:0 0 20px rgba(0,0,0,0.5);}
    .request-form input,.request-form select{width:100%;padding:12px;border:none;border-radius:10px;background:rgba(255,255,255,0.9);color:#333;outline:none}
    .request-form button{background:#a49a97;border:none;padding:12px;border-radius:20px;color:#fff;cursor:pointer}
    .success-message, .error-message{position:fixed;top:20px;right:20px;padding:15px 25px;border-radius:12px;font-size:17px;z-index:1000;opacity:0;transform:translateY(-20px);transition:opacity .4s,transform .4s}
    .success-message.show{opacity:1;background:rgba(0,128,0,0.9)}
    .error-message.show{opacity:1;background:rgba(200,0,0,0.9)}
    footer{display:flex;justify-content:space-between;align-items:flex-end;padding:20px 50px;border-top:3px solid #a49a97}
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
  <form class="request-form" method="POST" action="">
    <h2 style="text-align:center;margin:0 0 10px;color:#fff">Формирование заявки</h2>

    <label>Введите адрес</label>
    <input type="text" name="address" required placeholder="Улица, дом" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">

    <label>Введите телефон или почту</label>
    <input type="text" name="contacts" required placeholder="+7(XXX)XXX-XX-XX / name@mail.ru" value="<?= htmlspecialchars($_POST['contacts'] ?? '') ?>">

    <label>Выберите желаемую дату</label>
    <input type="datetime-local" name="date" required value="<?= htmlspecialchars($_POST['date'] ?? '') ?>">

    <label>Данные о водительском удостоверении</label>
    <input type="text" name="driver_license" required placeholder="Серия и номер ВУ" value="<?= htmlspecialchars($_POST['driver_license'] ?? '') ?>">

    <label>Выберите марку и модель</label>
    <select name="car" required>
      <option value="">Выберите...</option>
      <option value="Porsche 911" <?= (($_POST['car'] ?? '')==='Porsche 911') ? 'selected' : '' ?>>Porsche 911</option>
      <option value="Toyota Supra A80" <?= (($_POST['car'] ?? '')==='Toyota Supra A80') ? 'selected' : '' ?>>Toyota Supra A80</option>
      <option value="Ford Mustang Shelby GT500" <?= (($_POST['car'] ?? '')==='Ford Mustang Shelby GT500') ? 'selected' : '' ?>>Ford Mustang Shelby GT500</option>
      <option value="Land Rover Defender 110" <?= (($_POST['car'] ?? '')==='Land Rover Defender 110') ? 'selected' : '' ?>>Land Rover Defender 110</option>
    </select>

    <label>Выберите способ оплаты</label>
    <select name="payment" required>
      <option value="">Выберите...</option>
      <option value="наличные" <?= (($_POST['payment'] ?? '') === 'наличные') ? 'selected' : '' ?>>Наличные</option>
      <option value="карта" <?= (($_POST['payment'] ?? '') === 'карта') ? 'selected' : '' ?>>Карта</option>
    </select>

    <button type="submit">Отправить заявку</button>
  </form>
</section>

<?php if ($success_message): ?>
  <div class="success-message show" id="successMessage"><?= $success_message ?></div>
<?php endif; ?>

<?php if ($error_message): ?>
  <div class="error-message show" id="errorMessage"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>

<footer>
  <div class="socials">
    <img src="вк.png" alt="VK" style="width:30px;height:30px;filter:invert(1);margin-right:10px">
    <img src="гео.png" alt="WhatsApp" style="width:30px;height:30px;filter:invert(1);margin-right:10px">
    <img src="тг.png" alt="Telegram" style="width:30px;height:30px;filter:invert(1)">
  </div>

  <div class="footer-logo">
    <img src="логотип1.png" alt="Логотип" style="width:100px;height:100px;object-fit:contain;margin-bottom:5px">
    <p style="margin:0;color:#fff">TestDrive</p>
    <p style="margin:0;color:#fff">©2025</p>
  </div>
</footer>

<script>
  setTimeout(() => {
    document.getElementById('successMessage')?.classList?.remove('show');
    document.getElementById('errorMessage')?.classList?.remove('show');
  }, 3500);
</script>

</body>
</html>
