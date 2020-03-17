<?php include DIR_TPL.'header.tpl.php';
$title = 'Eliminar ' . ($isDir?'Carpeta':($isNote?'Apunte':'Cuaderno'));
?>

	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1><?php echo $title; ?></h1>
    </div>

    <p class="alert alert-warning" role="alert">
        Está a punto de eliminar <?php if($isDir) { echo 'una carpeta y todo lo que contiene'; } elseif($isNote) { echo 'un apunte'; } else { echo 'el cuaderno actual y todo lo que contiene'; } ?>.
        ¡No hay vuelta atrás!
    </p>
    <form method="post" action="?nb=<?php echo $notebookName; ?>&amp;action=delete<?php echo isset($_GET['item'])?'&amp;item='.$_GET['item']:''; ?>">
        <input id="delete" class="btn btn-lg btn-danger" name="delete" type="submit" value="<?php echo $title; ?>">
    </form>
<?php include DIR_TPL.'footer.tpl.php'; ?>