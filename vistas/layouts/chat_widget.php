<?php
// Widget de chat reutilizable - rutas de assets calculadas para ser compatibles
// cuando se incluye desde diferentes niveles (`header.php`, `menu.php`, vistas)
$baseUrl = preg_replace('#/vistas/.*$#', '', $_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/') $baseUrl = '';
$assetsUrl = $baseUrl . '/assets';
?>
<!-- Chat Button -->
<link rel="stylesheet" href="<?php echo $assetsUrl; ?>/css/chat_widget.css">
<div id="ft-chat-root">
  <div id="ft-chat-btn" class="ft-chat-btn" title="Fermin Bot">
    <!-- Robot icon using Boxicons class -->
    <i class='bx bxs-bot' style="color:#c90000;font-size:35px;"></i>
  </div>

  <div id="ft-chat-panel" class="ft-chat-panel">
    <div class="ft-chat-header">
      <div class="title">
        <!-- Mostrar logo Fermín en el header -->
        <img src="<?php echo $assetsUrl; ?>/images/fermin.png" alt="Fermin" style="width:32px;height:32px;border-radius:4px;object-fit:contain;background:transparent;" />
        Fermin Bot
      </div>
      <button id="ft-chat-close" class="ft-chat-close">✕</button>
    </div>
    <div class="ft-chat-messages" aria-live="polite"></div>
    <form id="ft-chat-form" class="ft-chat-input">
      <textarea id="ft-chat-input" placeholder="Escribe un mensaje..." aria-label="Mensaje"></textarea>
      <button type="submit" class="ft-send-btn" title="Enviar">
        <!-- Icono avion de papel -->
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M2 21L23 12L2 3L7 12L2 21Z" fill="currentColor" />
        </svg>
      </button>
    </form>
  </div>
</div>
<?php
// Preparar datos de usuario para JS
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$ft_user = [
  'has_session' => false,
  'idPersona' => null,
  'nombre' => null,
  'perfiles' => []
];
if (!empty($_SESSION['idPersona'])){
  $ft_user['has_session'] = true;
  $ft_user['idPersona'] = $_SESSION['idPersona'];
  // nombre completo puede estar en session
  if (!empty($_SESSION['nombre_completo'])) {
    $ft_user['nombre'] = $_SESSION['nombre_completo'];
  } else {
    $ft_user['nombre'] = trim((!empty($_SESSION['nombre'])?$_SESSION['nombre']:'') . ' ' . (!empty($_SESSION['apellido'])?$_SESSION['apellido']:''));
  }
  // intentar obtener todos los perfiles desde la BD si no están en session
  if (!empty($_SESSION['perfiles']) && is_array($_SESSION['perfiles'])){
    $ft_user['perfiles'] = $_SESSION['perfiles'];
  } else {
    // consultar detalle_perfil para obtener nombres de perfil
    try{
      require_once __DIR__ . '/../../config/conexion.php';
      $db = new Database();
      $conn = $db->getConnection();
      $sql = "SELECT pr.nombre_perfil FROM detalle_perfil dp INNER JOIN perfil pr ON dp.IdPerfil = pr.IdPerfil WHERE dp.IdPersona = :idPersona";
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':idPersona', $_SESSION['idPersona'], PDO::PARAM_INT);
      $stmt->execute();
      $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
      if ($rows) $ft_user['perfiles'] = array_values($rows);
    }catch(Exception $e){
      // ignore DB errors here
    }
  }
}
?>
<script>
  window.FT_CHAT_USER = <?php echo json_encode($ft_user, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="<?php echo $assetsUrl; ?>/js/chat_widget.js"></script>
