<?php
include DIR_TPL.'header.tpl.php';

$editItem = $_GET['action'] == 'edit';

if(empty($isDir) and empty($isNote)) {
    $isDir = $_GET['action'] == 'adddir';
}

if(isset($_GET['item'])) {
    $file = new SplFileInfo($_GET['item']);
    $fileName = $file->getBasename();
    $fileName = str_replace("." . $file->getExtension(), "", $fileName);
}

$title = $editItem?($isDir?'Editar Carpeta':'Editar Apunte'):($isDir?'Nueva Carpeta':'Nuevo Apunte');

?>
    
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1><?php echo $title; ?></h1>
    </div>

    <form method="post" action="?nb=<?php echo $notebookName; ?>&amp;action=<?php echo $_GET['action']; ?>&amp;item=<?php echo isset($_GET['item'])?$_GET['item']:''; ?>">
        <div class="form-group row">
            <label for="name" class="col-sm-2 col-form-label">Nombre</label>
            <div class="col-sm-10">
                <input id="name" class="form-control" name="name" type="text" value="<?php echo $editItem?$fileName:''; ?>" autofocus="autofocus">
            </div>
        </div>

    <?php if(isset($errors['empty']) && $errors['empty']) { ?>
        <div class="error">Por favor, ingrese un nombre.</div>
    <?php } elseif(isset($errors['sameName']) && $errors['sameName']) { ?>
        <div class="error">Ya tiene ese nombre.</div>
    <?php } elseif(isset($errors['alreadyExists']) && $errors['alreadyExists']) { ?>
        <div class="error">Ya existe un elemento con este nombre en esta carpeta. Por favor, introduzca otro.</div>
    <?php } ?>

        <input type="submit" class="btn btn-lg btn-primary" value="<?php echo $title; ?>">

    </form>

<?php if(!$isDir) { ?>

<div class="statblock">
    <div class="statblock__icon">
        <img src="tpl/img/feather/sidebar.svg" alt="">
    </div>
    <p>Puede definir un <span class="stat">capítulo</span>, usando dígitos o letras terminados con un punto <code>.</code>.<br/><small>El Visor creará un esquema de documentos basado en los números de capitulo.</small></p>
</div>

<div class="statblock">
    <div class="statblock__icon">
        <img src="tpl/img/feather/hash.svg" alt="">
    </div>
    <p>Puede agregar un <span class="stat">identificador</span>, encerrado una cadena por un par de corchetes <code>[</code> y <code>]</code>.<br/><small>Internamente puede usar el identificador para crear un enlace relativo <code>[Enlace](#/identificador)</code>.</p>
</div>

<?php } include DIR_TPL.'footer.tpl.php'; ?>