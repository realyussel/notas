<?php include DIR_TPL . 'header.tpl.php';?>

	<div class="card">
		<?php if ($isNote || $isDir) {?>
            <h5 class="card-header"><?php echo $_GET['item']; ?></h5>
		<?php }?>
		<div class="card-body">
			<?php if ($isWysiwyg) {?>
			    <article id="editor"><?php echo $note; ?></article>
			<?php } else {?>
				<textarea autocapitalize="off" autocomplete="off" autocorrect="off" id="editor" spellcheck="false" v-model="mdRaw"><?php echo $note; ?></textarea>
			<?php }?>
		</div>
	</div>

<?php include DIR_TPL . 'footer.tpl.php';?>