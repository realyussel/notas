<?php include DIR_TPL . 'header.tpl.php';
use Mexitek\PHPColors\Color;

$color = new Color(isset($notebook['color']) ? $notebook['color'] : '#1071f2');
$gradient = $color->makeGradient();
$text_color = $color->isDark() ? "#FFF" : "#000";
?>
<div id="response"></div>
<section class="MuiPaper" style="color: <?php echo $text_color; ?>;background:url('<?php echo URL_TPL; ?>img/muipaper.svg') no-repeat center center, linear-gradient(268.9deg, <?php echo "#" . $gradient["dark"]; ?> 0%, <?php echo "#" . $gradient["light"]; ?> 99.86%);background-size: cover;">
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
    <img src="<?php echo URL_TPL; ?>img/link.svg" alt="Ver cuaderno">
  </a>
</section>
<?php include DIR_TPL . 'footer.tpl.php';?>