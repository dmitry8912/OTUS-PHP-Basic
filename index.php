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
    if (array_key_exists('user',$_REQUEST) && array_key_exists('message',$_REQUEST) && $_REQUEST['form-token'] === 'eYIiRhAay8')
    {
        // проверка имени пользователя
        if (!preg_match("/^[a-zA-Zа-яА-Я]{3,20}+$/", $_REQUEST['user'])) {
            die('Имя пользователя не соответствует формату!');
        }

        $messages = [];
        // получение сообщений из messages.json
        if(file_exists('messages.json'))
        {
            $messages = json_decode(file_get_contents('messages.json'),true);
        }

        if(!empty($_FILES['picture']))
        {
            $name = basename($_FILES["picture"]['name']);
            $ext = pathinfo($name,PATHINFO_EXTENSION);
            if(!in_array($ext,['txt','jpg','pdf']))
                die('Загружаемый файл имеет неподдерживаемое расширение!');
            $name = uniqid().$ext;
            move_uploaded_file($_FILES["picture"]['tmp_name'], "$name");
            $picture = $name;
        }

        $messages[] = [
            'user' => $_REQUEST['user'],
            'message' => $_REQUEST['message'],
            'picture' => isset($picture) ? $picture : ''
        ];

        file_put_contents('messages.json',json_encode($messages));
    }

    if(file_exists('messages.json'))
    {
        $messages = json_decode(file_get_contents('messages.json'),true);
        foreach ($messages as $message) {
            $message['message'] = htmlspecialchars($message['message']);
            echo "<div>{$message['user']} -> {$message['message']}</div>";
            if(array_key_exists('picture', $message))
            {
                echo "<div><a href='/{$message['picture']}'>{$message['picture']}</a></div>";
            }
        }
    }
