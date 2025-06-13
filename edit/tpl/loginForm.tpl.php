<?php
include DIR_TPL . 'header.tpl.php';
?>

<?php if (!$appInstalled) {?>

    <h1 class="h3 mb-3 font-weight-normal">Instalar</h1>
    <p>Está a punto de instalar notas y crear su primera cuenta de usuario.</p>
    <p>Comprobación de requisitos:</p>
    <ul>
        <li class="<?php echo $phpMinVersion ? 'success' : 'error'; ?>">
            PHP <?php echo PHP_VERSION; ?> instalado (se requiere al menos PHP <?php echo $phpMinVersion; ?>):
            <?php echo $phpMinVersion ? 'SI' : 'NO'; ?>
        </li>
        <li class="<?php echo $isWritable ? 'success' : 'error'; ?>">
            Acceso de escritura para crear directorios <code>data/</code> &amp; <code>cache/</code>:
            <?php echo $isWritable ? 'SI' : 'NO'; ?>
        </li>
    </ul>
    <p>Ingrese el nombre de usuario y contraseña deseados. Inmediatamente iniciarás sesión:</p>

<?php } // if !$appInstalled ?>

<?php if (isset($user['error']['unknownLogin']) && $user['error']['unknownLogin']) {?>
            <span class="alert alert-warning">Nombre de usuario desconocido</span>
<?php }?>

<?php if (isset($user['error']['wrongPassword']) && $user['error']['wrongPassword']) {?>
            <span class="alert alert-danger">Contraseña incorrecta</span>
<?php }?>
<div class="text-center">
    <main class="form-signin">
      <form id="loginForm" method="post" action="">
        <img class="mb-4" src="../icon/n.svg" alt="" width="66" height="66">
        <h1 class="h3 mb-3 fw-normal">Accede tu cuenta</h1>
        <div class="form-floating mb-2">
          <input type="text" class="form-control" id="login" name="login" placeholder="Nombre de usuario" required="true" autofocus="autofocus" value="<?php echo isset($_POST['login']) ? $_POST['login'] : ''; ?>">
          <label for="login">Nombre de usuario</label>
        </div>
        <div class="form-floating">
          <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required="true">
          <label for="password">Contraseña</label>
        </div>
        <div class="checkbox my-3">
          <label>
            <input type="checkbox" id="remember" name="remember" value="remember" checked="true"> Mantener la sesión iniciada
          </label>
        </div>
        <input class="w-100 btn btn-lg btn-primary" type="submit" id="submitLoginForm" name="submitLoginForm" value="Enviar"></input>
      </form>
    </main>
</div>
<?php include DIR_TPL . 'footer.tpl.php';?>