<?php
include DIR_TPL.'header.tpl.php';

if($option == 'myPassword') { ?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Cambiar mi contraseña</h1>
    </div>

    <div class="alert alert-info" role="info">
        Introduce la nueva contraseña y, a continuación, pulsa Cambiar contraseña.
    </div>

    <form id="NewPasswordForm" method="post" action="">
        <div class="form-group row">
            <label for="password" class="col-sm-3 col-form-label">Nueva contraseña</label>
            <div class="col-sm-9">
                <input type="password" name="password" id="password" class="form-control" autofocus="autofocus">
<?php if(isset($error['emptyPassword']) && $error['emptyPassword']) { ?>
            <span class="error">Por favor ingrese una contraseña</span>
<?php } elseif(isset($error['save']) && $error['save']) { ?>
            <span class="error">Error desconocido al guardar la contraseña</span>
<?php } //error ?>
            </div>
        </div>
        <input type="submit" name="submitNewPassword" id="submitNewPassword" class="btn btn-lg btn-warning my-2" value="Cambiar contraseña" />
    </form>
    
<?php } elseif($option == 'addUser') { ?>
    

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Agregar un usuario</h1>
    </div>

    <div class="alert alert-info" role="info">
        Introduce un nombre de usuario, una contraseña y, a continuación, pulsa Agregar usuario, para darle acceso al editor.
    </div>

    <div class="body-signin">
    <form id="userForm" class="form-signin" method="post" action="">

            <label for="login" class="sr-only">Nombre de usuario</label>
            <input type="text" autofocus="autofocus" name="login" id="login" class="form-control" laceholder="Nombre de usuario" value="<?php echo isset($_POST['login'])?$_POST['login']:''; ?>">
<?php if(isset($errors['emptyLogin']) && $errors['emptyLogin']) { ?>
            <span class="error">Nombre de usuario no debe estar vacío</span>
<?php } elseif(isset($errors['notAvailable']) && $errors['notAvailable']) { ?>
            <span class="error">Nombre de usuario no disponible</span>
<?php } ?>

            <label for="password" class="sr-only">Contraseña</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Password">
<?php if(isset($errors['emptyPassword']) && $errors['emptyPassword'] && !empty($login)) { ?>
            <span class="error">La contraseña no debe estar vacía</span>
<?php } ?>

        <input type="submit" name="submitUserForm" id="submitUserForm" class="btn btn-lg btn-primary btn-block" value="Agregar usuario" />
    </form>
    </div>

<?php } elseif($option == 'deleteUser') { ?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Eliminar usuario: <?php echo $login; ?></h1>
    </div>

    <div class="alert alert-warning" role="alert">
        Está a punto de eliminar un usuario y todos sus cuadernos.
        ¡No hay vuelta atrás!
    </div>

    <form method="post" action="">
        <input id="deleteUserSubmit" class="btn btn-lg btn-danger my-2" name="deleteUserSubmit" type="submit" value="Eliminar: <?php echo $login; ?>">
    </form>
<?php } else { ?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
        <h1 class="h2">Opciones de configuración</h1>
    </div>

<?php
}

include DIR_TPL.'footer.tpl.php';
?>