// === DATOS GLOBALES ===
    // let allData = <?= json_encode($usuarios) ?>;
    // let filteredData = [...allData];
    // let currentPage = 1;
    // let entriesPerPage = parseInt(document.getElementById('entries').value) || 10;

    // // === BÚSQUEDA EN TIEMPO REAL ===
    // document.getElementById('buscar').addEventListener('input', function() {
    //     const searchTerm = this.value.toLowerCase().trim();
    //     const searchTerms = searchTerm.split(' ');

    //     filteredData = allData.filter(item => {
    //         const fullName = `${item.nombre} ${item.apellido}`.toLowerCase();
    //         return searchTerms.every(term => 
    //             fullName.includes(term) || 
    //             item.usuario.toLowerCase().includes(term) ||
    //             (item.correo && item.correo.toLowerCase().includes(term))
    //         );
    //     });

    //     currentPage = 1;
    //     renderTable();
    // });