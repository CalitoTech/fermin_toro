<?php
// Widget de chat reutilizable - insertar donde se incluya el menú
?>
<!-- Chat Button -->
<link rel="stylesheet" href="../../../assets/css/chat_widget.css">
<div id="ft-chat-root">
  <div id="ft-chat-btn" class="ft-chat-btn" title="Fermin Bot">
    <!-- Robot icon using Boxicons class -->
    <i class="bx bx-bot" style="color:#fff;font-size:22px;"></i>
  </div>

  <div id="ft-chat-panel" class="ft-chat-panel">
    <div class="ft-chat-header">
      <div class="title">
        <!-- Mostrar logo Fermín en el header -->
        <img src="../../../assets/images/fermin.png" alt="Fermin" style="width:32px;height:32px;border-radius:4px;object-fit:contain;background:transparent;" />
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
<script src="../../../assets/js/chat_widget.js"></script>
