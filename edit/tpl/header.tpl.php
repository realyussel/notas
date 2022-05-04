<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sólo una app para tomar notas o apuntes de forma fácil y rápida.</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

    <link rel="stylesheet" href="<?php echo URL_TPL; ?>style.css">

    <link rel="icon" type="image/png" href="<?php echo URL_TPL; ?>img/yotter-icon-16.png"/>

    <script src="<?php echo URL_TPL; ?>js/main.js"></script>

<?php if ($isNote && $isEditMode) {?>
    <script src="<?php echo URL_TPL; ?>js/editor.js"></script>
    <?php if ($isWysiwyg) {?>
        <script src="<?php echo URL_TPL; ?>js/ext/jquery.hotkeys.js"></script>
        <script src="<?php echo URL_TPL; ?>js/ext/bootstrap.min.js"></script>
        <script src="<?php echo URL_TPL; ?>js/ext/bootstrap-wysiwyg.js"></script>
        <script src="<?php echo URL_TPL; ?>js/editor-wysiwyg.js"></script>
    <?php } //isWysiwyg ?>
    <script>
    window.addEventListener('load', function (){
        //instanciate editor tools
        var editor = new <?php echo $isWysiwyg ? 'WysiwygEditor' : 'BaseEditor'; ?>();
        editor.init();
    });
    </script>
<?php } else {?>

<?php }?>

</head>
<body>
<header class="navbar navbar-expand-md bg-dark text-white py-3 border-bottom">
  <nav class="container-xxl flex-wrap flex-md-nowrap" aria-label="Main navigation">
    <a class="d-flex align-items-center mb-2 mb-lg-0 text-white text-decoration-none" aria-label="Bootstrap" href=".." >
      <img src="../view/dist/y-dark.svg" alt="" width="40" height="40" class="me-2">
    </a>
    <!--?php echo VERSION; ?-->
    <!-- a.active -->
    <div class="collapse navbar-collapse" id="bdNavbar">
      <ul class="di nav me-auto">
        <li>
          <a class="nav-link dim px-2" href="<?php echo URL; ?>" title="Cuadernos"><img src="<?php echo URL_TPL; ?>img/feather/feather.svg" alt="Cuadernos"></a>
        </li>
        <li>
          <a class="nav-link dim px-2" aria-current="true" href="<?php echo URL; ?>?action=search" title="Buscador"><img src="<?php echo URL_TPL; ?>img/feather/search.svg" alt="Buscador"></a>
        </li>
        <li>
          <a class="nav-link dim px-2" href="<?php echo URL; ?>?action=config" title="Configuración"><img src="<?php echo URL_TPL; ?>img/feather/settings.svg" alt="Configuración"></a>
        </li>
        <?php if ($user['isLoggedIn']) {?>
        <li>
            <a class="nav-link dim px-2" href="<?php echo URL; ?>?action=logout" title="Cerrar sesión" target="_blank" rel="noopener"><img src="<?php echo URL_TPL; ?>img/feather/log-out.svg" alt="Cerrar sesión"></a>
        </li>
        <?php }?>
      </ul>
      <a class="btn btn-outline-light text-end" href="..">Visor</a>
      <a class="btn ms-3 btn-outline-light text-end" href="<?php echo URL; ?>/filemanager">Archivos</a>

      <!--a class="ps-3 dim" -->
    </div>
  </nav>
</header>
    <?php if ($isNote && $isEditMode) {?>
    <nav id="toolbar" class="bd-subnavbar py-2">
        <div class="toolbar container-xxl d-flex align-items-md-center" id="item-toolbar" data-role="editor-toolbar" data-target="#editor">
            <ul class="nav navbar-nav code-types actions position-relative me-auto">
                <li>
                    <a href="#" id="save-button" class="disabled btn btn-secondary" title="Guarda este apunte">
                        <img src="<?php echo URL_TPL; ?>img/guardado.svg" alt="Guardar apunte">
                    </a>
                </li>
    <?php if ($isWysiwyg) {?>
                <li class="nav-item">
                    <a href="#" id="headingDropDown" class="ajax-formatter btn btn-light" data-toggle="dropdown" title="Título">
                        <img src="<?php echo URL_TPL; ?>img/feather/type.svg" alt="Título">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-light" data-edit="bold" title="Negrita (Ctrl+B)">
                        <img src="<?php echo URL_TPL; ?>img/feather/bold.svg" alt="Negrita">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-light" data-edit="italic" title="Itálica (Ctrl+I)">
                        <img src="<?php echo URL_TPL; ?>img/feather/italic.svg" alt="Itálica">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-light" data-edit="insertunorderedlist" title="Lista desordenada">
                        <img src="<?php echo URL_TPL; ?>img/feather/list.svg" alt="Lista desordenada">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-light" data-edit="insertorderedlist" title="Ordered list">
                        <img src="<?php echo URL_TPL; ?>img/edit-list-order.png" alt="Ordered List">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" id="linkDropdown" class="ajax-formatter btn btn-light" data-toggle="dropdown" title="Enlace">
                        <img src="<?php echo URL_TPL; ?>img/feather/link.svg" alt="Enlace">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-light" data-edit="unlink" title="Quitar enlace">
                        <img src="<?php echo URL_TPL; ?>img/feather/slash.svg" alt="Quitar enlace">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-light" id="picture-button" title="Insertar imagen (o arrastre y suelte en su texto)">
                        <img src="<?php echo URL_TPL; ?>img/feather/image.svg" alt="Insertar imagen">
                    </a>
                    <input type="file" id="hidden-picture-button" data-target="#picture-button" data-edit="insertImage" />
                </li>
                <li class="nav-item">
                    <a href="#" id="mdash-button" class="btn btn-light dim" title="Insert em dash">
                        &mdash;
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" id="source-button" class="btn btn-light dim" title="Ver código">
                        <img src="<?php echo URL_TPL; ?>img/feather/code.svg" alt="Ver código">
                    </a>
                </li>
    <?php } else { // End isWysiwyg?>
                    <li class="nav-item">
                        <span class="btn btn-light dim add-btn" data-type="bold" title="Negrita">
                            <img src="<?php echo URL_TPL; ?>img/feather/bold.svg" alt="Negrita">
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="btn btn-light dim add-btn" data-type="italic" title="Cursiva">
                            <img src="<?php echo URL_TPL; ?>img/feather/italic.svg" alt="Cursiva">
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="btn btn-light dim add-btn" data-type="strike" title="Tachado">
                            <img src="<?php echo URL_TPL; ?>img/editor/strikethrough.svg" alt="Tachado">
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="btn btn-light dim" id="modal-header" title="Título"></a>
                        <ul class="modal-menu bg-white rounded shadow-sm" id="menu-header">

                            <li class="nav-item">
                                <span class="btn btn-light dim add-start-btn" data-type="h2" title="Título nivel 2">
                                    <img src="<?php echo URL_TPL; ?>img/editor/t1.svg" alt="Título nivel 2">
                                </span>
                            </li>
                            <li class="nav-item">
                                <span class="btn btn-light dim add-start-btn" data-type="h3" title="Título nivel 3">
                                    <img src="<?php echo URL_TPL; ?>img/editor/t2.svg" alt="Título nivel 3">
                                </span>
                            </li>
                            <li class="nav-item">
                                <span class="btn btn-light dim add-start-btn" data-type="h4" title="Título nivel 4">
                                    <img src="<?php echo URL_TPL; ?>img/editor/t3.svg" alt="Título nivel 4">
                                </span>
                            </li>
                            <li class="nav-item">
                                <span class="btn btn-light dim add-start-btn" data-type="h5" title="Título nivel 5">
                                    <img src="<?php echo URL_TPL; ?>img/editor/t4.svg" alt="Título nivel 5">
                                </span>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <span class="btn btn-light dim add-start-btn" data-type="quote" title="Cita">
                            <img src="<?php echo URL_TPL; ?>img/feather/terminal.svg" alt="Cita">
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="btn btn-light dim add-btn" data-type="code" title="Código de cita">
                            <img src="<?php echo URL_TPL; ?>img/feather/code.svg" alt="Código de cita">
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="btn btn-light dim add-btn" data-type="code-block" title="Código de bloque">
                            <img src="<?php echo URL_TPL; ?>img/code-block.svg" alt="Código de bloque">
                        </span>
                    </li>

                <li class="nav-item">
                    <a href="#" id="preview-button" class="btn btn-outline-secondary" title="Vista previa del apunte">
                        <img src="<?php echo URL_TPL; ?>img/markdown.svg" alt="Vista previa">
                    </a>
                </li>

                <li class="nav-item">
                    <a href="https://help.github.com/es/github/writing-on-github/basic-writing-and-formatting-syntax" target="blank" id="markdown-button" class="btn btn-link dim" title="Mostrar ayuda de sintaxis de Markdown">
                        <img src="<?php echo URL_TPL; ?>img/feather/help-circle.svg" alt="Markdown">
                    </a>
                </li>
    <?php } // not isWysiwyg ?>
            </ul>
    <?php if ($isNote && $isEditMode && $isWysiwyg) {?>
            <div class="" id="insertLink" style="display: none;">
                <input placeholder="http://" type="text" data-edit="createLink"/>
                <button type="button">Add</button>
            </div>

            <ul class="actions" id="headingButtons" style="display: none;">
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-secondary" data-edit="formatBlock h1" title="Title level 1">
                        <img src="<?php echo URL_TPL; ?>img/edit-heading-1.png" alt="Level 1">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-secondary" data-edit="formatBlock h2" title="Title level 2">
                        <img src="<?php echo URL_TPL; ?>img/edit-heading-2.png" alt="Level 2">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-secondary" data-edit="formatBlock h3" title="Title level 3">
                        <img src="<?php echo URL_TPL; ?>img/edit-heading-3.png" alt="Level 3">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-secondary" data-edit="formatBlock h4" title="Title level 4">
                        <img src="<?php echo URL_TPL; ?>img/edit-heading-4.png" alt="Level 4">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-secondary" data-edit="formatBlock h5" title="Title level 5">
                        <img src="<?php echo URL_TPL; ?>img/edit-heading-5.png" alt="Level 5">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-secondary" data-edit="formatBlock h6" title="Title level 6">
                        <img src="<?php echo URL_TPL; ?>img/edit-heading-6.png" alt="Level 6">
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="ajax-formatter btn btn-secondary" data-edit="formatBlock p" title="Turn title into a paragraph">
                        <img src="<?php echo URL_TPL; ?>img/edit-heading-minus.png" alt="Paragraph">
                    </a>
                </li>
            </ul>
        <?php } // $isNote ?>
        </div>
    </nav>
    <?php } // $isNote & isEditMode ?>

<div class="container-xxl my-md-4 bd-layout">
  <div class="bd-sidebar">
    <nav class="collapse bd-links">
      <div id="panel" class="sidebar-sticky">
        <form action="">
            <select name="nb" id="notebookSelect" class="form-control w-100">
                <option value="!nothing!" default selected>&raquo; Seleccione un cuaderno</option>
                <optgroup label="Cargar un cuaderno">
<?php if (!empty($notebooks[$user['login']])) {
	foreach ($notebooks[$user['login']] as $key => $value) {?>
        <option value="<?php echo $key; ?>"><?php echo urldecode($key); ?></option>
<?php }}?>
                </optgroup>
                <option value="!new!">&raquo; Crea un nuevo cuaderno</option>
            </select>
        </form>

<?php if (isset($notebook['tree'])) {
	?>
<div class="nb-header">
    <h3 <?php if (empty($_GET['item'])) {echo ' id="selected"';}?> data-path="">
    <a class="item nav-link link-dark" id="notebookTitle" href="?nb=<?php echo $notebookName; ?>" data-name="<?php echo $notebookName; ?>"><?php echo urldecode($notebookName); ?></a>
    </h3>
    <ul class="nav my-2 justify-content-center">
        <li class="nav-item"><a class="nav-link link-dark hover-light" href="<?php echo URL; ?>?nb=<?php echo $notebookName; ?>&amp;action=edit" title="Editar cuaderno">
            <img class="icon" src="<?php echo URL_TPL; ?>img/feather/edit-3.svg" alt="">
        </a></li>
        <li class="nav-item"><a class="nav-link link-dark hover-light" href="<?php echo URL; ?>?nb=<?php echo $notebookName; ?>&amp;item=<?php echo $itemPath; ?>&amp;action=addnote">
            <img class="icon" src="tpl/img/feather/file-plus.svg" alt="Nuevo apunte">
        </a></li>
        <li class="nav-item"><a class="nav-link link-dark hover-light" href="<?php echo URL; ?>?nb=<?php echo $notebookName; ?>&amp;item=<?php echo $itemPath; ?>&amp;action=adddir">
            <img class="icon" src="tpl/img/feather/folder-plus.svg" alt="Nueva carpeta">
        </a></li>
        <li class="nav-item"><a class="nav-link link-dark hover-light" target="_blank" href="../view/index.php?nb=<?php echo $notebookName; ?>&amp;usr=<?php echo $user['login']; ?>" title="Ver cuaderno">
            <img class="icon" src="<?php echo URL_TPL; ?>img/feather/eye.svg" alt="">
        </a></li>
        <li class="nav-item"><a class="nav-link link-danger hover-light" href="<?php echo URL; ?>?nb=<?php echo $notebookName; ?>&amp;action=delete" title="Eliminar cuaderno">
            <img class="icon" src="<?php echo URL_TPL; ?>img/feather/trash-2.svg" alt="">
        </a></li>
    </ul>
</div>
<?php
function Tree2Html($tree, $nbName, $selectedPath, $parents = array()) {
		$level = count($parents);
		$html = str_repeat("\t", $level * 2) . "<ul";
		if ($level == 0) {
			$html .= ' id="root" class="subtree open"';
		} else {
			$html .= ' class="subtree open"';
		}
		$html .= ">\r\n";

		foreach ($tree as $key => $value) {
			$isArray = is_array($value);
			$isNote = substr($key, -3) == '.md';
			if ($isArray || $isNote) {
				//path to element
				$path = (!empty($parents) ? implode('/', $parents) . '/' : '') . $key;

				$html .= str_repeat("\t", $level * 2 + 1)
					. '<li class="' . ($isArray ? "directory" : "file") . '"'
					. ($path == $selectedPath ? ' id="selected"' : '')
					. ' data-path="' . $path . '">';

				//if array, show open/close button
				if ($isArray) {
					$html .= '<a class="arrow open" href="#"></a>';
				}
				$html .= "\r\n" . str_repeat("\t", $level * 2 + 2);
				$html .= '<div class="item-menu">';
				$html .= '<img class="dropdown-arrow" src="' . URL_TPL . 'img/dropbox/overflow.svg" alt="...">';
				$html .= '<div class="dropdown dropdown-menu shadow closed">';
				$html .= '<a class="dropdown-item nav-link link-dark d-flex gap-2 align-items-center" href="' . URL . '?nb=' . $nbName . '&amp;item=' . $path . '&amp;action=edit" title="Editar &quot;' . $path . '&quot;">';
				$html .= '<img class="icon" src="' . URL_TPL . 'img/feather/edit-3.svg" alt=""> Editar</a>';
				$html .= '<a class="dropdown-item nav-link link-danger d-flex gap-2 align-items-center" href="' . URL . '?nb=' . $nbName . '&amp;item=' . $path . '&amp;action=delete" title="Eliminar &quot;' . $path . '&quot;">';
				$html .= '<img class="icon" src="' . URL_TPL . 'img/feather/' . ($isArray ? "folder" : "file") . '-minus.svg" alt=""> Eliminar</a>';
				if ($isArray) {
					$html .= '<a class="dropdown-item nav-link link-dark d-flex gap-2 align-items-center" href="' . URL . '?nb=' . $nbName . '&amp;item=' . $path . '&amp;action=addnote" title="Nuevo apunte aquí">';
					$html .= '<img class="icon" src="' . URL_TPL . 'img/feather/file-plus.svg" alt=""> Nuevo apunte aquí</a>';
					$html .= '<a class="dropdown-item nav-link link-dark d-flex gap-2 align-items-center" href="' . URL . '?nb=' . $nbName . '&amp;item=' . $path . '&amp;action=adddir" title="Nueva carpeta aquí">';
					$html .= '<img class="icon" src="' . URL_TPL . 'img/feather/folder-plus.svg" alt=""> Nueva carpeta aquí</a>';
				}
				$html .= '</div>';
				$html .= '</div>';

				$html .= "\r\n" . str_repeat("\t", $level * 2 + 2);

				// item
				$html .= '<a draggable="true" class="item nav-link link-dark" href="' . URL . '?nb=' . $nbName . '&amp;item=' . $path . '">';
				$html .= basename($key, '.md');
				$html .= '</a>';

				//if array, show its children
				if ($isArray) {
					$html .= "\r\n";
					$html .= Tree2Html($value, $nbName, $selectedPath, array_merge($parents, (array) $key));
				}

				$html .= "\r\n" . str_repeat("\t", $level * 2 + 1);
				$html .= "</li>\r\n";
			}
		}

		$html .= str_repeat("\t", $level * 2) . "</ul>\r\n";
		return $html;
	}

	echo Tree2Html($notebook['tree'], $notebookName, isset($_GET['item']) ? $_GET['item'] : '');?>

<?php } // notebook tree ?>
<?php if ($isConfigMode) {
	?>
    <div>
        <ul class="nav flex-column">

            <li class="nav-item"><a class="nav-link" href="<?php echo URL; ?>?action=config&amp;option=myPassword">
                <img class="me-2" src="<?php echo URL_TPL; ?>img/feather/key.svg" alt="">Cambiar mi contraseña</a>
            </li>

            <li class="nav-item"><a class="nav-link" href="<?php echo URL; ?>?action=config&amp;option=addUser">
                <img class="me-2" src="<?php echo URL_TPL; ?>img/feather/user-plus.svg" alt="">Agregar un usuario</a>
            </li>

        </ul>

<?php if (count($users) > 1 && $user['login'] == 'realyussel') {
		?>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
          <span>Eliminar usuarios</span>
        </h6>

        <ul class="nav flex-column">

    <?php foreach ($users as $value) {
			if ($value['login'] != $user['login']) {?>
                <li class="nav-item"><a class="nav-link" href="<?php echo URL; ?>?action=config&amp;option=deleteUser&amp;user=<?php echo $value['login']; ?>">
                    <img class="me-2" src="<?php echo URL_TPL; ?>img/feather/user-minus.svg" alt="">
                    <?php echo $value['login']; ?></a>
                </li>
        <?php } // login = current user
		} //foreach?>

</ul>
</div>
<?php } // count($users) > 1 ?>



<?php } // isConfigMode ?>

</div>
        </div>
    </nav>
    <div id="app" role="main" class="order-1"> <!-- class=bd-main : para 3 columnas -->
        <div class="chartjs-size-monitor" style="position: absolute; left: 0px; top: 0px; right: 0px; bottom: 0px; overflow: hidden; pointer-events: none; visibility: hidden; z-index: -1;">
            <div class="chartjs-size-monitor-expand" style="position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;">
                <div style="position:absolute;width:1000000px;height:1000000px;left:0;top:0">

                </div>
            </div>
            <div class="chartjs-size-monitor-shrink" style="position:absolute;left:0;top:0;right:0;bottom:0;overflow:hidden;pointer-events:none;visibility:hidden;z-index:-1;">
                <div style="position:absolute;width:200%;height:200%;left:0; top:0">

                </div>
            </div>
        </div>
        <section id="content">