// tabla.js
class TablaDinamica {
    constructor(config) {
        this.config = {
            tablaId: 'tabla-datos',
            tbodyId: 'table-body',
            buscarId: 'buscar',
            entriesId: 'entries',
            paginationId: 'pagination',
            ...config
        };

        this.currentPage = 1;
        this.entriesPerPage = parseInt(localStorage.getItem(`${this.config.tablaId}_entries`)) || 10;
        this.filteredData = [...this.config.data];
        this.allData = [...this.config.data];

        this.init();
    }

    init() {
        this.renderTable();
        this.bindEvents();
    }

    bindEvents() {
        const buscar = document.getElementById(this.config.buscarId);
        const entries = document.getElementById(this.config.entriesId);

        if (buscar) {
            buscar.addEventListener('input', () => {
                this.filterData(buscar.value);
            });
        }

        if (entries) {
            entries.addEventListener('change', (e) => {
                this.entriesPerPage = parseInt(e.target.value);
                localStorage.setItem(`${this.config.tablaId}_entries`, this.entriesPerPage);
                this.currentPage = 1;
                this.renderTable();
            });
        }
    }

    filterData(term) {
        const searchTerm = term.toLowerCase().trim();
        const searchTerms = searchTerm.split(' ');

        this.filteredData = this.allData.filter(item => {
            return this.config.columns.some(col => {
                const value = this.getNestedValue(item, col.key);
                return searchTerms.every(t => String(value).toLowerCase().includes(t));
            });
        });

        this.currentPage = 1;
        this.renderTable();
    }

    getNestedValue(obj, path) {
        return path.split('.').reduce((acc, part) => acc?.[part], obj);
    }

    // Modifica el método renderTable en tablas.js
    renderTable() {
        const start = (this.currentPage - 1) * this.entriesPerPage;
        const end = start + this.entriesPerPage;
        const paginatedItems = this.filteredData.slice(start, end);
        const tbody = document.getElementById(this.config.tbodyId);
        const tabla = document.getElementById(this.config.tablaId);

        if (!tbody || !tabla) return;

        // Limpiar tabla
        tbody.innerHTML = '';

        // Verificar si hay datos
        if (paginatedItems.length === 0) {
            const noDataRow = document.createElement('tr');
            noDataRow.innerHTML = `
                <td colspan="${this.config.columns.length + 1}" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <i class='bx bx-folder-open' style="font-size: 4rem; color: #c90000; opacity: 0.7;"></i>
                        <h5 class="mt-3 text-muted">No hay registros disponibles</h5>
                        <p class="text-muted mb-0">No se encontraron datos para mostrar</p>
                    </div>
                </td>
            `;
            tbody.appendChild(noDataRow);
            return;
        }

        // Renderizar datos si existen
        paginatedItems.forEach(item => {
            const tr = document.createElement('tr');
            let actionsHtml = '';

            if (this.config.acciones) {
                this.config.acciones.forEach(acc => {
                    let actionHtml = '';
                    if (acc.onClick) {
                        const onclick = acc.onClick.replace('{id}', item[this.config.idField]);
                        actionHtml = `<button type="button" class="btn btn-sm ${acc.class}" onclick="${onclick}">${acc.icon}</button>`;
                    } else if (acc.url) {
                        const url = acc.url.replace('{id}', item[this.config.idField]);
                        actionHtml = `<a href="${url}" class="btn btn-sm ${acc.class}">${acc.icon}</a>`;
                    }
                    actionsHtml += actionHtml + ' ';
                });
            }

            tr.innerHTML = `
                ${this.config.columns.map(col => {
                    const value = this.getNestedValue(item, col.key);
                    return `<td>${value !== undefined && value !== null ? value : ''}</td>`;
                }).join('')}
                <td>${actionsHtml}</td>
            `;
            tbody.appendChild(tr);
        });

        this.renderPagination();
    }

    renderPagination() {
        const totalPages = Math.ceil(this.filteredData.length / this.entriesPerPage);
        const pagination = document.getElementById(this.config.paginationId);

        if (!pagination) return;

        pagination.innerHTML = '';

        if (totalPages === 0) return;

        // Botón "Anterior"
        this.addButton(pagination, '<', this.currentPage === 1, () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.renderTable();
            }
        });

        // Páginas
        const pages = this.getPagesToShow(totalPages);
        pages.forEach(page => {
            this.addButton(pagination, page, false, () => {
                this.currentPage = page;
                this.renderTable();
            }, this.currentPage === page);
        });

        // Botón "Siguiente"
        this.addButton(pagination, '>', this.currentPage === totalPages, () => {
            if (this.currentPage < totalPages) {
                this.currentPage++;
                this.renderTable();
            }
        });
    }

    addButton(parent, text, disabled, onClick, active = false) {
        const button = document.createElement('button');
        button.innerHTML = text;
        button.className = `btn btn-sm btn-outline-secondary mx-1 ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
        button.disabled = disabled;
        button.style.cursor = 'pointer';
        button.onclick = onClick;
        parent.appendChild(button);
    }

    getPagesToShow(totalPages) {
        const pages = new Set([1]);
        if (totalPages > 1) {
            for (let i = Math.max(2, this.currentPage - 2); i <= Math.min(totalPages - 1, this.currentPage + 2); i++) {
                pages.add(i);
            }
            pages.add(totalPages);
        }
        return Array.from(pages).sort((a, b) => a - b);
    }

    /**
     * Actualiza los datos de la tabla y vuelve a renderizar
     * @param {Array} newData - Nuevo conjunto de datos
     * @param {boolean} preserveFilter - Si true, mantiene el filtro actual
     */
    updateData(newData, preserveFilter = false) {
        // Actualizar datos originales
        this.allData = [...newData];
        
        // Si no se preserva el filtro, reiniciar búsqueda
        if (!preserveFilter) {
            this.filteredData = [...newData];
            const buscar = document.getElementById(this.config.buscarId);
            if (buscar) buscar.value = ''; // Limpiar input de búsqueda
        } else {
            // Re-aplicar filtro si hay término
            const buscar = document.getElementById(this.config.buscarId);
            if (buscar && buscar.value.trim() !== '') {
                this.filterData(buscar.value);
            } else {
                this.filteredData = [...newData];
            }
        }

        // Resetear a la primera página
        this.currentPage = 1;

        // Volver a renderizar
        this.renderTable();
    }
}