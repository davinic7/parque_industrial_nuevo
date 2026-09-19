/**
 * Declaraciones de datos de la empresa (public/empresa/formularios.php): campos condicionales (exporta / importa)
 * y confirmación con SweetAlert2 antes de guardar el borrador o enviar la declaración.
 */
    (function() {
        const form = document.getElementById('djForm');
        const accionEl = document.getElementById('djAccion');
        const exporta = document.getElementById('exporta');
        const importa = document.getElementById('importa');
        if (exporta && !exporta.disabled) {
            exporta.addEventListener('change', function() {
                const el = document.getElementById('exportaFields');
                if (el) el.classList.toggle('d-none', !this.checked);
            });
        }
        if (importa && !importa.disabled) {
            importa.addEventListener('change', function() {
                const el = document.getElementById('importaFields');
                if (el) el.classList.toggle('d-none', !this.checked);
            });
        }

        if (!form || !accionEl || form.classList.contains('dj-no-js')) return;

        const btnG = document.getElementById('djBtnGuardar');
        const btnE = document.getElementById('djBtnEnviar');
        if (!btnG || !btnE) return;

        btnG.addEventListener('click', function() {
            Swal.fire({
                title: '¿Guardar borrador?',
                text: 'Los datos quedarán guardados sin enviar al Ministerio. Podés continuar más tarde.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#6c757d'
            }).then(function(r) {
                if (!r.isConfirmed) return;
                accionEl.value = 'guardar';
                form.submit();
            });
        });

        btnE.addEventListener('click', function() {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            Swal.fire({
                title: '¿Enviar declaración jurada?',
                html: 'Al enviar, el Ministerio podrá revisar los datos. <strong>No podrás editarlos</strong> hasta una resolución (salvo que sea rechazado).',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d6efd'
            }).then(function(r) {
                if (!r.isConfirmed) return;
                accionEl.value = 'enviar';
                form.submit();
            });
        });
    })();
