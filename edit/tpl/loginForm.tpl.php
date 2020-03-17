<?php
include DIR_TPL.'header.tpl.php';
?>
    
<?php if(!$appInstalled) { ?>

    <h1 class="h3 mb-3 font-weight-normal">Instalar</h1>
    <p>Está a punto de instalar Jotter y crear su primera cuenta de usuario.</p>
    <p>Comprobación de requisitos:</p>
    <ul>
        <li class="<?php echo $phpMinVersion?'success':'error'; ?>">
            PHP <?php echo PHP_VERSION; ?> instalado (se requiere al menos PHP <?php echo $phpMinVersion; ?>):
            <?php echo $phpMinVersion?'SI':'NO'; ?>
        </li>
        <li class="<?php echo $isWritable?'success':'error'; ?>">
            Acceso de escritura para crear directorios <code>data/</code> &amp; <code>cache/</code>:
            <?php echo $isWritable?'SI':'NO'; ?>
        </li>
    </ul>
    <p>Ingrese el nombre de usuario y contraseña deseados. Inmediatamente iniciarás sesión:</p>

<?php } // if !$appInstalled ?>


    <div class="body-signin text-center">
        <form id="loginForm" class="form-signin" method="post" action="">
            <img class="mb-4" src="tpl/img/yussel.svg" alt="" width="72" height="72">
            <h1 class="h3 mb-3 font-weight-normal">Iniciar sesión</h1>
            
            <label for="login" class="sr-only">Nombre de usuario</label>
            <input type="text" id="login" name="login" class="form-control" placeholder="Nombre de usuario" required="" autofocus="autofocus" value="<?php echo isset($_POST['login'])?$_POST['login']:''; ?>">

            <label for="password" class="sr-only">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required="">

<?php if(isset($user['error']['unknownLogin']) && $user['error']['unknownLogin']) { ?>
            <span class="error">Nombre de usuario desconocido</span>
<?php } ?>

<?php if(isset($user['error']['wrongPassword']) && $user['error']['wrongPassword']) { ?>
            <span class="error">Contraseña incorrecta</span>
<?php } ?>

            <div class="checkbox mb-3">
                <label>
                    <input type="checkbox" id="remember" name="remember" value="remember"> Recuérdame
                </label>
            </div>

            <input type="submit" id="submitLoginForm" name="submitLoginForm" class="btn btn-lg btn-primary btn-block" value="Conectarse" />

        </form>

    </div>

<?php include DIR_TPL.'footer.tpl.php'; ?>