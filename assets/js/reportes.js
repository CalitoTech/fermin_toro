/**
 * Función para generar reportes imprimibles profesionales
 * @param {string} titulo - Título del reporte
 * @param {string} selectorTabla - Selector CSS de la tabla a imprimir
 * @param {Object} opciones - { logoUrl: string, colorPrincipal: string, usuarioNombre: string, usuarioApellido: string }
 */
function generarReporteImprimible(titulo, selectorTabla, opciones = {}) {
    // Configuración con valores por defecto
    const config = {
        logoUrl: opciones.logoUrl || '../../assets/images/logo_institucional.png',
        colorPrincipal: opciones.colorPrincipal || '#c90000',
        usuario: typeof opciones.usuario !== 'undefined' ? opciones.usuario : 'Sistema',
        margenLogo: '0 auto 5px auto',
        anchoLogo: '120px',
        margenTitulo: '15px 0 10px 0'
    };

    // Obtener nombre completo o "Sistema" si no hay nombre
    const nombreUsuario = config.usuarioNombre || config.usuarioApellido 
        ? `${config.usuarioNombre} ${config.usuarioApellido}`.trim()
        : 'Sistema';

    // Obtener la tabla del DOM
    const tablaActual = document.querySelector(selectorTabla);
    
    if (!tablaActual) {
        console.error('No se encontró la tabla con el selector:', selectorTabla);
        return;
    }

    // Clonar y preparar la tabla
    const tablaClone = tablaActual.cloneNode(true);
    
    // Eliminar columna de acciones
    const eliminarColumnaAcciones = (tabla) => {
        const thead = tabla.querySelector('thead');
        if (thead) {
            const thAcciones = thead.querySelector('th:last-child');
            if (thAcciones && thAcciones.textContent.includes('Acciones')) {
                thAcciones.remove();
            }
        }
        
        const tbody = tabla.querySelector('tbody');
        if (tbody) {
            tbody.querySelectorAll('tr').forEach(tr => {
                const tdAcciones = tr.querySelector('td:last-child');
                if (tdAcciones) tdAcciones.remove();
            });
        }
    };
    eliminarColumnaAcciones(tablaClone);

    // Crear ventana de impresión
    const printWindow = window.open('', '_blank', 'height=600,width=800');
    
    // Fecha y hora formateadas
    const ahora = new Date();
    const fechaHora = ahora.toLocaleDateString('es-VE', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit' 
    }) + ' ' + ahora.toLocaleTimeString('es-VE', {
        hour: '2-digit',
        minute: '2-digit'
    });

    // Construir el documento HTML
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${titulo}</title>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
            <style>
                /* Estilos base */
                body {
                    font-family: 'Poppins', sans-serif;
                    margin: 0;
                    padding: 15px 25px 50px 25px; /* Más espacio abajo para Firefox */
                    color: #333;
                    line-height: 1.4;
                    font-size: 14px;
                }
                
                /* Encabezado */
                .reporte-header {
                    text-align: center;
                    margin-bottom: 10px;
                }
                
                .reporte-logo {
                    display: block;
                    width: ${config.anchoLogo};
                    height: auto;
                    margin: ${config.margenLogo};
                }
                
                .reporte-titulo {
                    color: ${config.colorPrincipal};
                    font-size: 1.3rem;
                    margin: ${config.margenTitulo};
                    font-weight: 600;
                    padding-bottom: 5px;
                    border-bottom: 1px solid #e0e0e0;
                }
                
                /* Tabla */
                .reporte-tabla {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 5px;
                    font-size: 0.9rem;
                }
                
                .reporte-tabla th {
                    background-color: #f5f5f5;
                    font-weight: 500;
                    text-align: left;
                    padding: 8px 10px;
                    border: 1px solid #ddd;
                    color: #444;
                }
                
                .reporte-tabla td {
                    padding: 6px 10px;
                    border: 1px solid #ddd;
                    vertical-align: top;
                }
                
                .reporte-tabla tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                
                /* Pie de página - Solución compatible con Firefox */
                .reporte-footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: white;
                    padding: 8px 25px;
                    border-top: 1px solid #eee;
                    font-size: 0.75rem;
                    color: #777;
                    display: flex;
                    justify-content: space-between;
                }
                
                .paginacion {
                    font-weight: 500;
                }
                
                /* Media queries para impresión */
                @media print {
                    @page {
                        size: auto;
                        margin: 10mm 10mm 20mm 10mm;
                    }
                    
                    body {
                        padding-bottom: 30px !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .reporte-footer {
                        position: fixed;
                        bottom: 0;
                        border-top: 1px solid #ddd;
                    }
                    
                    .reporte-tabla {
                        page-break-inside: avoid;
                    }
                }
            </style>
        </head>
        <body>
            <div class="reporte-header">
                <img src="${config.logoUrl}" class="reporte-logo" alt="Logo">
                <h1 class="reporte-titulo">${titulo}</h1>
            </div>
            
            <table class="reporte-tabla">
                ${tablaClone.innerHTML}
            </table>
            
            <div class="reporte-footer">
                <div class="paginacion">Página 1 de 1</div>
                <div>Impreso por: ${config.usuario} | ${fechaHora}</div>
            </div>
            
            <script>
                // Solución universal para paginación
                function updatePageCount() {
                    try {
                        // Intentar usar CSS Paged Media si está disponible
                        if (CSS.supports('content', 'counter(page)')) {
                            const style = document.createElement('style');
                            style.innerHTML = \`
                                @page {
                                    @bottom-left {
                                        content: "Impreso por: ${nombreUsuario} | ${fechaHora}";
                                        font-size: 0.7rem;
                                    }
                                    @bottom-right {
                                        content: "Página " counter(page) " de " counter(pages);
                                        font-size: 0.7rem;
                                    }
                                }
                            \`;
                            document.head.appendChild(style);
                        }
                        
                        // Para navegadores sin soporte completo (como Firefox)
                        const footer = document.querySelector('.reporte-footer');
                        if (footer) {
                            footer.style.display = 'flex';
                        }
                    } catch (e) {
                        console.log('Error al actualizar paginación:', e);
                    }
                }
                
                // Imprimir después de un breve retraso para que carguen los estilos
                setTimeout(function() {
                    updatePageCount();
                    setTimeout(function() {
                        window.print();
                        window.close();
                    }, 200);
                }, 100);
            </script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
}