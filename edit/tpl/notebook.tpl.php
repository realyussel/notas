<?php include DIR_TPL.'header.tpl.php'; ?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1><?php echo urldecode($notebookName); ?></h1>
    </div>

<div class="statblock">
	<div class="statblock__icon">
		<img src="tpl/img/dropbox/folder-large.svg" alt="">
	</div>
	<p>Las carpetas de un cuaderno y el contenido dentro de ellas <span class="stat">no se mostraran</span> en el Visor de documentos.</p>
</div>

<?php include DIR_TPL.'footer.tpl.php'; ?>