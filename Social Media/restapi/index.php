<?php
require_once("DB.php");


$db = new DB("127.0.0.1", "pap", "root", "");

if ($_SERVER['REQUEST_METHOD'] == "GET") {

        if ($_GET['url'] == "musers") {

                $token = $_COOKIE['SNID'];
                $userid = $db->query('SELECT user_id FROM tokens WHERE token=:token', array(':token'=>sha1($token)))[0]['user_id'];

                $users = $db->query("SELECT DISTINCT s.username AS Sender, r.username AS Receiver, s.id AS SenderID, r.id AS ReceiverID FROM messages LEFT JOIN users s ON s.id = messages.sender LEFT JOIN users r ON r.id = messages.receiver WHERE (s.id = :userid OR r.id=:userid)", array(":userid"=>$userid));
                $u = array();
                foreach ($users as $user) {
                        if (!in_array(array('username'=>$user['Receiver'], 'id'=>$user['ReceiverID']), $u)) {
                                array_push($u, array('username'=>$user['Receiver'], 'id'=>$user['ReceiverID']));
                        }
                        if (!in_array(array('username'=>$user['Sender'], 'id'=>$user['SenderID']), $u)) {
                                array_push($u, array('username'=>$user['Sender'], 'id'=>$user['SenderID']));
                        }
                }
                echo json_encode($u);

        } else if ($_GET['url'] == "auth") {

        } else if ($_GET['url'] == "messages") {
                $sender = $_GET['sender'];
                $token = $_COOKIE['SNID'];
                $receiver = $db->query('SELECT user_id FROM tokens WHERE token=:token', array(':token'=>sha1($token)))[0]['user_id'];

                $messages = $db->query('SELECT messages.id, messages.body, s.username AS Sender, r.username AS Receiver
FROM messages
LEFT JOIN users s ON messages.sender = s.id
LEFT JOIN users r ON messages.receiver = r.id
WHERE (r.id=:r AND s.id=:s) OR r.id=:s AND s.id=:r', array(':r'=>$receiver, ':s'=>$sender));

echo json_encode($messages);

        } else if ($_GET['url'] == "search") {

                $tosearch = explode(" ", $_GET['query']);
                if (count($tosearch) == 1) {
                        $tosearch = str_split($tosearch[0], 2);
                }

                $whereclause = "";
                $paramsarray = array(':body'=>'%'.$_GET['query'].'%');
                for ($i = 0; $i < count($tosearch); $i++) {
                        if ($i % 2) {
                        $whereclause .= " OR body LIKE :p$i ";
                        $paramsarray[":p$i"] = $tosearch[$i];
                        }
                }
                $posts = $db->query('SELECT posts.id, posts.body, users.username, posts.posted_at FROM posts, users WHERE users.id = posts.user_id AND posts.body LIKE :body '.$whereclause.' LIMIT 10', $paramsarray);
                //echo "<pre>";
                echo json_encode($posts);

        } else if ($_GET['url'] == "users") {

                $token = $_COOKIE['SNID'];
                $user_id = $db->query('SELECT user_id FROM tokens WHERE token=:token', array(':token'=>sha1($token)))[0]['user_id'];
                $username = $db->query('SELECT username FROM users WHERE id=:uid', array(':uid'=>$user_id))[0]['username'];
                echo $username;

        } else if ($_GET['url'] == "comments" && isset($_GET['postid'])) {
                $output = "";
                $comments = $db->query('SELECT DISTINCT comments.comment, users.username FROM comments, users WHERE post_id = :postid AND comments.user_id = users.id', array(':postid'=>$_GET['postid']));
                $output .= "[";
                foreach($comments as $comment) {
                        $output .= "{";
                        $output .= '"Comment": "'.$comment['comment'].'",';
                        $output .= '"CommentedBy": "'.$comment['username'].'"';
                        $output .= "},";
                        //echo $comment['comment']." ~ ".$comment['username']."<hr />";
                }
                $output = substr($output, 0, strlen($output)-1);
                $output .= "]";
                echo $output;

        } else if ($_GET['url'] == "posts") {

                $token = $_COOKIE['SNID'];

                $userid = $db->query('SELECT user_id FROM tokens WHERE token=:token', array(':token'=>sha1($token)))[0]['user_id'];

                $followingposts = $db->query('SELECT DISTINCT posts.id, posts.body, posts.posted_at, posts.postimg, posts.likes, users.username FROM users, posts, seguidores
                WHERE (posts.user_id = seguidores.user_id
                OR posts.user_id = :userid)
                AND users.id = posts.user_id      
                ORDER BY posts.posted_at DESC;', array(':userid'=>$userid), array(':userid'=>$userid));
                $response = "[";
                foreach($followingposts as $post) {

                        $response .= "{";
                                $response .= '"PostId": '.$post['id'].',';
                                $response .= '"PostBody": "'.$post['body'].'",';
                                $response .= '"PostedBy": "'.$post['username'].'",';
                                $response .= '"PostDate": "'.$post['posted_at'].'",';
                                $response .= '"PostImage": "'.$post['postimg'].'",';
                                $response .= '"Likes": '.$post['likes'].'';
                        $response .= "},";


                }
                $response = substr($response, 0, strlen($response)-1);
                $response .= "]";

                http_response_code(200);
                echo $response;

        } else if ($_GET['url'] == "profileposts") {
                $start = (int)$_GET['start'];
                $userid = $db->query('SELECT id FROM users WHERE username=:username', array(':username'=>$_GET['u']))[0]['id'];

                $followingposts = $db->query('SELECT DISTINCT posts.id, posts.body, posts.posted_at, posts.postimg, posts.likes, users.`username` FROM users, posts
                WHERE users.id = posts.user_id
                AND users.id = :userid
                ORDER BY posts.posted_at DESC
                LIMIT 5
                OFFSET '.$start.';', array(':userid'=>$userid));
                $response = "[";
                foreach($followingposts as $post) {

                        $response .= "{";
                                $response .= '"PostId": '.$post['id'].',';
                                $response .= '"PostBody": "'.$post['body'].'",';
                                $response .= '"PostedBy": "'.$post['username'].'",';
                                $response .= '"PostDate": "'.$post['posted_at'].'",';
                                $response .= '"PostImage": "'.$post['postimg'].'",';
                                $response .= '"Likes": '.$post['likes'].'';
                        $response .= "},";


                }
                $response = substr($response, 0, strlen($response)-1);
                $response .= "]";

                http_response_code(200);
                echo $response;

        }

} else if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($_GET['url'] == "message") 
    {
        $token = $_COOKIE['SNID'];

        $userid = $db->query('SELECT user_id FROM tokens WHERE token=:token', array(':token'=>sha1($token)))[0]['user_id'];

        $postBody = file_get_contents("php://input");
        $postBody = json_decode($postBody);

        $body = $postBody->body;
        $receiver = $postBody->receiver;

        if (strlen($body) > 100) {
                echo "{ 'Error': 'Message too long!' }";
        }
if ($body == null) {
          $body = "";
        }
        if ($receiver == null) {
          die();
        }
        if ($userid == null) {
          die();
        }
        $db->query("INSERT INTO messages VALUES ('', :body, :sender, :receiver, '0')", array(':body'=>$body, ':sender'=>$userid, ':receiver'=>$receiver));

        echo '{ "Success": "Message Sent!" }';

        }

        

         else if ($_GET['url'] == "users") {

                $postBody = file_get_contents("php://input");
                $postBody = json_decode($postBody);

                $PrimeiroNome = $postBody->PrimeiroNome;
                $UltimoNome = $postBody->UltimoNome;
                $Username = $postBody->Username;
                $Email = $postBody->Email;
                $EmailRepeat = $postBody->EmailRepeat;
                $password = $postBody->password;
                $PasswordRepeat = $postBody->PasswordRepeat;
                


                if (!$db->query('SELECT username FROM users WHERE email=:email', array(':email'=>$Email))) {

                        if (strlen($Username) >= 3 && strlen($Username) <= 32) {

                                if (preg_match('/[a-zA-Z0-9_]+/', $Username)) {

                                        if (strlen($password) >= 6 && strlen($password) <= 60) {

                                        if (filter_var($Email, FILTER_VALIDATE_EMAIL)) {

                                        if (!$db->query('SELECT email FROM users WHERE email=:email', array(':email'=>$Email))) {

                                                $db->query('INSERT INTO users VALUES (\'\', :PrimeiroNome, :UltimoNome, :Username,:email,:password,\'\')', array(':PrimeiroNome'=>$PrimeiroNome,':UltimoNome'=>$UltimoNome,':Username'=>$Username,':email'=>$Email, ':password'=>password_hash($password, PASSWORD_BCRYPT)));
                                                
                                                echo '{ "Success": "Conta criada com sucesso!" }';
                                                http_response_code(200);
                                        } else {
                                                echo '{ "Error": "Email in use!" }';
                                                http_response_code(409);
                                        }
                                } else {
                                        echo '{ "Error": "Invalid Email!" }';
                                        http_response_code(409);
                                        }
                                } else {
                                        echo '{ "Error": "Invalid Password!" }';
                                        http_response_code(409);
                                }
                                } else {
                                        echo '{ "Error": "Invalid Username!" }';
                                        http_response_code(409);
                                }
                        } else {
                                echo '{ "Error": "Invalid Username!" }';
                                http_response_code(409);
                        }

                } else {
                        echo '{ "Error": "User exists!" }';
                        http_response_code(409);
                }


        }

        if ($_GET['url'] == "post") {
                $token = $_COOKIE['SNID'];

                $userid = $db->query('SELECT user_id FROM tokens WHERE token=:token', array(':token'=>sha1($token)))[0]['user_id'];
                echo "Dfdf";
        }

        if ($_GET['url'] == "auth") {
                $postBody = file_get_contents("php://input");
                $postBody = json_decode($postBody);

                $email = $postBody->email;
                $password = $postBody->password;

                if ($db->query('SELECT email FROM users WHERE email=:email', array(':email'=>$email))) {
                        if (password_verify($password, $db->query('SELECT password FROM users WHERE email=:email', array(':email'=>$email))[0]['password'])) {
                                $cstrong = True;
                                $token = bin2hex(openssl_random_pseudo_bytes(64, $cstrong));
                                $user_id = $db->query('SELECT id FROM users WHERE email=:email', array(':email'=>$email))[0]['id'];
                                $db->query('INSERT INTO tokens VALUES (\'\', :token, :user_id)', array(':token'=>sha1($token), ':user_id'=>$user_id));
                                setcookie("SNID",$token, time() + 60*60*24*7, '/',NULL,NULL,TRUE);
            setcookie("SNID:",'1',time() + 60*60*24*3, '/',NULL,NULL,TRUE);
                                echo '{ "Token": "'.$token.'" }';
                        } else {
                                echo '{ "Error": "Invalid email or password!" }';
                                http_response_code(401);
                        }
                } else {
                        echo '{ "Error": "Invalid email or password!" }';
                        http_response_code(401);
                }

        } else if ($_GET['url'] == "likes") {
                $postId = $_GET['id'];
                $token = $_COOKIE['SNID'];
                $likerId = $db->query('SELECT user_id FROM tokens WHERE token=:token', array(':token'=>sha1($token)))[0]['user_id'];

                if (!$db->query('SELECT user_id FROM post_likes WHERE post_id=:postid AND user_id=:userid', array(':postid'=>$postId, ':userid'=>$likerId))) {

                        $db->query('UPDATE posts SET likes=likes+1 WHERE id=:postid', array(':postid'=>$postId));
                        $db->query('INSERT INTO post_likes VALUES (\'\', :postid, :userid)', array(':postid'=>$postId, ':userid'=>$likerId));
                        //Notify::createNotify("", $postId);
                } else {
                        $db->query('UPDATE posts SET likes=likes-1 WHERE id=:postid', array(':postid'=>$postId));
                        $db->query('DELETE FROM post_likes WHERE post_id=:postid AND user_id=:userid', array(':postid'=>$postId, ':userid'=>$likerId));
                }

                echo "{";
                echo '"Likes":';
                echo $db->query('SELECT likes FROM posts WHERE id=:postid', array(':postid'=>$postId))[0]['likes'];
                echo "}";
        }

}  else if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
     if ($_GET['url'] =="DeletePost") 
      {
        $postId = $_GET['id'];
        $token = $_COOKIE['SNID'];
        $followerid = $db->query('SELECT  user_id FROM tokens WHERE token=:token', array(':token'=>sha1($token)))[0]['user_id'];
         if($db->query('SELECT id FROM posts WHERE id =:postid AND user_id =:userid', array(':postid'=>$postId,':userid'=>$followerid)))
                /*$response = "[";
                foreach($followingposts as $post) {

                        $response .= "{";
                                $response .= '"PostId": '.$post['id'].',';
                        $response .= "},";
                        $response = substr($response, 0, strlen($response)-1);
                         $response .= "]";*/
                    {
                        $db->query('DELETE FROM posts WHERE id =:postid AND user_id =:userid',array(':postid'=>$postId,':userid'=>$followerid));
                        $db->query('DELETE FROM post_likes WHERE post_id =:postid',array(':postid'=>$postId));
                    }
        
      }
        if ($_GET['url'] == "auth") {
                if (isset($_GET['token'])) {
                        if ($db->query("SELECT token FROM tokens WHERE token=:token", array(':token'=>sha1($_GET['token'])))) {
                                $db->query('DELETE FROM tokens WHERE token=:token', array(':token'=>sha1($_GET['token'])));
                                echo '{ "Status": "Success" }';
                                http_response_code(200);
                        } else {
                                echo '{ "Error": "Invalid token" }';
                                http_response_code(400);
                        }
                } else {
                        echo '{ "Error": "Malformed request" }';
                        http_response_code(400);
                }
        }
} else {
        http_response_code(405);
}

// Helper functionsrequire_once("Mail.php");
?>