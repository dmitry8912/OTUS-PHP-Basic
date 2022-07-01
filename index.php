<form action="/" method="post" enctype="multipart/form-data">
    <div>
        <label for="user">User:</label>
        <input type="text" name="user" />
    </div>
    <div>
        <label for="message">Message:</label>
        <input type="text" name="message" />
    </div>
    <div>
        <input type="file" name="picture" />
    </div>
    <div>
        <input type="hidden" value="eYIiRhAay8" name="form-token">
        <input type="submit" value="Log In" />
    </div>
</form>
<?php
    function connect() {
        return new PDO('mysql:host=localhost;dbname=otus17','root','root',[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

    function getMessages() {
        $db = connect();
        $result = $db->query("SELECT * FROM messages order by created_at DESC");
        $result->execute();
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    function addMessage(string $user, string $message, string $picture = null)
    {
        $db = connect();
        $result = $db->prepare("INSERT INTO messages(user,message,picture) values (?,?,?)");
        $result->execute([$user, $message, $picture]);
    }

    if (array_key_exists('user',$_REQUEST) && array_key_exists('message',$_REQUEST) && $_REQUEST['form-token'] === 'eYIiRhAay8')
    {
        // проверка имени пользователя
        if (!preg_match("/^[a-zA-Zа-яА-Я]{3,20}+$/", $_REQUEST['user'])) {
            die('Имя пользователя не соответствует формату!');
        }

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

        addMessage($_REQUEST['user'], $_REQUEST['message'], $picture ?? null);
    }

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
