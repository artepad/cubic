<?php
session_start();
require_once 'config/config.php';

// Definir constantes para mensajes de error
define('ERR_USER_NOT_FOUND', 'Usuario no encontrado.');
define('ERR_INCORRECT_PASSWORD', 'Contraseña incorrecta.');
define('ERR_GENERAL', 'Oops! Algo salió mal. Por favor, intenta nuevamente más tarde.');

$login_err = '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y sanitizar entradas
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    if ($username && $password) {
        $login_err = authenticate_user($conn, $username, $password);
    } else {
        $login_err = ERR_GENERAL;
    }
}

$conn->close();

function authenticate_user($conn, $username, $password) {
    $sql = "SELECT id, username, password FROM usuarios WHERE username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $username, $hashed_password);
                if ($stmt->fetch()) {
                    if (password_verify($password, $hashed_password)) {
                        session_regenerate_id();
                        $_SESSION['loggedin'] = TRUE;
                        $_SESSION['username'] = $username;
                        $_SESSION['id'] = $id;
                        header("location: index.php");
                        exit;
                    } else {
                        return ERR_INCORRECT_PASSWORD;
                    }
                }
            } else {
                return ERR_USER_NOT_FOUND;
            }
        } else {
            return ERR_GENERAL;
        }
        $stmt->close();
    } else {
        return ERR_GENERAL;
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords" content="">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/plugins/images/favicon.png">
    <title>Schaaf Producciones - Login</title>
    <link href="assets/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/animate.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/colors/default.css" id="theme" rel="stylesheet">
</head>
<body class="mini-sidebar">
    <div class="preloader">
        <div class="cssload-speeding-wheel"></div>
    </div>
    <section id="wrapper" class="login-register">
        <div class="login-box">
            <div class="white-box">
                <form class="form-horizontal form-material" id="loginform" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <h3 class="box-title m-b-20">Iniciar Sesión</h3>
                    <?php if (!empty($login_err)): ?>
                        <div class="alert alert-danger"><?php echo $login_err; ?></div>
                    <?php endif; ?>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <input class="form-control" type="text" required="" placeholder="Usuario" name="username">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <input class="form-control" type="password" required="" placeholder="Contraseña" name="password">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12">
                            <div class="checkbox checkbox-primary pull-left p-t-0">
                                <input id="checkbox-signup" type="checkbox">
                                <label for="checkbox-signup"> Recuérdame </label>
                            </div>
                            <a href="javascript:void(0)" id="to-recover" class="text-dark pull-right"><i class="fa fa-lock m-r-5"></i> ¿Olvidaste la contraseña?</a>
                        </div>
                    </div>
                    <div class="form-group text-center m-t-20">
                        <div class="col-xs-12">
                            <button class="btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light" type="submit">Iniciar Sesión</button>
                        </div>
                    </div>
                    <div class="form-group m-b-0">
                        <div class="col-sm-12 text-center">
                            <p>www.schaafproducciones.cl <a href="https://www.schaafproducciones.cl" class="text-primary m-l-5"><b>Ir Ahora!</b></a></p>
                        </div>
                    </div>
                </form>
                <form class="form-horizontal" id="recoverform" action="index.html">
                    <div class="form-group">
                        <div class="col-xs-12">
                            <h3>Recuperar Contraseña</h3>
                            <p class="text-muted">¡Ingresa tu correo electrónico y te enviaremos las instrucciones!</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <input class="form-control" type="text" required="" placeholder="Correo Electrónico">
                        </div>
                    </div>
                    <div class="form-group text-center m-t-20">
                        <div class="col-xs-12">
                            <button class="btn btn-primary btn-lg btn-block text-uppercase waves-effect waves-light" type="submit">Restablecer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script src="assets/plugins/components/jquery/dist/jquery.min.js"></script>
    <script src="assets/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="assets/js/sidebarmenu.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/plugins/components/styleswitcher/jQuery.style.switcher.js"></script>
</body>
</html>