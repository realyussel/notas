<?php include DIR_TPL.'header.tpl.php'; ?>
    
    <!--h2><img src="<?php echo URL_TPL; ?>img/jotter.png" alt="Jotter"></h2-->
    
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Cuadernos</h1>
    </div>

    <div class="list-inline fl1 fs-body1">
<?php
if(!empty($notebooks[$user['login']])) {
    foreach($notebooks[$user['login']] as $name => $notebook) {
?>

        <a class="list-inline-item post-tag" href="<?php echo URL.'?nb='.$name; ?>"><?php echo urldecode($name); ?></a>
<?php } } ?>

        
    </div>
    <a class="btn btn-secondary my-2" href="?action=add">Comience un nuevo cuaderno</a>
<?php include DIR_TPL.'footer.tpl.php'; ?>