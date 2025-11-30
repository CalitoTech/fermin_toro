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

        if(!tablaActual) {
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
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <style>
                :root {
                    --primary-color: ${config.colorPrincipal};
                    --secondary-color: #ffffff;
                    --text-color: #333333;
                    --border-color: #e0e0e0;
                    --bg-light: #f8f9fa;
                }

                body {
                    font-family: 'Poppins', sans-serif;
                    margin: 0;
                    padding: 0;
                    color: var(--text-color);
                    line-height: 1.3;
                    font-size: 11px;
                    background-color: white;
                }

                /* Franja superior - Parte del flujo normal para evitar superposiciones */
                .top-bar {
                    height: 8px;
                    background-color: var(--primary-color);
                    width: 100%;
                    margin-bottom: 20px;
                }

                .container {
                    padding: 0 30px 30px 30px;
                    max-width: 100%;
                    margin: 0 auto;
                }

                /* Encabezado Profesional con 3 secciones */
                .reporte-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid var(--primary-color);
                    padding-bottom: 15px;
                    gap: 15px;
                }

                .header-left {
                    flex: 0 0 auto;
                }

                .reporte-logo {
                    width: 90px;
                    height: auto;
                    object-fit: contain;
                }

                .header-center {
                    flex: 1;
                    text-align: center;
                    font-size: 9px;
                    font-weight: 600;
                    color: #444;
                    text-transform: uppercase;
                    line-height: 1.4;
                    padding: 0 10px;
                }

                .header-right {
                    flex: 0 0 auto;
                    text-align: right;
                    min-width: 180px;
                }

                .reporte-titulo {
                    color: var(--primary-color);
                    font-size: 18px;
                    font-weight: 700;
                    margin: 0 0 2px 0;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .reporte-subtitulo {
                    font-size: 11px;
                    color: #000;
                    font-weight: 600;
                    margin-bottom: 5px;
                }

                .reporte-meta {
                    font-size: 10px;
                    color: #666;
                    line-height: 1.3;
                }

                /* Tabla Estilizada */
                .reporte-tabla {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 5px;
                    background-color: white;
                }

                .reporte-tabla th {
                    background-color: var(--primary-color);
                    color: var(--secondary-color);
                    font-weight: 600;
                    text-align: left;
                    padding: 8px 10px;
                    font-size: 10px;
                    text-transform: uppercase;
                    border: 1px solid var(--primary-color);
                }

                .reporte-tabla td {
                    padding: 8px 10px;
                    border: 1px solid #ddd;
                    color: #444;
                    font-size: 10px;
                    vertical-align: middle;
                }

                .reporte-tabla tr:nth-child(even) {
                    background-color: var(--bg-light);
                }

                /* Footer */
                .reporte-footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: white;
                    padding: 10px 30px;
                    border-top: 1px solid var(--border-color);
                    font-size: 9px;
                    color: #777;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                /* Ajustes de Impresión */
                @media print {
                    @page {
                        margin: 10mm 10mm 15mm 10mm;
                        size: auto;
                    }

                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }

                    .reporte-tabla th {
                        background-color: var(--primary-color) !important;
                        color: white !important;
                    }

                    .reporte-footer {
                        position: fixed;
                        bottom: 0;
                    }
                    
                    .reporte-tabla {
                        page-break-inside: auto;
                    }
                    
                    tr {
                        page-break-inside: avoid;
                    }
                }
            </style>
        </head>
        <body>
            <div class="top-bar"></div>
            
            <div class="container">
                <header class="reporte-header">
                    <div class="header-left">
                        <img src="${config.logoUrl}" class="reporte-logo" alt="Logo Institucional">
                    </div>
                    <div class="header-right">
                        <h1 class="reporte-titulo">${titulo}</h1>
                        <div class="reporte-subtitulo">U.E.C. "Fermín Toro" - Araure</div>
                        <div class="reporte-meta">
                            Generado por: <strong>${config.usuario}</strong><br>
                            Fecha: ${fechaHora}
                        </div>
                    </div>
                </header>
                
                <table class="reporte-tabla">
                    ${tablaClone.innerHTML}
                </table>
            </div>
            
            <div class="reporte-footer">
                <div>U.E.C. "Fermín Toro" - Documento Oficial</div>
                <div class="paginacion">Página 1</div>
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