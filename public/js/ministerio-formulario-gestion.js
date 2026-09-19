/**
 * Gestión de un formulario (public/ministerio/formulario-gestion.php), pestaña "Enviar":
 * muestra solo el bloque de filtros que corresponde al tipo elegido.
 */
(function() {
    function syncFiltros() {
        const sel = document.getElementById("tipoFiltro");
        if (!sel) return;
        const t = sel.value;
        document.querySelectorAll(".filtro-opt").forEach(el => el.style.display = "none");
        if (t === "rubro")               document.getElementById("boxRubros").style.display = "block";
        if (t === "ubicacion")           document.getElementById("boxUbic").style.display = "block";
        if (t === "estado")              document.getElementById("boxEstado").style.display = "block";
        if (t === "empresas_especificas") document.getElementById("boxEmp").style.display = "block";
    }
    const sel = document.getElementById("tipoFiltro");
    if (sel) { sel.addEventListener("change", syncFiltros); syncFiltros(); }
})();
