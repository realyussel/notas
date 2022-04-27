<?php include DIR_TPL . 'header.tpl.php';?>
<div class="row row-cols-1 row-cols-lg-3 align-items-stretch g-4">
  <div class="col">
    <div class="card card-cover h-100 overflow-hidden text-white rounded-5 shadow-lg book-wrapper">
      <div class="d-flex flex-column h-100 p-5 pb-3 text-white text-shadow-1">
        <h2 class="pt-5 mt-5 mb-4 display-6 lh-1 fw-bold"><?php echo urldecode($notebookName); ?></h2>
        <ul class="d-flex list-unstyled mt-auto">
          <li class="d-flex align-items-center me-3">
            <img class="icon" src="tpl/img/feather/user.svg" alt="User">&nbsp;
            <small><?php echo urldecode($notebook['user']); ?></small>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col">
    <ul class="nav nav-pills flex-column mb-auto">
      <li class="nav-item">
        <a class="nav-link link-dark hover-light" href="<?php echo URL; ?>?nb=<?php echo $notebookName; ?>&amp;action=edit" title="Editar cuaderno">
                    <img class="icon" src="<?php echo URL_TPL; ?>img/feather/edit-3.svg" alt=""> Editar
                </a>
      </li>
      <li>
        <a class="nav-link link-dark hover-light" target="_blank" href="../view/index.php?nb=<?php echo $notebookName; ?>&amp;usr=<?php echo $user['login']; ?>" title="Ver cuaderno">
                    <img class="icon" src="<?php echo URL_TPL; ?>img/feather/eye.svg" alt=""> Ver
                </a>
      </li>
      <li>
        <a class="nav-link link-danger hover-light" href="<?php echo URL; ?>?nb=<?php echo $notebookName; ?>&amp;action=delete" title="Eliminar cuaderno">
                    <img class="icon" src="<?php echo URL_TPL; ?>img/feather/trash-2.svg" alt=""> Eliminar
                </a>
      </li>
    </ul>
  </div>
  <div class="col">
    <p class="lead mb-4"><?php echo $notebook['site_description']; ?></p>
  </div>
</div>
<?php include DIR_TPL . 'footer.tpl.php';?>