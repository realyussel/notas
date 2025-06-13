<?php
include DIR_TPL . 'header.tpl.php';
$editItem = $_GET['action'] == 'edit';

if (empty($isDir) and empty($isNote)) {
	$isDir = $_GET['action'] == 'adddir';
}

$title = ($isDir ? 'Nueva Carpeta' : 'Nuevo Apunte');
$action = '?action=add';
$chapter = '';
$slug = '';

if ($editItem) {
	$title = ($isDir ? 'Editar Carpeta' : 'Editar Apunte');
	$action = '?nb=' . $_GET['nb'] . '&amp;action=edit';
	$chapter = $item['chapter'];
	$slug = $item['slug'];
}

if (isset($_GET['item'])) {
	$file = new SplFileInfo($_GET['item']);
	$fileName = $file->getBasename();
	$fileName = str_replace("." . $file->getExtension(), "", $fileName);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1><?php echo $title; ?></h1>
</div>

<form method="post" action="?nb=<?php echo $notebookName; ?>&amp;action=<?php echo $_GET['action']; ?>&amp;item=<?php echo isset($_GET['item']) ? $_GET['item'] : ''; ?>">


    <div class="form-group row mb-3">
        <label for="name" class="col-sm-2 col-form-label">Nombre</label>
        <div class="col-sm-10">
            <input id="name" class="form-control" name="name" type="text" value="<?php echo $editItem ? $fileName : ''; ?>" autofocus="autofocus">
        </div>
    </div>

    <?php if (isset($errors['empty']) && $errors['empty']) {?>
    <div class="alert alert-warning">Por favor, ingresa un Nombre.</div>
    <?php } elseif (isset($errors['alreadyExists']) && $errors['alreadyExists']) {?>
    <div class="alert alert-warning">
        Ya existe <?php echo $isDir ? 'una carpeta' : 'un apunte'; ?> con ese Nombre. Por favor, introduce otro.
    </div>
    <?php }?>

<?php if ($editItem) {?>
    <div class="form-group row mb-3">
        <label for="chapter" class="col-sm-2 col-form-label">Capítulo</label>
        <div class="col-sm-4">
            <input id="chapter" class="form-control" name="chapter" type="text" value="<?php echo $chapter; ?>" autofocus="autofocus">
        </div>
        <label for="slug" class="col-sm-2 col-form-label">Identificador</label>
        <div class="col-sm-4">
            <input id="slug" class="form-control" name="slug" type="text" value="<?php echo $slug; ?>">
        </div>
    </div>
    <div class="form-group row mb-3">
        <label class="col-sm-2 col-form-label">Contenido</label>
        <div class="col-sm-10">
            <div class="form-check">
                <input type="checkbox" name="hidden" id="hidden" class="form-check-input" <?php echo $item['hidden'] ? 'checked="checked"' : ''; ?>>
                <label for="hidden" class="form-check-label">
                    <strong class="red">Oculto</strong>: no es accesible.
                </label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="unindexed" id="unindexed" class="form-check-input" <?php echo $item['unindexed'] ? 'checked="checked"' : ''; ?>>
                <label for="unindexed" class="form-check-label">
                    <strong class="yellow">No indexado</strong>: no se muestra en la vista, pero es accesible mediante enlaces directos.
                </label>
            </div>
        </div>
    </div>
<?php }?>
    <input type="submit" class="btn btn-primary" value="<?php echo $title; ?>">
</form>
<?php if ($editItem) {?>
<div class="row g-4 py-5 row-cols-1 row-cols-lg-3">
    <div class="col d-flex align-items-start">
        <img class="bi text-muted flex-shrink-0 me-3" src="tpl/img/feather/hash.svg" alt="" style="width: 2em;">
        <div>
            <h4><code>[Texto](#/identificador)</code></h4>
            <p>Puede usar el identificador para crear enlaces relativos.</p>
        </div>
    </div>
</div>
<?php }
include DIR_TPL . 'footer.tpl.php';?>