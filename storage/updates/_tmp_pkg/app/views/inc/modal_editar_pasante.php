<!-- ======= MODAL: EDITAR PASANTE ======= -->
<div id="modalEditarPasante" class="modal-overlay">
    <div class="modal-box" style="max-width: 500px; min-height: auto;">
        <div class="modal-head">
            <div>
                <h2><i class="ti ti-edit" style="margin-right:8px;"></i>Editar Datos Personales</h2>
                <p>Modificar información básica del pasante</p>
            </div>
            <button class="btn-close-modal" onclick="cerrarModalEditarPasante()"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body" style="padding: 20px 30px 30px;">
            <input type="hidden" id="edit-pasante-id">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="font-size: 0.85rem; color: #1e3a8a;">Nombres</label>
                <input type="text" class="form-input" id="edit-nombres" placeholder="Nombres del pasante" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="font-size: 0.85rem; color: #1e3a8a;">Apellidos</label>
                <input type="text" class="form-input" id="edit-apellidos" placeholder="Apellidos del pasante" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="font-size: 0.85rem; color: #1e3a8a;">Teléfono</label>
                <input type="text" class="form-input" id="edit-telefono" placeholder="Teléfono de contacto">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="font-size: 0.85rem; color: #1e3a8a;">Dirección</label>
                <input type="text" class="form-input" id="edit-direccion" placeholder="Dirección de domicilio">
            </div>
            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label" style="font-size: 0.85rem; color: #1e3a8a;">Institución de Procedencia</label>
                <select class="form-input no-choices" id="edit-institucion">
                    <option value="">Seleccione una institución</option>
                    <!-- Options populated by JS -->
                </select>
            </div>

            <button class="btn-submit" onclick="guardarDatosPasante()" style="padding: 12px 20px; font-size: 0.9rem; width: 100%; display: flex; box-shadow: 0 4px 12px rgba(37,99,235,0.2);">
                <i class="ti ti-device-floppy"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>

<script>
// ── Editar Pasante ──
window.abrirModalEditarPasante = async function(pasanteId) {
    try {
        Swal.fire({ title: 'Cargando datos...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const resp = await fetch(URLROOT + '/pasantes/obtenerDatosPersonales/' + pasanteId);
        const json = await resp.json();
        Swal.close();
        
        if (json.success) {
            const data = json.data;
            document.getElementById('edit-pasante-id').value = pasanteId;
            document.getElementById('edit-nombres').value = data.nombres || '';
            document.getElementById('edit-apellidos').value = data.apellidos || '';
            document.getElementById('edit-telefono').value = data.telefono || '';
            document.getElementById('edit-direccion').value = data.direccion || '';
            
            // Populate select
            const selectInst = document.getElementById('edit-institucion');
            selectInst.innerHTML = '<option value="">Seleccione una institución</option>';
            if (data.instituciones_lista) {
                data.instituciones_lista.forEach(inst => {
                    const option = document.createElement('option');
                    option.value = inst.id;
                    option.textContent = inst.nombre;
                    if (data.institucion_id == inst.id) option.selected = true;
                    selectInst.appendChild(option);
                });
            }
            
            document.getElementById('modalEditarPasante').classList.add('active');
        } else {
            Swal.fire('Error', json.message, 'error');
        }
    } catch (e) {
        Swal.close();
        Swal.fire('Error', 'Error de conexión', 'error');
    }
};

window.cerrarModalEditarPasante = function() {
    document.getElementById('modalEditarPasante').classList.remove('active');
};

window.guardarDatosPasante = async function() {
    const id = document.getElementById('edit-pasante-id').value;
    const nombres = document.getElementById('edit-nombres').value.trim();
    const apellidos = document.getElementById('edit-apellidos').value.trim();
    const telefono = document.getElementById('edit-telefono').value.trim();
    const direccion = document.getElementById('edit-direccion').value.trim();
    const institucion = document.getElementById('edit-institucion').value;

    if (!nombres || !apellidos) {
        Swal.fire('Atención', 'Nombres y apellidos son obligatorios', 'warning');
        return;
    }

    const fd = new FormData();
    fd.append('id', id);
    fd.append('nombres', nombres);
    fd.append('apellidos', apellidos);
    fd.append('telefono', telefono);
    fd.append('direccion', direccion);
    fd.append('institucion', institucion);

    try {
        const resp = await fetch(URLROOT + '/pasantes/actualizarDatos', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await resp.json();
        
        if (json.success) {
            cerrarModalEditarPasante();
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: json.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => window.location.reload());
        } else {
            Swal.fire('Error', json.message, 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Error de conexión', 'error');
    }
};
</script>
