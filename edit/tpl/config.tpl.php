<?php include DIR_TPL . 'header.tpl.php';
if ($option == 'myPassword') {?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Cambiar mi contraseña</h2>
    </div>
    <form id="NewPasswordForm" method="post" action="">
        <div class="row g-3 mb-3 align-items-center">
            <div class="col-auto">
                <label for="password" class="col-form-label">Nueva contraseña</label>
            </div>
            <div class="col-auto">
                <input type="password" id="password" name="password" class="form-control" autofocus="autofocus">
            </div>
            <div class="col-auto">
                <input type="submit" name="submitNewPassword" id="submitNewPassword" class="btn btn-primary" value="Cambiar contraseña">
            </div>
        </div>
        <?php if (isset($error['emptyPassword']) && $error['emptyPassword']) {?>
        <div class="alert alert-warning">Por favor ingrese una contraseña</div>
        <?php } elseif (isset($error['save']) && $error['save']) {?>
        <div class="alert alert-danger">Error desconocido al guardar la contraseña</div>
        <?php } //error ?>
    </form>

<?php } elseif ($option == 'addUser') {?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Agregar un usuario</h2>
    </div>

    <form id="userForm" method="post" action="">

<div class="mb-3 row">
    <label for="login" class="col-sm-2 col-form-label">Nombre de usuario</label>
  <div class="col-sm-4">
      <input type="text" id="login" name="login" class="form-control" placeholder="Nombre de usuario" value="<?php echo isset($_POST['login']) ? $_POST['login'] : ''; ?>">
  </div>
  <div class="col-sm-6">
      <span id="passwordHelpInline" class="form-text">
    <?php if (isset($errors['emptyLogin']) && $errors['emptyLogin']) {?>
        Nombre de usuario no debe estar vacío.
    <?php } elseif (isset($errors['notAvailable']) && $errors['notAvailable']) {?>
        Nombre de usuario no disponible.
    <?php }?>
    </span>
  </div>
</div>



<div class="mb-3 row">
    <label for="password" class="col-sm-2 col-form-label">Contraseña</label>
  <div class="col-sm-4">
      <input type="password" id="password" name="password" class="form-control" placeholder="Password">
  </div>
  <div class="col-sm-6">
      <span id="passwordHelpInline" class="form-text">
    <?php if (isset($errors['emptyPassword']) && $errors['emptyPassword'] && !empty($login)) {?>
        La contraseña no debe estar vacía.
    <?php }?>
    </span>
  </div>
</div>

        <input type="submit" name="submitUserForm" id="submitUserForm" class="btn btn-primary" value="Agregar usuario">

    </form>

<?php } elseif ($option == 'deleteUser') {?>

    <div class="bg-light p-5 rounded">
        <h2>Eliminar usuario</h2>
        <p class="lead">Estás a punto de eliminar el usuario <strong><?php echo $login; ?></strong>.</p>
        <form method="post" action="">
            <input id="deleteUserSubmit" class="btn btn-lg btn-danger my-2" name="deleteUserSubmit" type="submit" value="Eliminar">
        </form>
    </div>

<?php } else {?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Opciones de configuración</h2>
    </div>

<?php }
include DIR_TPL . 'footer.tpl.php';?>