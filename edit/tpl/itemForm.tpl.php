<?php
include DIR_TPL . 'header.tpl.php';
$editItem = $_GET['action'] == 'edit';
if (empty($isDir) and empty($isNote)) {
	$isDir = $_GET['action'] == 'adddir';
}
if (isset($_GET['item'])) {
	$file = new SplFileInfo($_GET['item']);
	$fileName = $file->getBasename();
	$fileName = str_replace("." . $file->getExtension(), "", $fileName);
}
$title = $editItem ? ($isDir ? 'Editar Carpeta' : 'Editar Apunte') : ($isDir ? 'Nueva Carpeta' : 'Nuevo Apunte');
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1><?php echo $title; ?></h1>
</div>
<form method="post" action="?nb=<?php echo $notebookName; ?>&amp;action=<?php echo $_GET['action']; ?>&amp;item=<?php echo isset($_GET['item']) ? $_GET['item'] : ''; ?>">
    <div class="row g-3 mb-3 align-items-center">
        <div class="col-auto">
            <label for="name" class="col-form-label">Nombre</label>
        </div>
        <div class="col-auto">
            <input type="text" id="name" name="name" class="form-control" aria-describedby="passwordHelpInline" value="<?php echo $editItem ? $fileName : ''; ?>" autofocus="autofocus">
        </div>
        <div class="col-auto">
            <input type="submit" class="btn btn-primary" value="<?php echo $title; ?>">
        </div>
    </div>
    <?php if (isset($errors['empty']) && $errors['empty']) {?>
    <div class="alert alert-warning">Por favor, ingrese un nombre.</div>
    <?php } elseif (isset($errors['sameName']) && $errors['sameName']) {?>
    <div class="alert alert-warning">Ya tiene ese nombre.</div>
    <?php } elseif (isset($errors['alreadyExists']) && $errors['alreadyExists']) {?>
    <div class="alert alert-warning">Ya existe un elemento con este nombre en esta carpeta. Por favor, introduzca otro.</div>
    <?php }?>
</form>
<?php if (!$isDir) {?>
<div class="row g-4 py-5 row-cols-1 row-cols-lg-3">
    <div class="col d-flex align-items-start">
        <img class="bi text-muted flex-shrink-0 me-3" src="tpl/img/feather/sidebar.svg" alt="" style="width: 2em;">
        <div>
            <h4 class="fw-bold">Capítulo</h4>
            <p>Puede definir un <mark>capítulo</mark>, usando dígitos o letras terminados con un punto <code>.</code>.<br/>El Visor creará un esquema de documentos basado en los números de capitulo.</p>
        </div>
    </div>
    <div class="col d-flex align-items-start">
        <img class="bi text-muted flex-shrink-0 me-3" src="tpl/img/feather/hash.svg" alt="" style="width: 2em;">
        <div>
            <h4 class="fw-bold">Identificador</h4>
            <p>Puede agregar un <mark>identificador</mark>, encerrado una cadena por un par de corchetes <code>[</code> y <code>]</code>.<br/><small>Internamente puede usar el identificador para crear un enlace relativo <code>[Enlace](#/identificador)</code>.</small></p>
        </div>
    </div>
</div>
<?php } else {?>
<div class="row g-4 py-5 row-cols-1 row-cols-lg-3">
    <div class="col d-flex align-items-start">
      <img class="bi text-muted flex-shrink-0 me-3" src="tpl/img/dropbox/folder-large.svg" alt="" style="width: 3em;">
      <div>
        <h4 class="fw-bold">Carpetas</h4>
        <p>Las carpetas y el contenido dentro de ellas <mark>no se mostraran</mark> en el Visor de documentos.</p>
      </div>
    </div>
  </div>
<?php }
include DIR_TPL . 'footer.tpl.php';?>