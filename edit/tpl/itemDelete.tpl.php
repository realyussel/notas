<?php include DIR_TPL . 'header.tpl.php';
    $title   = 'Eliminar ' . ($isDir ? 'Carpeta' : ($isNote ? 'Apunte' : 'Cuaderno'));
    $warning = ($isDir or $isNote);
?>
<div class="<?php echo $warning ? 'bg-light' : 'bg-danger text-white'; ?> p-5 rounded">
    <h1><?php echo $title; ?></h1>
    <p class="lead">Estás a punto de eliminar                                                                                                                                           <?php if ($isDir) {echo 'una carpeta y todo lo que contiene';} elseif ($isNote) {echo 'un apunte';} else {echo 'el cuaderno actual y todo lo que contiene';}?>.<br><strong class="fw-bold">¡No hay vuelta atrás!</strong></p>

    <?php if (isset($errors['noSuch']) && $errors['noSuch']) {?>
    <div class="alert alert-danger">
        <?php echo $isDir ? 'La carpeta' : 'El archivo'; ?> no existe o no es accesible.
    </div>
    <?php }?>

    <form method="post" action="?nb=<?php echo $notebookName; ?>&amp;action=delete<?php echo isset($_GET['item']) ? '&amp;item=' . $_GET['item'] : ''; ?>">
        <input id="delete" class="advert_nav btn btn-lg                                                                                                                                                                      <?php echo $warning ? 'btn-danger' : 'advert-light'; ?>" name="delete" type="submit" value="<?php echo $title; ?>">
    </form>
</div>
<?php include DIR_TPL . 'footer.tpl.php'; ?>