<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Routes

$app->get('/users', function (Request $request, Response $response, array $args) {
    $sql_select_user = 'select id, username, email, password from users';
    $stmt = $this->db->prepare($sql_select_user);
    $stmt->execute();
    $res = $stmt->fetchAll();
    return $response->withJson($res);
});

$app->get('/login/{username}/{password}', function (Request $request, Response $response, array $args) {
	$arr_response = array();
    $error_message = '';
    $token = '';
    $username = $args['username'];
    $password = $args['password'];
    $sql_select_user = 'select id, password, active from users where username = :username';
    $stmt = $this->db->prepare($sql_select_user);
    $stmt->execute(['username' => $username]);
    $res = $stmt->fetchAll();
    if ($stmt->rowCount() > 0) {
        if ($res[0]['active'] == 0) {
            $error_message = 'Cont neactivat';
        } elseif (password_verify($password, $res[0]['password']) && $error_message == '') {
            $token = createToken();
            $user_id = $res[0]['id'];
            $sql_insert_token = "insert into users_token(user_id, token, create_date) values('".$user_id."','".$token."', now())";
            // die($sql_insert_token);
            $stmt= $this->db->prepare($sql_insert_token);
            $stmt->execute();
            
            // $arr_response['status'] = "ok";
            // $arr_response['message'] = "success login";
            // $arr_response['payload']['token'] = $token;
        } else {
            $error_message = 'Incorrect user / password';
            // $arr_response['status'] = "notok";
            // $arr_response['message'] = "login failed";
            // $arr_response['payload'] = 'Incorrect user / password';
        }
    } else {
        // $arr_response['status'] = "notok";
        // $arr_response['message'] = "login failed";
        // $arr_response['payload'] = 'User not exists: ' . $username;
        $error_message = 'User not exists: ' . $username;
    }

    if ($error_message == '') {
        $arr_response['status'] = "ok";
        $arr_response['message'] = "success login";
        $arr_response['payload']['token'] = $token;
    } else {
        $arr_response['status'] = "notok";
        $arr_response['message'] = "login failed";
        $arr_response['payload'] = $error_message;        
    }
    // sleep(5000);
    return $response->withJson($arr_response);

});

$app->post('/logout', function (Request $request, Response $response, array $args) {
    // sleep(5);
    $arr_response = [];
    $header_data = $request->getHeaders();
    $token = $header_data['HTTP_TOKENAUTH'][0];
    // $token = "12019/02/15 02:35:34pm";
    $sql_delete_token = "delete from users_token where token = :token";
    $stmt = $this->db->prepare($sql_delete_token);
    $stmt->execute(['token' => $token]);

    $arr_response['status'] = "ok";
    $arr_response['message'] = "success logout";
    $arr_response['payload'] = "";

    return $this->response->withJson($arr_response);

})->add($chkTkM);

$app->post('/register/{username}/{email}/{password}', function (Request $request, Response $response, array $args) {
    $arr_response = array();
    $error_message = "";
    $username = $args['username'];
    $password = $args['password'];
    $email = $args['email'];
    // $publicPath = $request->getUri()->getBasePath() . '/';
    // $profileImagesPath = 'assets/profile_images/';
    $profileImagesPath = $this->get('profile_images_path');
    $directory = dirname(realpath(__DIR__)) . '/public/' . $profileImagesPath;
    $uploadFileOk = true;
    $fileSizeAllowed = 1;
    $filename = '';
    $allowedFiles = ['jpg', 'png'];

    /*check email*/
    if(!checkEmail($email)) {
        $error_message = "Adresa de email incorecta!!!";
    }
    /*check password*/
    $checkPassResult = checkPassword($password);
    if($checkPassResult != '') {
        $error_message = $checkPassResult;
    }

    /*check unique username*/
    $sql_used_username = 'select id, username from users where username = :username';
    $stmt = $this->db->prepare($sql_used_username);
    $stmt->execute(['username' => $username]);
    $res = $stmt->fetchAll();
    if ($stmt->rowCount() > 0) {
        $error_message = 'Username este deja folosit';
    }
    /*check unique email*/
    $sql_used_email = 'select id, username from users where email = :email';
    $stmt = $this->db->prepare($sql_used_email);
    $stmt->execute(['email' => $email]);
    $res = $stmt->fetchAll();
    if ($stmt->rowCount() > 0) {
        $this->logger->info('Email este deja folosi: '. $stmt->rowCount());
        $error_message = 'Email este deja folosit';
    }

    /*file upload*/
    if ($error_message === '') {            

        $uploadedFiles = $request->getUploadedFiles();
        if(!empty($uploadedFiles['photo'])) {
            $uploadedFile = $uploadedFiles['photo'];
            $fileExtension = strtolower(pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION));

            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {

                if (!in_array($fileExtension, $allowedFiles)) {
                    $error_message = "Nu ai ales un fisier bun $fileExtension, doar " . implode(',', $allowedFiles);
                    $uploadFileOk = false;
                }

                /*if ($fileSize > $fileSizeAllowed) {
                    $error_message = "Fisier prea grasan, doar $fileSizeAllowed MB!, tu ai: " . $fileSize;
                    $uploadFileOk = false;
                }*/

                if ($uploadFileOk) {
                    $filename = moveUploadedFile($directory, $uploadedFile, $username);
                    if (!$filename) {
                        $error_message = "E nasol daca ai ajuns aici, ceva nu a mers bine!";
                        $uploadFileOk = false;
                    }
                } 
            } else {
                $error_code = $uploadedFile->getError();
                $error_message = "Eroare: ". getUploadErrorDescription($error_code);
            }            
        }
        
    }

    if($error_message == '') {
        if ($filename != '') {
            $user_image = $profileImagesPath .'/'. $filename;
        } else {
            $user_image = null;
        }
        //password hashing
        $hashed_password = cryptPassword($password);
        $activation_key = sha1(mt_rand(10000,99999).time().$email);
        $activation_link = $this->paths['public_path'] . 'activate_account/' . $activation_key;
        $data = [
            'email' => $email,
            'username' => $username,
            'password' => $hashed_password,
            'user_image' => $user_image,
            'activation_key' => $activation_key,
        ];
        $this->db->beginTransaction();
        try{
            $sql_insert = "insert into users(email, username, password, user_image_path, activation_key) values(:email, :username, :password, :user_image, :activation_key)";
            $stmt = $this->db->prepare($sql_insert);
            $stmt->execute($data);
            $last_insert_user_id = $this->db->lastInsertId();
            $sql_insert_setting = "insert into user_setting_mn(setting_type_id, setting_value_number, setting_value_string, user_id)
                                    select 1, 0, null, $last_insert_user_id UNION select 2, 0, null, $last_insert_user_id";
            $stmt2 = $this->db->prepare($sql_insert_setting);
            $stmt2->execute();
            // $this->db->commit();

            //send email
            $phpMailer = $this->mailer;
            $phpMailer->addAddress($email); 
            $phpMailer->Subject = 'Creare cont aplicatie Kumparuturi';
            $phpMailer->Body    = 'Tocmai ce ti-ai facut cont in aplicatia de <b>Kumparaturi</b> <br>
                                   Ar cam trebui sa dai cu mausu aici <a href="' . $activation_link . '"><b>' . $activation_link .'</b></a>';
            // $email->AltBody = 'Tocmai ce ti-ai facut cont in aplicatia de Kumparaturi';
            try {
                $phpMailer->send();
                $this->db->commit();
            } catch(Exception  $e) {
                $error_message = 'O problema avem cu mailul de validare, nu s-a trimis';
            }
        } catch(PDOException $e) {
             $this->db->rollBack();
             $error_message == 'Eroare creare cont';
        }
        // if(!$stmt->execute($data)) {
        //     $error_message == 'Eroare creare cont';
        // }            
    }    

    if ($error_message == '') {
        $arr_response['status'] = 'ok';
        $arr_response['message'] = 'register success';
        $arr_response['payload']['userImage'] = $filename;
    } else {
        $arr_response['status'] = 'notok';
        $arr_response['message'] = 'register failed';
        $arr_response['payload']['errors'] = $error_message;
    }
    
    return $response->withJson($arr_response);

        /*$register_data = json_decode ( $request->getBody () );
        
        if (preg_match('/^[_#&A-Za-z0-9-]+(\.[_a-z0-9-]+)*@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*(\.[A-Za-z]{2,3})$/',trim($register_data->email))){
            $this->logger->info("Adresa email valida: ".trim($register_data->email));
        }else{
            $error_message = "Adresa de email incorecta!!!";
        }
*/

});

$app->get('/activate_account/{activation_key}', function (Request $request, Response $response, array $args) {
    $activation_key = $args['activation_key'];
    $sql = "update users set active = 1 where activation_key = :activation_key";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['activation_key' => $activation_key]);
    if ($stmt->rowCount() > 0) {
        $status = 1;
        $reason = 'Contul a fost activat';
    } else {
        $status = 0;
        $reason = 'Na ca nu a mers activarea, aia e !';
    }
    return $this->renderer->render($response, 'activate_account.phtml', ['status' => $status, 'reason' => $reason]);
});

$app->get('/get_user_details', function (Request $request, Response $response, array $args) {
    $arr_response = [];
    $arr_setting_tmp = [];
    $arr_setting = [];
    $header_data = $request->getHeaders();
    $token = $header_data['HTTP_TOKENAUTH'][0];
    $serverURL = "http://$_SERVER[HTTP_HOST]";
    $publicPath = $request->getUri()->getBasePath() . '/';
    $profileBasePath = $serverURL . $publicPath;

    $sql_user_details = 'select u.id as user_id, u.email, u.username, u.user_image_path from users_token ut inner join users u on u.id = ut.user_id where ut.token = :token';

    $stmt = $this->db->prepare($sql_user_details);
    $stmt->execute(['token' => $token]);
    $res_det = $stmt->fetchAll();
    if ($stmt->rowCount() > 0) {
        $user_id = $res_det['0']['user_id'];
        $sql_user_setting = "select st.id as setting_type_id, mn.setting_value_string, mn.setting_value_number 
                            from setting_type st
                            inner join user_setting_mn mn on mn.setting_type_id = st.id
                            where mn.user_id = $user_id";
        $stmt_stng = $this->db->prepare($sql_user_setting);
        $stmt_stng->execute();
        $res_stng = $stmt_stng->fetchAll();
        //setting_type_id devine key in array
        foreach ($res_stng as $settingKey => $settingValue) {
            foreach ($settingValue as $key => $value) {
                if ($key == 'setting_type_id') {
                    $newkey = $value;
                } else {
                    $arr_setting_tmp[$key] = $value;
                }
            }
            $arr_setting[$newkey] = $arr_setting_tmp;
        }
        $arr_response['status'] = 'ok';
        $arr_response['message'] = 'succes';
        $arr_response['payload']['username'] = $res_det['0']['username'];
        $arr_response['payload']['email'] = $res_det['0']['email'];
        $arr_response['payload']['userImage'] = $res_det['0']['user_image_path'] != '' ? $profileBasePath . $res_det['0']['user_image_path'] : '';
        $arr_response['payload']['setting'] = $arr_setting;
    } else {
        $arr_response['status'] = 'ok';
        $arr_response['message'] = 'succes';
        $arr_response['payload']['username'] = 'N/A';
        $arr_response['payload']['email'] = 'N/A';
    }
    return $response->withJson($arr_response);
})->add($chkTkM);

$app->post('/change_user_settings', function (Request $request, Response $response, array $args) {
    $arr_response = [];
    $error_message = '';
    $header_data = $request->getHeaders();
    $body_data = $request->getParsedBody();
    $token = $header_data['HTTP_TOKENAUTH'][0];
    $sql_user_details = 'select u.id, u.email, u.username, u.user_image_path from users_token ut inner join users u on u.id = ut.user_id and ut.token = :token';
    $stmt = $this->db->prepare($sql_user_details);
    $stmt->execute(['token' => $token]);
    $res = $stmt->fetchAll();
    $user_id = $res['0']['id'];
    if (isset($body_data['setting_type'])) {
        if ($body_data['setting_type'] === 'password') {
            $sql_check_old_password = 'select u.password from users u where id = :user_id';
            $stmt_check = $this->db->prepare($sql_check_old_password);
            $stmt_check->execute(['user_id' => $user_id]);
            $res_pass_check = $stmt_check->fetchAll();
            if(decryptPassword($body_data['oldPassword'], $res_pass_check[0]['password'])) {
                $new_password_hashed = cryptPassword($body_data['newPassword']);
                $sql_update_pass = 'update users set password = :password where id = :user_id';
                $stmt = $this->db->prepare($sql_update_pass);
                $stmt->execute(['user_id' => $res['0']['id'], 'password' => $new_password_hashed]);
            } else {
                $error_message = 'Parola veche nu este corecta';
            }
        } else if ($body_data['setting_type'] === 'sendNotification') {
            $newStatus = $body_data['newStatus'];
            $sql_update = 'update user_setting_mn set setting_value_number = :new_status where user_id = :user_id and setting_type_id = 1';
            $stmt = $this->db->prepare($sql_update);
            $stmt->execute(['user_id' => $user_id, 'new_status' => $newStatus]);
        } else if ($body_data['setting_type'] === 'receiveNotification') {
            $newStatus = $body_data['newStatus'];
            $sql_update = 'update user_setting_mn set setting_value_number = :new_status where user_id = :user_id and setting_type_id = 2';
            $stmt = $this->db->prepare($sql_update);
            $stmt->execute(['user_id' => $user_id, 'new_status' => $newStatus]);
        } else {
            $error_message = 'Setarea nu exista';
        }
    } else {
        $error_message = 'Ce nu a mers bine, e nasol aici';
    }

    if ($error_message !== '') {
        $arr_response['status'] = 'notok';
        $arr_response['message'] = 'change setting failed';
        $arr_response['payload']['errors'] = $error_message;
    } else {
        $arr_response['status'] = 'ok';
        $arr_response['message'] = 'change setting success';
        $arr_response['payload'] = '';
    }
    
    return $response->withJson($arr_response);
    
})->add($chkTkM);

$app->post('/change_avatar', function (Request $request, Response $response, array $args) {
    $arr_response = [];
    $error_message = '';
    $header_data = $request->getHeaders();
    $body_data = $request->getParsedBody();
    $public_path = $this->paths['public_path'];
    $token = $header_data['HTTP_TOKENAUTH'][0];
    $sql_user_details = 'select u.id, u.email, u.username, u.user_image_path from users_token ut inner join users u on u.id = ut.user_id and ut.token = :token';
    $stmt = $this->db->prepare($sql_user_details);
    $stmt->execute(['token' => $token]);
    $res = $stmt->fetchAll();
    $user_id = $res['0']['id'];
    $image_path = $res['0']['user_image_path'];
    $image_path_disk = $this->paths['public_path_disk'] . $image_path;

    $profileImagesPath = $this->get('profile_images_path');
    $directory = dirname(realpath(__DIR__)) . '/public/' . $profileImagesPath;
    $uploadFileOk = true;
    $fileSizeAllowed = 1;
    $filename = '';
    $allowedFiles = ['jpg', 'png'];

    /*if(!empty($image_path)) {
        if (file_exists($image_path_disk)) {*/
            $uploadedFiles = $request->getUploadedFiles();
            // var_dump($uploadedFiles);
            // die('stop');
            if(!empty($uploadedFiles['photo'])) {
                $uploadedFile = $uploadedFiles['photo'];
                $fileExtension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);

                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {

                    if (!in_array($fileExtension, $allowedFiles)) {
                        $error_message = "Nu ai ales un fisier bun, doar " . implode(',', $allowedFiles);
                        $uploadFileOk = false;
                    }

                    /*if ($fileSize > $fileSizeAllowed) {
                        $error_message = "Fisier prea grasan, doar $fileSizeAllowed MB!, tu ai: " . $fileSize;
                        $uploadFileOk = false;
                    }*/

                    if ($uploadFileOk) {
                        $filename = moveUploadedFile($directory, $uploadedFile);
                        if (!$filename) {
                            $error_message = "E nasol daca ai ajuns aici, ceva nu a mers bine!";
                            $uploadFileOk = false;
                        }
                    } 
                } else {
                    $error_code = $uploadedFile->getError();
                    $error_message = "Eroare: ". getUploadErrorDescription($error_code);
                }            
            } else {
                $error_message = "Nu a venit poza in rest";
            }
        /*} else {
            // $error_message = 'Nu exista in path';
            var_dump('Nu exista in path');
        }*/
    /*} else {
        // $error_message = 'Nu exista in BD';
        var_dump('Nu exista in BD');
    }*/

    if($error_message == '') {
        if(!unlink($image_path_disk)) {
            $error_message = 'Nu am putut sa sterg poza';
        }
        if ($filename != '') {
            $user_image = $profileImagesPath .'/'. $filename;
        } else {
            $user_image = null;
        }
        $data = [
            'user_id' => $user_id,
            'user_image' => $user_image,
        ];
        // $sql_update = "insert into users(email, username, password, user_image_path) values(:email, :username, :password, :user_image)";
        $sql_update = "update users set user_image_path = :user_image where id = :user_id";
        $stmt = $this->db->prepare($sql_update);
        if(!$stmt->execute($data)) {
            $error_message = 'Eroare schimbare poza in BD';
        }            
    }

    if ($error_message !== '') {
        $arr_response['status'] = 'notok';
        $arr_response['message'] = 'change photo failed';
        $arr_response['payload']['errors'] = $error_message;
    } else {
        $arr_response['status'] = 'ok';
        $arr_response['message'] = 'change photo success';
        $arr_response['payload'] = '';
    }
    
    return $response->withJson($arr_response); 

})->add($chkTkM);

$app->get('/items', function (Request $request, Response $response, array $args) {
    $arr_response = [];
    $public_path = $this->paths['public_path'];
    $sql_items = "select s.id, s.item, s.status_id, s.created_at, CONCAT('$public_path', u.user_image_path) as avatar_image_path, u.username from shop s left join users u on u.id = s.user_id order by status_id, id desc";
    $stmt = $this->db->prepare($sql_items);
    $stmt->execute();
    $all_items = $stmt->fetchAll();
    $arr_response['status'] = 'ok';
    $arr_response['message'] = 'succes';
    $arr_response['payload'] = $all_items;
    return $response->withJson($arr_response);
})->add($chkTkM);

$app->get('/add_item/{item}', function (Request $request, Response $response, array $args) {
    $arr_response = [];
    $error_message = '';
	$current_date = date('Y-m-d');
	$status_id_inserted = '0';
	$item = $args['item'];
    $payload = [];
    
    $header_data = $request->getHeaders();
    $token = $header_data['HTTP_TOKENAUTH'][0];
    // $token = 'eyJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1NTE0NDYwODgsIm5iZiI6MTU1MTQ0NjA4OCwiZXhwIjoxNTUxODA2MDg4LCJpc3MiOiJTaG9wcGluZyBzZXJ2aWNlIiwiYXVkIjoiS3VtcGFyYXR1cmkifQ.uqswFdWmU-Fw8rQmXYvz627VUGtREW7aC85KAwTz3Nk';
    $sql_user = 'select ut.user_id, st.setting_value_number as send_notification, u.user_image_path, u.username
                from users_token ut
                inner join users u on u.id = ut.user_id
                left join (select mn.user_id, mn.setting_type_id, max(mn.setting_value_number) setting_value_number, max(mn.setting_value_string) setting_value_string
                    from user_setting_mn mn
                    where mn.setting_type_id = 1
                    group by mn.user_id, mn.setting_type_id) st on st.user_id = ut.user_id
                where token = :token order by create_date desc';
    $stmt_u = $this->db->prepare($sql_user);
    $stmt_u->execute(['token' => $token]);
    $res_user = $stmt_u->fetchAll();

    if ( $stmt_u->rowCount() > 0 ) {
        $user_id = $res_user['0']['user_id'];
        $username = $res_user['0']['username'];
        $user_image = $this->paths['public_path'] . "/" . $res_user['0']['user_image_path'];
    	$query = "INSERT INTO shop(item, status_id, created_at, user_id) VALUES('" . $item . "', $status_id_inserted, '" . $current_date . "', $user_id)";
    	$stmt = $this->db->prepare($query);
    	$stmt->execute();
    	$last_insert_id = $this->db->lastInsertId();
        if ($last_insert_id) {
            $payload = [
                'id' => $last_insert_id,
                'item' => $item,
                'status_id' => $status_id_inserted,
                'created_at' => $current_date,
                'avatar_image_path' => $user_image,
                'username' => $username,
            ];
            if ($res_user['0']['send_notification'] == '1') {
                sendPushNotification($item);
            }
        } else {
            $error_message = 'Nu am adaugat itemul';
        }
    } else {
        $error_message = 'Nu exista user_id';
    }

    if ($error_message == '') {
    	$arr_response['status'] = 'ok';
        $arr_response['message'] = 'succes';
        $arr_response['payload'] = $payload;
    } else {
        $arr_response['status'] = 'notok';
        $arr_response['message'] = 'add failed';
        $arr_response['payload'] = $error_message;        
    }
    return $response->withJson($arr_response);
})->add($chkTkM);

$app->get('/edit_item_name/{item_id}/{item_name}', function (Request $request, Response $response, array $args) {
    $arr_response = [];
	$item_id = $args['item_id'];
	$item_name = $args['item_name'];
	$query = "update shop set item ='" . $item_name . "'where id = $item_id";

	$stmt = $this->db->prepare($query);
	$stmt->execute();

	$row_count = $stmt->rowCount();
	if ($row_count > 0) {
		$payload = [
			'id' => $item_id,
			'item' => $item_name,
		];
		$arr_response['status'] = 'ok';
	    $arr_response['message'] = 'succes';
	    $arr_response['payload'] = $payload;
	} else {
		$arr_response['status'] = 'notok';
	    $arr_response['message'] = 'failed';
	    $arr_response['payload'] = 'Nu pot sa editez item';
	}
    return $response->withJson($arr_response);
})->add($chkTkM);

$app->get('/edit_item_status/{item_id}/{status_id}', function (Request $request, Response $response, array $args) {
    $arr_response = [];
    $error_message = "";
    $row_count = 0;
    $arr_status = [0, 1];
	$item_id = $args['item_id'];
	$status_id = $args['status_id'];
	if(in_array($status_id, $arr_status)) {
		$query = "update shop set status_id ='" . $status_id . "'where id = $item_id";
		$stmt = $this->db->prepare($query);
		$stmt->execute();
		$row_count = $stmt->rowCount();
	} else {
		$error_message = 'Incorect status';
	}

	if ($row_count > 0 && empty($error_message)) {
		$arr_response['status'] = 'ok';
	    $arr_response['message'] = 'succes';
	    $arr_response['payload'] = ['id' => $item_id, 'status' => $status_id];
	} else {
		$arr_response['status'] = 'notok';
	    $arr_response['message'] = 'failed';
	    $arr_response['payload'] = 'Unable to update item' . $error_message;
	}
    return $response->withJson($arr_response);
})->add($chkTkM);

$app->post('/delete_item', function (Request $request, Response $response, array $args) {
    $arr_response = [];
    $error_message = "";
    $row_count = 0;
    $deleted_ids = "";
    $body_data = $request->getParsedBody();
    $deleted_ids = implode("," , $body_data);
	$query = "delete from shop where id in ($deleted_ids)";
	$stmt = $this->db->prepare($query);
	$stmt->execute();
	$row_count = $stmt->rowCount();

	if ($row_count > 0 ) {
		$arr_response['status'] = 'ok';
	    $arr_response['message'] = 'succes';
	    $arr_response['payload'] = $deleted_ids;
	} else {
		$arr_response['status'] = 'notok';
	    $arr_response['message'] = 'failed';
	    $arr_response['payload'] = 'Unable to delete item';
	}
    return $response->withJson($arr_response);
})->add($chkTkM);

$app->post('/delete_all_items', function (Request $request, Response $response, array $args) {
    $arr_response = [];
    $error_message = "";
    $row_count = 0;
    $status_id = '';
    $body_data = $request->getParsedBody();
    $status_id = $body_data['status_id'];
    if ($status_id == '-1') {
        $query = "delete from shop";
    } else if ($status_id == '1' || $status_id == '0') {
        $query = "delete from shop where status_id = $status_id";
    } else {
        $query = '';
        $error_message = 'Status incorect';
    }

    if ($query != '') {
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $row_count = $stmt->rowCount();
    }

    if ($error_message === '' ) {
        $arr_response['status'] = 'ok';
        $arr_response['message'] = 'succes';
        $arr_response['payload']['status_id'] = $status_id;
    } else {
        $arr_response['status'] = 'notok';
        $arr_response['message'] = 'failed';
        $arr_response['payload'] = 'Nu s-a sters nici un item: ' . $error_message;
    }
    return $response->withJson($arr_response);
})->add($chkTkM);



function checkEmail($email) {
    // if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //     return true;
    // } else {
    //     return false;
    // }
    return true;
}

function checkPassword( $password )
{
    $error = '';
    $char = 3;
    // if (strlen($password) < $char) $error = "Parola trebuie sa aiba minim $char caractere";
    // if (!preg_match('/[0-9]/', $password)) $error = "Parola trebuie sa contina numere";
    // if (!preg_match('/[a-z]/', $password)) $error = "Parola trebuie sa contina litere mici";
    // if (!preg_match('/[A-Z]/', $password)) $error = "Parola trebuie sa contina majuscule";
    // if (!preg_match('/[\W]+/', $password)) $error = "Parola trebuie sa contina caractere speciale";

    return $error;

}

function moveUploadedFile($directory, UploadedFile $uploadedFile, $username)
{
    
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = $username . '_' . bin2hex(random_bytes(8));
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}

function cryptPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function decryptPassword($password_input, $password_db) {
    return password_verify($password_input, $password_db);
}

function createToken() {
    // The algorithm manager with the HS256 algorithm.
    $algorithmManager = AlgorithmManager::create([
        new HS256(),
    ]);

    $encodedKey = base64_encode('superdupersecretkeychangeme');
    // Our key.
    $jwk = JWK::create([
        'kty' => 'oct',
        'k' => $encodedKey,
    ]);

    // The JSON Converter.
    $jsonConverter = new StandardConverter();

    // We instantiate our JWS Builder.
    $jwsBuilder = new JWSBuilder(
        $jsonConverter,
        $algorithmManager
    );
    // The payload we want to sign. The payload MUST be a string hence we use our JSON Converter.
    $payload = $jsonConverter->encode([
        'iat' => time(),
        'nbf' => time(),
        'exp' => time() + 360000,
        'iss' => 'Shopping service',
        'aud' => 'Kumparaturi',
    ]);

    $jws = $jwsBuilder
        ->create()                               // We want to create a new JWS
        ->withPayload($payload)                  // We set the payload
        ->addSignature($jwk, ['alg' => 'HS256']) // We add a signature with a simple protected header
        ->build();                               // We build it
    $serializer = new CompactSerializer($jsonConverter); // The serializer

    $token = $serializer->serialize($jws, 0); // We serialize the signature at index 0 (we only have one signature).

    return $token;
}

function getUploadErrorDescription($code) {
    $errorDescription = '';
    $phpFileUploadErrors = array(
        // 0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    );
    if (array_key_exists($code, $phpFileUploadErrors)) {
        $errorDescription = $phpFileUploadErrors[$code];
    } else {
        $errorDescription = $code;
    }

    return $errorDescription;
}

function sendPushNotification($item) {
    $content      = array(
        "en" => $item
    );
    // $hashes_array = array();
    // array_push($hashes_array, array(
    //     "id" => "like-button",
    //     "text" => "Like",
    //     "icon" => "http://i.imgur.com/N8SN8ZS.png",
    //     "url" => "https://yoursite.com"
    // ));
    // array_push($hashes_array, array(
    //     "id" => "like-button-2",
    //     "text" => "Like2",
    //     "icon" => "http://i.imgur.com/N8SN8ZS.png",
    //     "url" => "https://yoursite.com"
    // ));
    $fields = array(
        'app_id' => "be8d8ed6-32b2-4af3-a69d-ea6d3420c92b",
        'included_segments' => array(
            'All'
        ),
        'data' => array(
            "foo" => "bar"
        ),
        'contents' => $content,
        // 'web_buttons' => $hashes_array
    );
    
    $fields = json_encode($fields);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: fill  me'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

function sendEmail($to, $fromMail = '', $fromName = 'Kumparaturi aplicatie') {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = 0;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host       = '';  // Specify main and backup SMTP servers
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = '';                     // SMTP username
        $mail->Password   = '';                               // SMTP password
        $mail->SMTPSecure = 'ssl';                                  // Enable TLS encryption, `ssl` also accepted
        $mail->Port       = 465;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom($fromMail, $fromName);
        // $mail->addAddress('', 'Joe User');     // Add a recipient
        $mail->addAddress($to);               // Name is optional
        // $mail->addReplyTo('info@example.com', 'Information');
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');

        // Attachments
        // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Here is the subject';
        $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }    
}


//  ?>