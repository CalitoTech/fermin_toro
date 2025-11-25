/**
 * ==============================================
 * SISTEMA DE INSCRIPCIÓN ESCOLAR - UECFT ARAURE
 * ==============================================
 * 
 * Archivo principal JavaScript (main.js)
 * 
 * Organizado en módulos funcionales con documentación detallada
 * 
 * @author [Carlos Navas]
 * @version 1.0
 */

/* ==============================================
 * MÓDULO PRINCIPAL - INICIALIZACIÓN
 * ==============================================
 * Responsable de inicializar todos los módulos cuando el DOM esté listo
 */
$(document).ready(function() {
    // Inicializa tooltips de Bootstrap
    TooltipManager.init();
    
    // Configura desplazamiento suave para anclas
    SmoothScroll.init();
    
    // Gestiona el comportamiento del sidebar
    SidebarManager.init();
    
    // Maneja la fecha y hora actual
    DateTimeManager.init();
    
    // Controla el panel de notificaciones
    NotificationManager.init();
    
    // Administra el menú móvil responsivo
    MobileMenuManager.init();
});

/* ==============================================
 * MÓDULO: TOOLTIP MANAGER
 * ==============================================
 * Encargado de la gestión de tooltips en la interfaz
 */
const TooltipManager = {
    /**
     * Inicializa el módulo
     */
    init: function() {
        this.initTooltips();
    },

    /**
     * Activa los tooltips de Bootstrap en elementos con el atributo data-toggle
     */
    initTooltips: function() {
        $('[data-toggle="tooltip"]').tooltip();
    }
};

/* ==============================================
 * MÓDULO: SMOOTH SCROLL
 * ==============================================
 * Maneja el desplazamiento suave al hacer clic en enlaces internos (#)
 */
const SmoothScroll = {
    /**
     * Inicializa el módulo
     */
    init: function() {
        this.setupAnchors();
    },

    /**
     * Configura el comportamiento de los anclajes
     */
    setupAnchors: function() {
        $('a[href^="#"]').on('click', function(e) {
            // Previene el comportamiento por defecto del ancla
            e.preventDefault();
            
            // Obtiene el elemento destino
            const target = $(this.hash);
            
            if (target.length) {
                // Animación de desplazamiento con offset de 80px
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 800); // Duración de 800ms
            }
        });
    }
};

/* ==============================================
 * MÓDULO: SIDEBAR MANAGER
 * ==============================================
 * Gestiona todo el comportamiento del panel lateral (sidebar)
 * Incluye: toggle, submenús y comportamiento responsivo
 */
const SidebarManager = {
    elements: {
        sidebar: $('.sidebar'),
        sidebarBtn: $('.bx-menu'),
        homeSection: $('.home-section'),
        allLi: $('.sidebar .nav-links li')
    },

    init: function() {
        // Solo inicializar si los elementos existen
        if (this.elements.sidebar.length === 0) {
            console.warn('Elemento .sidebar no encontrado');
            return;
        }

        this.setInitialState();
        this.setupToggle();
        this.setupSubmenus();
        this.setupMobileBehavior();
    },

    setInitialState: function() {
        // Fuerza estado cerrado en todos los dispositivos
        this.elements.sidebar
            .removeClass('open')
            .addClass('close')
            .removeAttr('style');
            
        this.elements.homeSection
            .removeClass('expand')
            .removeAttr('style');
            
        this.closeAllSubmenus();
    },

    setupToggle: function() {
        const self = this;
        this.elements.sidebarBtn.off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            self.toggleSidebar();
        });
    },

    toggleSidebar: function() {
        // Asegura que solo una clase esté presente
        this.elements.sidebar.toggleClass('close').toggleClass('open');
        this.elements.homeSection.toggleClass('expand');
        
        if (this.elements.sidebar.hasClass('close')) {
            this.closeAllSubmenus();
        }
        
    },

    /**
     * Configura los submenús del sidebar
     */
    setupSubmenus: function() {
        this.elements.allLi.each(function() {
            const li = $(this);
            const arrow = li.find('.arrow');
            
            // Si el elemento tiene flecha (submenú)
            if (arrow.length) {
                arrow.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    SidebarManager.handleSubmenuClick(li);
                });
            }
        });
    },

    /**
     * Maneja el clic en un submenú
     * @param {jQuery} li - Elemento LI que contiene el submenú
     */
    handleSubmenuClick: function(li) {
        // Cierra otros submenús y abre/cierra el actual
        li.siblings().removeClass('showMenu');
        li.toggleClass('showMenu');
    },

    /**
     * Cierra todos los submenús
     */
    closeAllSubmenus: function() {
        this.elements.allLi.removeClass('showMenu');
    },

    /**
     * Configura el comportamiento en dispositivos móviles
     */
    setupMobileBehavior: function() {
        // Cierra el sidebar al hacer clic fuera en móviles
        $(document).on('click', (e) => {
            if (!$(e.target).closest('.sidebar, .bx-menu').length && $(window).width() <= 768) {
                this.closeSidebar();
            }
        });

        // Ajusta el sidebar al cambiar el tamaño de la ventana
        $(window).on('resize', () => {
            if ($(window).width() <= 768) {
                this.closeSidebar();
            }
        });
    },

    /**
     * Cierra el sidebar (usado en móviles)
     */
    closeSidebar: function() {
        this.elements.sidebar.addClass('close');
        this.elements.homeSection.removeClass('expand');
        this.closeAllSubmenus();
    }
};

/* ==============================================
 * MÓDULO: DATE TIME MANAGER
 * ==============================================
 * Maneja la visualización de la fecha y hora actual
 * Actualiza cada segundo para mantener la hora exacta
 */
const DateTimeManager = {
    /**
     * Inicializa el módulo
     */
    init: function() {
        this.updateDateTime(); // Actualización inmediata
        setInterval(() => this.updateDateTime(), 1000); // Actualiza cada segundo
    },

    /**
     * Actualiza el elemento con la fecha y hora actual
     */
    updateDateTime: function() {
        const now = new Date();
        const options = { 
            weekday: 'short',  // Día abreviado (ej: "lun")
            year: 'numeric',   // Año (ej: "2023")
            month: 'short',    // Mes abreviado (ej: "sep")
            day: 'numeric',    // Día del mes (ej: "25")
            hour: '2-digit',   // Hora (ej: "09")
            minute: '2-digit'  // Minutos (ej: "30")
        };
        
        // Formatea la fecha según configuración regional y actualiza el DOM
        document.getElementById('current-date-time').textContent = 
            now.toLocaleDateString('es-VE', options);
    }
};

/* ==============================================
 * MÓDULO: NOTIFICATION MANAGER
 * ==============================================
 * Controla el panel de notificaciones (mostrar/ocultar)
 */
const NotificationManager = {
    // Referencias a elementos del DOM
    elements: {
        notificationBtn: document.getElementById('notification-btn'),
        notificationPanel: document.getElementById('notification-panel'),
        closePanel: document.getElementById('close-panel')
    },

    /**
     * Inicializa el módulo
     */
    init: function() {
        // Solo si existen los elementos necesarios
        if (this.elements.notificationBtn) {
            this.setupEventListeners();
        }
    },

    /**
     * Configura los event listeners para las notificaciones
     */
    setupEventListeners: function() {
        // Toggle al hacer clic en el botón
        this.elements.notificationBtn.addEventListener('click', () => this.togglePanel());
        
        // Cerrar al hacer clic en el botón de cerrar
        this.elements.closePanel.addEventListener('click', () => this.closePanel());
        
        // Cerrar al hacer clic fuera del panel
        document.addEventListener('click', (e) => {
            if (!this.elements.notificationPanel.contains(e.target) && 
                e.target !== this.elements.notificationBtn) {
                this.closePanel();
            }
        });
    },

    /**
     * Alterna la visibilidad del panel de notificaciones
     */
    togglePanel: function() {
        this.elements.notificationPanel.style.display = 
            this.elements.notificationPanel.style.display === 'block' ? 'none' : 'block';
    },

    /**
     * Cierra el panel de notificaciones
     */
    closePanel: function() {
        this.elements.notificationPanel.style.display = 'none';
    }
};

/* ==============================================
 * MÓDULO: MOBILE MENU MANAGER
 * ==============================================
 * Maneja el menú móvil y la interfaz responsiva
 */
const MobileMenuManager = {
    init: function() {
        // 1. Referencias a elementos (sin jQuery)
        this.sidebar = document.querySelector('.sidebar');
        this.sidebarBtn = document.querySelector('.bx-menu');
        this.homeSection = document.querySelector('.home-section');
        this.logoDetails = document.querySelector('.logo-details');
        this.overlay = document.querySelector('.sidebar-overlay');

        // 2. Solo continuar si existen los elementos esenciales
        if (!this.sidebar || !this.sidebarBtn) {
            console.warn('Elementos esenciales no encontrados');
            return;
        }

        // 3. Crear botón móvil (igual que antes)
        this.createMobileToggle();

        // 4. Configurar eventos (adaptado a tu estructura)
        this.setupEventListeners();

        // 5. Estado inicial (forzar cerrado en móviles)
        this.updateMobileMenu();
    },

    createMobileToggle: function() {
        // Eliminar toggle anterior si existe
        const oldToggle = document.querySelector('.mobile-menu-toggle');
        if (oldToggle) oldToggle.remove();
        
        // Crear botón idéntico al que tenías funcionando
        this.mobileToggle = document.createElement('button');
        this.mobileToggle.className = 'mobile-menu-toggle';
        this.mobileToggle.innerHTML = '<i class="bx bx-menu"></i>';
        document.body.appendChild(this.mobileToggle);
    },

    setupEventListeners: function() {
        const self = this;

        // 1. Click en botón móvil - usa misma lógica que SidebarManager
        this.mobileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            $(self.sidebar).toggleClass('close').toggleClass('open');
            $(self.homeSection).toggleClass('expand');
            self.adjustLogoPosition();
            self.toggleOverlay();
        });

        // 2. Click en botón regular (.bx-menu) - delegamos a SidebarManager
        this.sidebarBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            SidebarManager.toggleSidebar();
            self.adjustLogoPosition();
            if (window.innerWidth <= 768) {
                self.toggleOverlay();
            }
        });

        // 3. Click en overlay para cerrar menú
        if (this.overlay) {
            this.overlay.addEventListener('click', function() {
                self.closeSidebar();
            });
        }

        // 4. Cerrar al hacer click fuera (solo móviles)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!self.sidebar.contains(e.target) &&
                    !e.target.closest('.mobile-menu-toggle') &&
                    e.target !== self.sidebarBtn) {

                    self.closeSidebar();
                }
            }
        });
    },

    toggleOverlay: function() {
        if (!this.overlay || window.innerWidth > 768) return;

        if ($(this.sidebar).hasClass('open')) {
            this.overlay.classList.add('active');
        } else {
            this.overlay.classList.remove('active');
        }
    },

    closeSidebar: function() {
        $(this.sidebar).addClass('close').removeClass('open');
        $(this.homeSection).removeClass('expand');
        this.adjustLogoPosition();
        if (this.overlay) {
            this.overlay.classList.remove('active');
        }
    },

    adjustLogoPosition: function() {
        if (!this.logoDetails || window.innerWidth > 768) return;

        // Ajuste fino: 35px en lugar de 60px para moverlo solo un poco más abajo
        this.logoDetails.style.marginTop =
            $(this.sidebar).hasClass('open') ? '35px' : '0';
    },

    updateMobileMenu: function() {
        if (window.innerWidth <= 768) {
            // Mostrar botón en móviles
            this.mobileToggle.style.display = 'block';
            
            // Forzar estado cerrado inicial (sin afectar desktop)
            if (!$(this.sidebar).hasClass('open')) {
                $(this.sidebar).addClass('close');
            }
        } else {
            // Ocultar botón en desktop
            this.mobileToggle.style.display = 'none';
        }
    }
};