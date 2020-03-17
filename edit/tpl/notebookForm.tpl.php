<?php
include DIR_TPL.'header.tpl.php';

$editNotebook = $_GET['action'] == 'edit';
if ($editNotebook ) {
    $editor = $notebook["editor"] == 'markdown';
}
$title = $editNotebook?'Editar cuaderno':'Nuevo cuaderno';


?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1><?php echo $title; ?></h1>
    </div>

    <form method="post" action="<?php echo $editNotebook?"?nb=" . $_GET['nb'] . "&amp;action=edit":'?action=add';?>">

            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Nombre</label>
                <div class="col-sm-10">
                    <input id="name" class="form-control" name="name" type="text" value="<?php echo $editNotebook?$_GET['nb']:''; ?>" <?php echo $editNotebook?'readonly':'autofocus="autofocus"'; ?>>
                </div>
            </div>

<?php if(isset($errors['empty']) && $errors['empty']) { ?>
            <p class="error">Por favor ingrese un nombre para su nuevo cuaderno.</p>
<?php } elseif(isset($errors['alreadyExists']) && $errors['alreadyExists']) { ?>
            <p class="error">Ya existe un cuaderno con este nombre. Por favor, introduzca otro.</p>
<?php } ?>


<?php if($editNotebook) {
?>
            
            <div class="form-group row">
                <label for="site_name" class="col-sm-2 col-form-label">Sitio web</label>
                <div class="col-sm-4">
                    <input id="site_name" class="form-control" name="site_name" type="text" value="<?php echo $editNotebook?$notebook["site_name"]:''; ?>" autofocus="autofocus">
                </div>
                
                <label for="site_description" class="col-sm-2 col-form-label">Descripción</label>
                <div class="col-sm-4">
                    <input id="site_description" class="form-control" name="site_description" type="text" value="<?php echo $editNotebook?$notebook["site_description"]:''; ?>">
                </div>
            </div>

            <div class="form-group row">
                <label for="home_route" class="col-sm-2 col-form-label">Ruta de inicio</label>
                <div class="col-sm-4">
                    <input id="home_route" class="form-control" name="home_route" type="text" value="<?php echo $editNotebook?$notebook["home_route"]:''; ?>">
                </div>

                <label for="password" class="col-sm-2 col-form-label">Contraseña</label>
                <div class="col-sm-4">
                    <input id="password" class="form-control" name="password" type="text" value="<?php echo $editNotebook?$notebook["password"]:''; ?>">
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Opciones</label>
                <div class="col-sm-10">

                    <div class="form-check">
                        <input type="checkbox" name="public_view" id="public_view" class="form-check-input" <?php echo $notebook["public_view"]?'checked="checked"':''; ?>>
                        <label for="public_view" class="form-check-label">
                            <strong>Contenido público</strong>: cualquier persona lo puede ver.
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="display_chapter" id="display_chapter" class="form-check-input" <?php echo $notebook["display_chapter"]?'checked="checked"':''; ?>>
                        <label for="display_chapter" class="form-check-label">
                            <strong>Mostrar capítulos</strong>: Muestra el número de capítulo (como 1.1.a.) antes del título del documento.
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="display_index" id="display_index" class="form-check-input" <?php echo $notebook["display_index"]?'checked="checked"':''; ?>>
                        <label for="display_index" class="form-check-label">
                            <strong>Mostrar índice</strong>: Muestra la navegación del índice de contenido (según el esquema del contenido).
                        </label>
                    </div>

                </div>
            </div>
        <!--p>¿El cuaderno está configurado para usar Markdown o no?</p-->

<?php } ?>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Editor</label>
                <div class="col-sm-10">

                    <div class="form-group">
                        <div class="form-check">
                                <input type="radio" name="editor" id="markdown" class="form-check-input" value="markdown" <?php echo $editNotebook?$editor?'checked="checked"':'':'checked="checked"'; ?>>
                                <label for="markdown" class="form-check-label">Markdown</label>
                        </div>

                        <div class="form-check">
                                <input type="radio" name="editor" id="wysiwyg" class="form-check-input" value="wysiwyg" <?php echo $editNotebook?$editor?'':'checked="checked"':''; ?>>
                                <label for="wysiwyg" class="form-check-label"><abbr title="What You See Is What You Get">WYSIWYG</abbr></label>
                        </div>
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" name="safe-wysiwyg" id="safe-wysiwyg" class="form-check-input" <?php echo $editNotebook?$notebook["safe"]?'checked="checked"':'':''; ?>>
                        <label for="safe-wysiwyg" class="form-check-label">
                            Haga que <abbr title="What You See Is What You Get">WYSIWYG</abbr> <strong>sea más seguro</strong>: esto se asegura de eliminar el contenido inseguro al guardar el texto pegado de una página web (puede perder un poco más de formato en el proceso). <code class="highlighter-rouge">No lo recomiendo.</code>
                        </label>
                    </div>
                </div>
            </div>

        <input type="submit" class="btn btn-lg btn-primary" value="<?php echo $title; ?>">
    </form>
<?php include DIR_TPL.'footer.tpl.php'; ?>