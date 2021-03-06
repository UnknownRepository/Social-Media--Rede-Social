<?php
include('./classes/DB.php');
include_once('./classes/cookie_login.php');
include('./classes/Post.php');
include('./classes/Comment.php');
include_once('./classes/notifyClass.php');
 $userid = Login::isLoggedIn();
$username = "";
$username = DB::query('SELECT username from users where id =:id', array(':id'=>$userid))[0]['username'];
//print_r($username) ;
if (Login::IsLoggedIn()) 
{
  $userid=Login::IsLoggedIn();
  
}
else
{
    die(header("Location: login.php"));
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Untitled</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Alegreya">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
    <link rel="stylesheet" href="assets/css/Footer-Dark.css">
    <link rel="stylesheet" href="assets/css/Google-Style-Login.css">
    <link rel="stylesheet" href="assets/css/Login-Form-Clean.css">
    <link rel="stylesheet" href="assets/css/Login-Form-Dark.css">
    <link rel="stylesheet" href="assets/css/Navigation-with-Search-1.css">
    <link rel="stylesheet" href="assets/css/Navigation-with-Search-2.css">
    <link rel="stylesheet" href="assets/css/Navigation-with-Search.css">
    <link rel="stylesheet" href="assets/css/Search-Field-With-Icon.css">
    <link rel="stylesheet" href="assets/css/untitled.css">
    
</head>

<body>
    <header>
        <nav class="navbar navbar-light navbar-expand-md shadow" style="border-color: #cbcbcb;">
            <div class="container-fluid"><a class="navbar-brand" href="#" style="background-image: url(&quot;assets/img/índice.png&quot;);"></a>
                <button data-toggle="collapse" class="navbar-toggler" data-target="#navcol-1"><span class="sr-only">Toggle navigation</span><span class="navbar-toggler-icon"></span></button>
                <h1 style="color: rgb(0,0,0); position: absolute; left: 40%;">Mensagens</h1>
                <div class="collapse navbar-collapse" id="navcol-1" style="width: 563px;margin-right: 0px;">
                    <ul class="nav navbar-nav ml-auto">
                        <li class="nav-item" role="presentation"><a class="nav-link active text-left" href="homepage.php" style="font-size: 20px;">Pagina Principal</a></li>
                        <li class="nav-item" role="presentation"><a class="nav-link" href="messages.php" style="font-size: 20px;">Mensagens</a></li>
                        <li class="nav-item" role="presentation"><a class="nav-link" href="notify.php" style="font-size: 20px;">Notificações</a></li>
                           <li class="nav-item dropdown"><a class="dropdown-toggle nav-link" data-toggle="dropdown" aria-expanded="false" href="#" style="font-size: 20px;">Utilizador</a>
                            <div class="dropdown-menu" role="menu"><a class="dropdown-item" role="presentation" href="profile.php?u=<?php echo $username; ?>">Profile</a><a class="dropdown-item" role="presentation" href="logout.php">Sair</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    </br>
    </br/>
    <div style="position: relative;">
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-xl-3" style="color: rgb(0,0,0);">
                   <ul class="list-group" id="users">
                    </ul>
                </div>
                <div class="col-md-6 col-xl-9" style="position: relative;">
                    <ul class="list-group">
                        <li class="list-group-item" id="m" style="overflow: auto;height: 500px;width: 100%;margin-bottom: 55px;position: relative;margin-bottom: initial;border-radius: 12px;">
                        </li>
                    </ul>
                    <div class="row">
                        <div class="col">
                            <textarea spellcheck="true" id="messagecontent"></textarea>
                            <button class="btn btn-primary text-monospace" id='sendmessage' type="button" style="width: 10%;/*padding-top: 0px;*//*margin-bottom: 4.5%;*/font-size: 14px; position: absolute;">Send</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div style="position: relative;">
        <div class="footer-dark"  >
        <footer >
            <div class="container">
                <p class="copyright">Social Media© 2020</p>
            </div>
        </footer>
    </div>
    </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script type="text/javascript">
    SENDER = window.location.hash.split('#')[1];
    USERNAME = "";
    function getUsername() {
            $.ajax({

                    type: "GET",
                    url: "restapi/users",
                    processData: false,
                    contentType: "application/json",
                    data: '',
                    success: function(r) {
                            USERNAME = r;
                    }
            })
    }

    $(document).ready(function() {

            $(window).on('hashchange', function() {
                    location.reload()
            })

            $('#sendmessage').click(function() {
                    $.ajax({

                            type: "POST",
                            url: "restapi/message",
                            processData: false,
                            contentType: "application/json",
                            data: '{ "body": "'+ $("#messagecontent").val() +'", "receiver": "'+ SENDER +'" }',
                            success: function(r) {
                                    location.reload()
                            },
                            error: function(r) {

                            }
                    })
            })

            $.ajax({

                    type: "GET",
                    url: "restapi/musers",
                    processData: false,
                    contentType: "application/json",
                    data: '',
                    success: function(r) {
                            r = JSON.parse(r)
                            for (var i = 0; i < r.length; i++) {
                                    $('#users').append('<li id="user'+i+'" data-id='+r[i].id+' class="list-group-item" style="background-color:#FFF;"><span style="font-size:16px;"><strong>'+r[i].username+'</strong></span></li>')
                                    $('#user'+i).click(function() {
                                            window.location = 'messages.php#' + $(this).attr('data-id')
                                    })
                            }
                    }
            })

            $.ajax({

                    type: "GET",
                    url: "restapi/messages?sender="+SENDER,
                    processData: false,
                    contentType: "application/json",
                    data: '',
                    success: function(r) {
                            r = JSON.parse(r)
                            $.ajax({

                                    type: "GET",
                                    url: "restapi/users",
                                    processData: false,
                                    contentType: "application/json",
                                    data: '',
                                    success: function(u) {
                                            USERNAME = u;
                                            for (var i = 0; i < r.length; i++) {
                                                    if (r[i].Sender == USERNAME) {
                                                            $('#m').append('<p                                class="text-break text-capitalize" style="position: relative;color: rgb(0,0,0);left: 50%;width: 50%;border-radius: 10px;background-color: #e8e8e8;padding: 10px;">'+r[i].body+'</p>')
                                                    } else {
                                                            $('#m').append('<p class="text-break text-capitalize" style="background-color: #d2f4dc;width: 60%;border-radius: 10px;color: rgb(0,0,0);padding: 1%;overflow: hidden;">'+r[i].body+'</p>')
                                                    }
                                            }
                                    }
                            })
                    },
                    error: function(r) {
                            console.log(r)
                    }
             })
    })
    </script>
</body>

</html>