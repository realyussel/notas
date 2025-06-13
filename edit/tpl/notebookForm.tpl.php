<?php
include DIR_TPL . 'header.tpl.php';
$editNotebook = $_GET['action'] == 'edit';
// Default values
$title = 'Nuevo cuaderno';
$action = '?action=add';
$name = '';
$home_route = '';
$evo_color = '#FFFFFF';
$site_description = '';
$site_name = '';
$password = '';
if ($editNotebook) {
	$title = 'Editar cuaderno';
	$action = '?nb=' . $_GET['nb'] . '&amp;action=edit';
	$name = $_GET['nb'];
	$home_route = $notebook['home_route'];
	$evo_color = isset($notebook['color']) ? $notebook['color'] : '#EEEEEE';
	$site_description = $notebook['site_description'];
	$site_name = $notebook['site_name'];
	$password = $notebook['password'];
}
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1><?php echo $title; ?></h1>
    </div>

    <form method="post" action="<?php echo $action; ?>">

            <div class="form-group row mb-3">
                <label for="name" class="col-sm-2 col-form-label">Nombre</label>
                <div class="col-sm-10">
                    <input id="name" class="form-control" name="name" type="text" value="<?php echo $name; ?>" autofocus="autofocus">
                </div>
            </div>

<?php if (isset($errors['empty']) && $errors['empty']) {?>
    <div class="alert alert-warning">Por favor, ingresa un Nombre</div>
<?php } elseif (isset($errors['alreadyExists']) && $errors['alreadyExists']) {?>
    <div class="alert alert-warning">Ya existe un cuaderno con ese Nombre. Por favor, introduce otro.</div>
<?php }

if ($editNotebook) {?>
            <div class="form-group row mb-3">
                <label for="site_name" class="col-sm-2 col-form-label">Sitio web</label>
                <div class="col-sm-4">
                    <input id="site_name" class="form-control" name="site_name" type="text" value="<?php echo $site_name; ?>" autofocus="autofocus">
                </div>
                <label for="home_route" class="col-sm-2 col-form-label">Ruta de inicio</label>
                <div class="col-sm-4">
                    <input id="home_route" class="form-control" name="home_route" type="text" value="<?php echo $home_route; ?>">
                </div>
            </div>
            <div class="form-group row mb-3">
                <label for="site_description" class="col-sm-2 col-form-label">Descripción</label>
                <div class="col-sm-4">
                    <input id="site_description" class="form-control" name="site_description" type="text" value="<?php echo $site_description; ?>">
                </div>
                <label for="password" class="col-sm-2 col-form-label">Contraseña</label>
                <div class="col-sm-4">
                    <input id="password" class="form-control" name="password" type="text" value="<?php echo $password; ?>">
                </div>
            </div>
            <div class="form-group row mb-3">
                <label class="col-sm-2 col-form-label">Mostrar</label>
                <div class="col-sm-10">

                    <div class="form-check">
                        <input type="checkbox" name="display_chapter" id="display_chapter" class="form-check-input" <?php echo $notebook['display_chapter'] ? 'checked="checked"' : ''; ?>>
                        <label for="display_chapter" class="form-check-label">
                            <strong>Capítulos</strong>: antes del título (p. ej.: <code>1. Título</code>).
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="display_index" id="display_index" class="form-check-input" <?php echo $notebook['display_index'] ? 'checked="checked"' : ''; ?>>
                        <label for="display_index" class="form-check-label">
                            <strong>Índice</strong>: creado a partir de los encabezados.
                        </label>
                    </div>

                </div>
            </div>
            <div class="form-group row mb-3">
                <label for="color" class="col-sm-2 col-form-label">Color</label>
                <div class="col-sm-10">

                    <div id="evo-color" value="<?php echo $evo_color; ?>"></div>
                    <input id="color" class="form-control" name="color" required="true" type="hidden" value="<?php echo $evo_color; ?>">

<?php if (isset($errors['emptyColor']) && $errors['emptyColor']) {?>
    <div class="alert alert-warning">Por favor seleccione un color.</div>
<?php }?>

                </div>
            </div>
<?php }?>
        <input type="submit" class="btn btn-primary" value="<?php echo $title; ?>">
    </form>
<?php include DIR_TPL . 'footer.tpl.php';?>