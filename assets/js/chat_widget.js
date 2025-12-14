// Chat widget behavior (reutilizable)
(function(){
  const STORAGE_KEY = 'ft_chat_messages_v1';

  function $(sel, ctx){ return (ctx||document).querySelector(sel); }
  function $all(sel, ctx){ return Array.from((ctx||document).querySelectorAll(sel)); }

  function defaultConversation(){
    return [
      {from:'bot', text: 'Hola, soy Fermin Bot. ¿En qué puedo ayudarte hoy?', time: Date.now()-600000},
      {from:'me', text: 'Hola! ¿Pueden explicarme el proceso de inscripción?', time: Date.now()-590000},
      {from:'bot', text: 'Claro. Primero debes completar la solicitud online y luego presentarte con los documentos.', time: Date.now()-580000}
    ];
  }

  function loadMessages(){
    try{
      const raw = localStorage.getItem(STORAGE_KEY);
      if(!raw) return defaultConversation();
      return JSON.parse(raw);
    }catch(e){ console.error(e); return defaultConversation(); }
  }

  function saveMessages(msgs){ localStorage.setItem(STORAGE_KEY, JSON.stringify(msgs)); }

  function renderMessages(){
    const msgs = loadMessages();
    const container = $('.ft-chat-messages');
    if(!container) return;
    container.innerHTML = '';
      msgs.forEach(m => {
        // messages from user ('me') should appear on the right; bot messages on the left
        const classForRender = (m.from === 'me') ? 'me' : 'bot';
        const el = document.createElement('div');
        el.className = 'ft-msg ' + classForRender;

        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.textContent = m.text;

        // Animar SOLO los mensajes entrantes del bot (recientes)
        if(m.from === 'bot' && (Date.now() - (m.time || 0) < 3500)){
          bubble.classList.add('ft-incoming');
        }

        el.appendChild(bubble);
        container.appendChild(el);
      });
    container.scrollTop = container.scrollHeight;
  }

  function openPanel(){
    const p = $('.ft-chat-panel');
    const btn = $('#ft-chat-btn');
    if(!p) return;
    p.classList.add('open');
    if(btn) btn.classList.add('hidden'); // ocultar botón para que el panel quede a su misma altura
    renderMessages();
  }
  function closePanel(){
    const p = $('.ft-chat-panel'); const btn = $('#ft-chat-btn');
    if(!p) return; p.classList.remove('open');
    if(btn) btn.classList.remove('hidden');
  }

  function sendMessage(text){
    if(!text || !text.trim()) return;
    const msgs = loadMessages();
    const m = {from:'me', text: text.trim(), time: Date.now()};
    msgs.push(m); saveMessages(msgs); renderMessages();

    // Simular respuesta del bot
    setTimeout(()=>{
      const msgs2 = loadMessages();
      msgs2.push({from:'bot', text: 'Gracias por tu mensaje. Pronto un agente te responderá.', time: Date.now()});
      saveMessages(msgs2); renderMessages();
    }, 900);
  }

  // Inicializar eventos
  document.addEventListener('DOMContentLoaded', function(){
    const btn = document.getElementById('ft-chat-btn');
    const panel = document.getElementById('ft-chat-panel');
    const closeBtn = document.getElementById('ft-chat-close');
    const form = document.getElementById('ft-chat-form');
    const textarea = document.getElementById('ft-chat-input');

    if(btn) btn.addEventListener('click', function(e){
      if(panel.classList.contains('open')) closePanel(); else openPanel();
    });
    if(closeBtn) closeBtn.addEventListener('click', closePanel);
    if(form) form.addEventListener('submit', function(ev){
      ev.preventDefault(); sendMessage(textarea.value); textarea.value=''; textarea.focus();
    });

    // Enviar con Enter (sin Shift)
    if(textarea){
      textarea.addEventListener('keydown', function(e){
        if(e.key === 'Enter' && !e.shiftKey){
          e.preventDefault();
          if(form) form.dispatchEvent(new Event('submit', {cancelable: true}));
        }
      });
    }

    // Render initially (ensures sample conversation present)
    if(!localStorage.getItem(STORAGE_KEY)) saveMessages(defaultConversation());
    renderMessages();
  });

})();
