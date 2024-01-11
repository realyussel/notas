<?php include DIR_TPL . 'header.tpl.php';?>
<section class="MuiPaper">
  <div class="Mui-L">
    <h2><?php echo urldecode($notebookName); ?></h2>
    <p><?php echo $notebook['site_description']; ?></p>
    <ul class="d-flex list-unstyled mt-auto">
      <li class="d-flex align-items-center me-3">
        <img class="icon" src="tpl/img/feather/user.svg" alt="User">&nbsp;
        <small><?php echo urldecode($notebook['user']); ?></small>
      </li>
    </ul>
  </div>
  <a class="Mui-R" target="_blank" href="../view/index.php?nb=<?php echo $notebookName; ?>&amp;usr=<?php echo $user['login']; ?>" title="Ver cuaderno">
    <img src="<?php echo URL_TPL; ?>img/viewer-lap.svg" alt="Ver cuaderno">
  </a>
</section>
<?php include DIR_TPL . 'footer.tpl.php';?>