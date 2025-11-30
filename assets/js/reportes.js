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

                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Poppins', sans-serif;
                    color: var(--text-color);
                    line-height: 1.3;
                    font-size: 11px;
                    background-color: white;
                    padding-bottom: 60px;
                }

                /* Franja superior */
                .top-bar {
                    height: 8px;
                    background-color: var(--primary-color);
                    width: 100%;
                    margin-bottom: 20px;
                }

                .container {
                    padding: 0 30px 60px 30px;
                    max-width: 100%;
                    margin: 0 auto;
                }

                /* Encabezado Profesional con 3 columnas */
                .reporte-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 20px;
                    border-bottom: 2px solid var(--primary-color);
                    padding-bottom: 15px;
                    gap: 15px;
                }

                /* Logo - Columna izquierda */
                .header-left {
                    flex: 0 0 110px;
                    display: flex;
                    align-items: flex-start;
                }

                .reporte-logo {
                    width: 110px;
                    height: auto;
                    object-fit: contain;
                }

                /* Texto institucional - Columna centro */
                .header-center {
                    flex: 0 0 280px;
                    text-align: left;
                    font-size: 9px;
                    font-weight: 600;
                    color: #000;
                    text-transform: uppercase;
                    line-height: 1.5;
                    padding: 0 0 0 8px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }

                .header-center div {
                    margin-bottom: 2px;
                    text-align: justify;
                    text-justify: inter-word;
                }

                /* Título del reporte - Columna derecha */
                .header-right {
                    flex: 1;
                    text-align: right;
                    min-width: 0;
                }

                .reporte-titulo {
                    color: var(--primary-color);
                    font-size: 18px;
                    font-weight: 700;
                    margin: 0 0 8px 0;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    hyphens: auto;
                }

                .reporte-meta {
                    font-size: 10px;
                    color: #666;
                    line-height: 1.5;
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

                .footer-left {
                    flex: 1;
                }

                .footer-right {
                    flex: 0 0 auto;
                    font-weight: 600;
                }

                /* Ajustes de Impresión */
                @media print {
                    @page {
                        margin: 10mm 10mm 20mm 10mm;
                        size: auto;
                    }

                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                        padding-bottom: 0;
                    }

                    .container {
                        padding-bottom: 0;
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
                    
                    .reporte-tabla tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }

                    .reporte-tabla thead {
                        display: table-header-group;
                    }

                    .reporte-tabla tbody {
                        display: table-row-group;
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
                    
                    <div class="header-center">
                        <div>REPÚBLICA BOLIVARIANA DE VENEZUELA</div>
                        <div>MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN</div>
                        <div>UNIDAD EDUCATIVA COLEGIO FERMÍN TORO</div>
                        <div>INSCRITO EN EL MPPE BAJO EL NO. PD04281802</div>
                    </div>
                    
                    <div class="header-right">
                        <h1 class="reporte-titulo">${titulo}</h1>
                        <div class="reporte-meta">
                            <strong>Generado por:</strong> ${config.usuario}<br>
                            <strong>Fecha:</strong> ${fechaHora}
                        </div>
                    </div>
                </header>
                
                <table class="reporte-tabla">
                    ${tablaClone.innerHTML}
                </table>
            </div>
            
            <div class="reporte-footer">
                <div class="footer-left">U.E.C. "Fermín Toro" - Documento Oficial</div>
                <div class="footer-right" id="pageInfo">1 / 1</div>
            </div>
            
            <script>
                // Calcular total de páginas basado en el contenido
                function calcularTotalPaginas() {
                    const body = document.body;
                    const html = document.documentElement;
                    
                    // Altura total del documento
                    const alturaTotal = Math.max(
                        body.scrollHeight,
                        body.offsetHeight,
                        html.clientHeight,
                        html.scrollHeight,
                        html.offsetHeight
                    );
                    
                    // Altura de una página A4 en px (aproximadamente 1123px a 96dpi)
                    const alturaPagina = 1047; // px de contenido útil por página
                    
                    // Calcular número de páginas
                    const numPaginas = Math.max(1, Math.ceil(alturaTotal / alturaPagina));
                    
                    return numPaginas;
                }
                
                // Crear footers dinámicos para cada página
                function crearFootersParaCadaPagina() {
                    const totalPaginas = calcularTotalPaginas();
                    const pageInfo = document.getElementById('pageInfo');
                    
                    if (!pageInfo) {
                        return;
                    }
                    
                    // Actualizar el footer original con el total
                    pageInfo.textContent = '1 / ' + totalPaginas;
                    
                    // Si solo hay una página, no hacer nada más
                    if (totalPaginas <= 1) {
                        return;
                    }
                    
                    // Clonar el footer original para cada página adicional
                    const footerOriginal = document.querySelector('.reporte-footer');
                    const tabla = document.querySelector('.reporte-tabla tbody');
                    
                    if (!footerOriginal || !tabla) {
                        return;
                    }
                    
                    // Altura de página
                    const alturaPagina = 1047;
                    const header = document.querySelector('.reporte-header');
                    const thead = document.querySelector('.reporte-tabla thead');
                    
                    let alturaAcumulada = (header ? header.offsetHeight : 0) + (thead ? thead.offsetHeight : 0) + 40;
                    let paginaActual = 1;
                    
                    const filas = Array.from(tabla.querySelectorAll('tr'));
                    
                    filas.forEach((fila, index) => {
                        const alturaFila = fila.offsetHeight;
                        
                        // Si excedemos el límite de la página
                        if (alturaAcumulada + alturaFila > alturaPagina * paginaActual) {
                            // Insertar salto de página
                            const divSalto = document.createElement('div');
                            divSalto.style.pageBreakBefore = 'always';
                            divSalto.style.height = '0';
                            fila.parentNode.insertBefore(divSalto, fila);
                            
                            // Crear y agregar footer para esta página
                            paginaActual++;
                            const nuevoFooter = footerOriginal.cloneNode(true);
                            const pageInfoClone = nuevoFooter.querySelector('.footer-right');
                            if (pageInfoClone) {
                                pageInfoClone.textContent = paginaActual + ' / ' + totalPaginas;
                            }
                            
                            // Insertar el footer después del salto
                            divSalto.appendChild(nuevoFooter);
                            
                            // Resetear altura para la nueva página
                            alturaAcumulada = (header ? header.offsetHeight : 0) + (thead ? thead.offsetHeight : 0) + alturaFila;
                        } else {
                            alturaAcumulada += alturaFila;
                        }
                    });
                }
                
                // Ejecutar cuando la página esté lista
                window.addEventListener('load', function() {
                    setTimeout(function() {
                        crearFootersParaCadaPagina();
                        
                        // Imprimir después de crear los footers
                        setTimeout(function() {
                            window.print();
                            
                            // Cerrar ventana después de imprimir
                            setTimeout(function() {
                                window.close();
                            }, 500);
                        }, 300);
                    }, 400);
                });
            </script>
        </body>
        </html>
    `);

    printWindow.document.close();
}