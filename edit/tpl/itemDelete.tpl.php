<?php include DIR_TPL . 'header.tpl.php';
$title = 'Eliminar ' . ($isDir ? 'Carpeta' : ($isNote ? 'Apunte' : 'Cuaderno'));
?>

    <div class="bg-light p-5 rounded">
    <h1><?php echo $title; ?></h1>
    <p class="lead">Está a punto de eliminar <?php if ($isDir) {echo 'una carpeta y todo lo que contiene';} elseif ($isNote) {echo 'un apunte';} else {echo 'el cuaderno actual y todo lo que contiene';}?>.
        ¡No hay vuelta atrás!</p>
    <form method="post" action="?nb=<?php echo $notebookName; ?>&amp;action=delete<?php echo isset($_GET['item']) ? '&amp;item=' . $_GET['item'] : ''; ?>">
        <input id="delete" class="btn btn-lg btn-danger" name="delete" type="submit" value="<?php echo $title; ?>">
    </form>
    </div>

<?php include DIR_TPL . 'footer.tpl.php';?>