<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Base rapida personas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7fb;
            color: #111827;
        }

        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }

        .brand-mark {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
        }

        .panel {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .result-box {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <nav class="topbar">
        <div class="container py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <span class="brand-mark">SDI</span>
                <div>
                    <div class="fw-semibold">Base rapida personas</div>
                    <div class="small text-muted">Administrador: {{ auth()->user()->name ?? 'Usuario' }}</div>
                </div>
            </div>

            <span class="badge text-bg-secondary">Laravel 8</span>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex align-items-end justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Prueba base rapida de personas</h1>
                <p class="text-muted mb-0">Consulta, edita o agrega personas por RUT.</p>
            </div>
            <span class="badge text-bg-success">MySQL indexado</span>
        </div>

        <form id="personaForm" class="row g-4 align-items-start">
            <div class="col-lg-5">
                <div class="card panel">
                    <div class="card-body p-4">
                        <label for="rut" class="form-label fw-semibold">RUT</label>
                        <input
                            id="rut"
                            name="rut"
                            type="text"
                            class="form-control form-control-lg"
                            autocomplete="off"
                            inputmode="text"
                            placeholder="Ej: 12.345.678-9">

                        <div id="estadoBusqueda" class="small text-muted mt-2">
                            Escriba un RUT para preparar la consulta.
                        </div>

                        <div class="result-box mt-4 p-3">
                            <div class="small text-muted">Resultado</div>
                            <div id="resumenPersona" class="fw-semibold">Sin busqueda activa</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card panel">
                    <div class="card-body p-4">
                        <input type="hidden" id="personaId">

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="nombre1" class="form-label">Nombres</label>
                                <input id="nombre1" name="nombre1" type="text" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label for="appaterno" class="form-label">Apellido paterno</label>
                                <input id="appaterno" name="appaterno" type="text" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label for="apmaterno" class="form-label">Apellido materno</label>
                                <input id="apmaterno" name="apmaterno" type="text" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Telefono</label>
                                <input id="telefono" name="telefono" type="text" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label for="direccion" class="form-label">Direccion</label>
                                <input id="direccion" name="direccion" type="text" class="form-control">
                                <div class="form-text">La direccion se guarda cifrada.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white p-4 d-flex justify-content-between align-items-center">
                        <span id="modoFormulario" class="small text-muted">Esperando RUT</span>
                        <button id="guardarBtn" type="submit" class="btn btn-primary" disabled>Guardar</button>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <script>
    const rutInput = document.getElementById('rut');
    const estado = document.getElementById('estadoBusqueda');
    const resumen = document.getElementById('resumenPersona');
    const form = document.getElementById('personaForm');
    const guardarBtn = document.getElementById('guardarBtn');
    const modo = document.getElementById('modoFormulario');
    const fields = ['personaId', 'nombre1', 'appaterno', 'apmaterno', 'email', 'telefono', 'direccion'];
    let timer = null;
    let ultimoRut = '';

    function normalizarRut(value) {
        return (value || '').toUpperCase().replace(/[^0-9K]/g, '');
    }

    function formatearRut(value) {
        const limpio = normalizarRut(value);

        if (limpio.length <= 1) {
            return limpio;
        }

        return `${limpio.slice(0, -1)}-${limpio.slice(-1)}`;
    }

    function setStatus(message, type = 'muted') {
        estado.className = `small text-${type} mt-2`;
        estado.textContent = message;
    }

    function limpiarFormulario() {
        fields.forEach((id) => document.getElementById(id).value = '');
    }

    function cargarPersona(persona) {
        document.getElementById('personaId').value = persona.id || '';
        document.getElementById('nombre1').value = persona.nombre1 || '';
        document.getElementById('appaterno').value = persona.appaterno || '';
        document.getElementById('apmaterno').value = persona.apmaterno || '';
        document.getElementById('email').value = persona.email || '';
        document.getElementById('telefono').value = persona.telefono || '';
        document.getElementById('direccion').value = persona.direccion || '';
        resumen.textContent = persona.nombre_completo || 'Persona sin nombre completo';
    }

    async function leerJsonSeguro(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return response.json();
        }

        return {
            ok: false,
            message: 'El servidor respondio con un error inesperado. Recargue y vuelva a intentar.',
        };
    }

    async function buscarRut() {
        const rut = normalizarRut(rutInput.value);
        ultimoRut = rut;

        if (rut.length < 2) {
            limpiarFormulario();
            guardarBtn.disabled = true;
            modo.textContent = 'Esperando RUT';
            resumen.textContent = 'Sin busqueda activa';
            setStatus('Escriba un RUT para preparar la consulta.');
            return;
        }

        setStatus('Preparando consulta...');

        let data;

        try {
            const response = await fetch(`{{ route('personas-rapidas.buscar') }}?rut=${encodeURIComponent(rut)}`, {
                headers: {'Accept': 'application/json'}
            });
            data = await leerJsonSeguro(response);
        } catch (error) {
            limpiarFormulario();
            guardarBtn.disabled = true;
            modo.textContent = 'Error de conexion';
            resumen.textContent = 'No disponible';
            setStatus('No se pudo conectar con la base rapida.', 'danger');
            return;
        }

        if (ultimoRut !== rut) {
            return;
        }

        if (!data.ok) {
            limpiarFormulario();
            guardarBtn.disabled = true;
            modo.textContent = 'RUT no valido';
            resumen.textContent = 'No disponible';
            setStatus(data.message || 'No se puede consultar este RUT.', 'danger');
            return;
        }

        if (data.found) {
            cargarPersona(data.persona);
            guardarBtn.disabled = false;
            modo.textContent = 'Editando persona existente';
            setStatus('Encontrado. Puede actualizar sus datos.', 'success');
            return;
        }

        limpiarFormulario();
        guardarBtn.disabled = !data.ready_to_create;
        resumen.textContent = `RUT ${data.rut_normalizado} no encontrado`;
        modo.textContent = data.ready_to_create ? 'Nueva persona' : 'Sin resultados';
        setStatus(data.message, 'warning');
    }

    rutInput.addEventListener('input', () => {
        rutInput.value = formatearRut(rutInput.value);
        clearTimeout(timer);
        timer = setTimeout(buscarRut, 250);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        guardarBtn.disabled = true;
        setStatus('Guardando...');

        const payload = {
            rut: rutInput.value,
            nombre1: document.getElementById('nombre1').value,
            appaterno: document.getElementById('appaterno').value,
            apmaterno: document.getElementById('apmaterno').value,
            email: document.getElementById('email').value,
            telefono: document.getElementById('telefono').value,
            direccion: document.getElementById('direccion').value,
        };

        let response;
        let data;

        try {
            response = await fetch(`{{ route('personas-rapidas.guardar') }}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            data = await leerJsonSeguro(response);
        } catch (error) {
            guardarBtn.disabled = false;
            setStatus('No se pudo conectar con el servidor.', 'danger');
            return;
        }

        guardarBtn.disabled = false;

        if (!response.ok || !data.ok) {
            setStatus(data.message || 'No se pudo guardar.', 'danger');
            return;
        }

        cargarPersona(data.persona);
        modo.textContent = data.created ? 'Nueva persona creada' : 'Persona actualizada';
        setStatus(data.message, 'success');
    });

    </script>
</body>
</html>
