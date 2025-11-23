document.addEventListener('DOMContentLoaded', function () {
    const notificationBtn = document.getElementById('notification-btn');
    const notificationPanel = document.getElementById('notification-panel');
    const closePanelBtn = document.getElementById('close-panel');
    const badge = document.getElementById('notification-badge');
    const panelBody = document.querySelector('#notification-panel .panel-body');
    const panelFooter = document.querySelector('#notification-panel .panel-footer');

    // Toggle Panel
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notificationPanel.classList.toggle('active');
            if (notificationPanel.classList.contains('active')) {
                cargarNotificaciones();
            }
        });
    }

    if (closePanelBtn) {
        closePanelBtn.addEventListener('click', function () {
            notificationPanel.classList.remove('active');
        });
    }

    // Close when clicking outside
    document.addEventListener('click', function (e) {
        if (notificationPanel && notificationPanel.classList.contains('active')) {
            if (!notificationPanel.contains(e.target) && !notificationBtn.contains(e.target)) {
                notificationPanel.classList.remove('active');
            }
        }
    });

    // Cargar notificaciones al inicio
    cargarNotificaciones();

    // Intervalo para verificar nuevas notificaciones (cada 60 seg)
    setInterval(cargarNotificaciones, 60000);

    function cargarNotificaciones() {
        fetch('../../../controladores/notificaciones/notificaciones_controller.php?action=obtener_no_leidas')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarBadge(data.total);
                    renderizarLista(data.data);
                }
            })
            .catch(error => console.error('Error al cargar notificaciones:', error));
    }

    function actualizarBadge(total) {
        if (badge) {
            badge.textContent = total;
            if (total > 0) {
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        let interval = seconds / 31536000;
        if (interval > 1) return "hace " + Math.floor(interval) + " años";

        interval = seconds / 2592000;
        if (interval > 1) return "hace " + Math.floor(interval) + " meses";

        interval = seconds / 86400;
        if (interval > 1) return "hace " + Math.floor(interval) + " días";

        interval = seconds / 3600;
        if (interval > 1) return "hace " + Math.floor(interval) + " horas";

        interval = seconds / 60;
        if (interval > 1) return "hace " + Math.floor(interval) + " minutos";

        return "hace " + Math.floor(seconds) + " segundos";
    }

    function renderizarLista(notificaciones) {
        if (!panelBody) return;

        panelBody.innerHTML = '';

        if (notificaciones.length === 0) {
            panelBody.innerHTML = '<div class="text-center p-3 text-muted"><small>No tienes notificaciones nuevas</small></div>';
            return;
        }

        notificaciones.forEach(notif => {
            const item = document.createElement('div');
            item.className = 'notification-item';
            // Determinar icono según tipo
            let iconClass = 'bx-info-circle';
            let bgClass = 'bg-blue';

            if (notif.tipo === 'inscripcion') {
                iconClass = 'bx-user-plus';
                bgClass = 'bg-blue';
            } else if (notif.tipo === 'comunicado') {
                iconClass = 'bx-bullhorn';
                bgClass = 'bg-orange';
            }

            // Formatear fecha (hace X tiempo)
            const tiempoTranscurrido = timeAgo(notif.fecha_creacion);

            item.innerHTML = `
                <div class="notif-icon ${bgClass}"><i class='bx ${iconClass}'></i></div>
                <div class="notif-content" style="flex: 1;">
                    <p class="mb-1"><strong>${notif.titulo}</strong></p>
                    <p class="mb-1 text-muted" style="font-size: 0.85rem;">${notif.mensaje}</p>
                    <small class="text-muted" style="font-size: 0.75rem;">${tiempoTranscurrido}</small>
                    <div class="mt-2 d-flex gap-2">
                        ${notif.enlace ? `<a href="${notif.enlace}" class="btn btn-xs btn-outline-primary py-0 px-2 btn-ver-notif" data-id="${notif.IdNotificacion}" style="font-size: 0.75rem;">Ver</a>` : ''}
                        <button class="btn btn-xs btn-outline-secondary py-0 px-2 btn-marcar-leida" data-id="${notif.IdNotificacion}" style="font-size: 0.75rem;">Leído</button>
                    </div>
                </div>
            `;
            panelBody.appendChild(item);
        });

        // Agregar listeners a botones "Leído"
        document.querySelectorAll('.btn-marcar-leida').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation(); // Evitar cerrar el panel
                const id = this.getAttribute('data-id');
                marcarComoLeida(id);
            });
        });

        // Agregar listeners a botones "Ver" para marcar como leída antes de ir
        document.querySelectorAll('.btn-ver-notif').forEach(btn => {
            btn.addEventListener('click', function (e) {
                // No prevenimos el default inmediatamente para permitir la navegación,
                // pero idealmente deberíamos marcar como leída en segundo plano.
                // Sin embargo, si la navegación es rápida, la petición fetch podría cancelarse.
                // Una opción segura es usar sendBeacon o esperar un poco, pero para UX fluida:
                // Lanzamos el fetch y dejamos que el navegador navegue.
                const id = this.getAttribute('data-id');
                marcarComoLeida(id, false); // false = no recargar lista
            });
        });

        // Actualizar footer con "Marcar todas como leídas"
        if (panelFooter) {
            panelFooter.innerHTML = '<a href="#" id="btn-marcar-todas">Marcar todas como leídas</a>';
            document.getElementById('btn-marcar-todas').addEventListener('click', function (e) {
                e.preventDefault();
                marcarTodasComoLeidas();
            });
        }
    }

    function marcarComoLeida(id, recargar = true) {
        const formData = new FormData();
        formData.append('id', id);

        fetch('../../../controladores/notificaciones/notificaciones_controller.php?action=marcar_leida', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && recargar) {
                    cargarNotificaciones(); // Recargar lista
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function marcarTodasComoLeidas() {
        fetch('../../../controladores/notificaciones/notificaciones_controller.php?action=marcar_todas_leidas')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cargarNotificaciones();
                }
            })
            .catch(error => console.error('Error:', error));
    }
});
