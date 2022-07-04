<?php
    session_start();
    var_dump($_COOKIE);
    if(!empty($_COOKIE['remember-token'])) {
        $userData = getUserDataByToken($_COOKIE['remember-token']);
        $_SESSION['user_id'] = intval($userData['id']);
        $_SESSION['username'] = $userData['username'];
    }

    function connect() {
        return new PDO('mysql:host=localhost;dbname=otus17','root','root',[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

    function getMessages() {
        $db = connect();
        $result = $db->query("SELECT messages.id, users.username AS user, messages.message, messages.picture, messages.created_at FROM messages JOIN users ON users.id = messages.user_id ");
        $result->execute();
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    function addMessage(int $user_id, string $message, string $picture = null)
    {
        $db = connect();
        $result = $db->prepare("INSERT INTO messages(user_id,message,picture) values (?,?,?)");
        $result->execute([$user_id, $message, $picture]);
    }

    function setRememberToken(int $user_id, string $token)
    {
        $db = connect();
        $result = $db->prepare("update users set remember_token = ? where id = ?");
        $result->execute([$token, $user_id]);
    }

    function getUserDataByToken(string $token)
    {
        $db = connect();
        $result = $db->prepare("select username, id from users where remember_token = ?");
        $result->execute([$token]);
        if($result->rowCount() > 0) {
            return $result->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    function authorize(string $user, string $password) {
        $db = connect();
        $result = $db->prepare("SELECT id from users where username = ? and password = ?");
        $result->execute([$user, $password]);
        if($result->rowCount() == 0) {
            return false;
        }
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    if(!empty($_GET['action']) && $_GET['action'] === 'auth')
    {
        $authResult = authorize($_POST['user'], $_POST['password']);
        if(!$authResult) {
            echo "<h2>Неправильное имя пользователя или пароль!</h2>";
        } else {
            $_SESSION['user_id'] = intval($authResult['id']);
            $_SESSION['username'] = $_POST['user'];
            if($_POST['remember_me'] === 'on') {
                $token = uniqid();
                setRememberToken($_SESSION['user_id'], $token);
                setcookie('remember-token', $token, time() + 3600*24*30*6);
            }
        };
    }

    if(!empty($_GET['action']) && $_GET['action'] === 'addMessage')
    {
        if (array_key_exists('message',$_REQUEST) && $_REQUEST['form-token'] === 'eYIiRhAay8')
        {
            // проверка имени пользователя
            /*if (!preg_match("/^[a-zA-Zа-яА-Я]{3,20}+$/", $_REQUEST['user'])) {
                die('Имя пользователя не соответствует формату!');
            }*/

            if(!empty($_FILES['picture']) && $_FILES['picture']['size'] > 0)
            {
                $name = basename($_FILES["picture"]['name']);
                $ext = pathinfo($name,PATHINFO_EXTENSION);
                if(!in_array($ext,['txt','jpg','pdf','png']))
                    die('Загружаемый файл имеет неподдерживаемое расширение!');
                $name = uniqid().".".$ext;
                move_uploaded_file($_FILES["picture"]['tmp_name'], "$name");
                $picture = $name;
            }

            addMessage($_SESSION['user_id'], $_REQUEST['message'], $picture ?? null);
        }
    }
?>
<?php if(empty($_SESSION['user_id'])): ?>
    <form action="/index.php?action=auth" method="post" enctype="multipart/form-data">
        <div>
            <label for="user">Username:</label>
            <input type="text" name="user" />
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" name="password" />
        </div>
        <div>
            <label for="remember_me">Remember me:</label>
            <input type="checkbox" name="remember_me" />
        </div>
        <div>
            <input type="submit" value="Log In" />
        </div>
    </form>
<?php else: ?>
    <form action="/index.php?action=addMessage" method="post" enctype="multipart/form-data">
        <div>
            <label for="message">Message:</label>
            <input type="text" name="message" />
        </div>
        <div>
            <input type="file" name="picture" />
        </div>
        <div>
            <input type="hidden" value="eYIiRhAay8" name="form-token">
            <input type="submit" value="Add message" />
        </div>
    </form>
<?php endif; ?>
<?php
    $messages = getMessages();
    foreach ($messages as $message) {
        $message['message'] = htmlspecialchars($message['message']);
        echo "<div>Сообщение #{$message['id']} от {$message['created_at']}<br><h4>{$message['user']}</h4>{$message['message']}</div>";
        if($message['picture'] != null)
        {
            echo "<div><a href='/{$message['picture']}'><img src='/{$message['picture']}' width='120'></a></div>";
        }
        echo "<hr>";
    }
