<?php
// session_start() DEBE ir primero, antes de cualquier output
session_start();

if (!isset($_SESSION['registros'])) {
    $_SESSION['registros'] = [];
}

// ── Variables de estado ──────────────────────────────────────────────────────
$total_ingresos    = 0;
$total_combustible = 0;
$ganancia_real     = 0;
$consumo_anormal   = false;
$umbral_combustible = 500;
$mensaje           = '';
$tipo_alerta       = 'info'; // 'ok' | 'warn' | 'info'  — ya no se parsea del texto
$guardado          = false;

// ── Días en español (date('l') devuelve inglés siempre) ──────────────────────
$dias_es = [
    'Monday'    => 'Lunes',
    'Tuesday'   => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday'  => 'Jueves',
    'Friday'    => 'Viernes',
    'Saturday'  => 'Sábado',
    'Sunday'    => 'Domingo',
];
$meses_es = [
    1  => 'enero', 2  => 'febrero', 3  => 'marzo',    4  => 'abril',
    5  => 'mayo',  6  => 'junio',   7  => 'julio',     8  => 'agosto',
    9  => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
];
$dia_nombre = $dias_es[date('l')] ?? date('l');
$fecha_hoy  = $dia_nombre . ', ' . date('j') . ' de ' . $meses_es[(int)date('n')] . ' de ' . date('Y');

// ── Procesamiento del formulario ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['accion']) && $_POST['accion'] === 'limpiar') {
        $_SESSION['registros'] = [];
        $mensaje     = 'Historial limpiado correctamente.';
        $tipo_alerta = 'info';

    } else {
        // Sanitizar y convertir entradas
        $ingresos_raw    = str_replace(',', '.', $_POST['ingresos']    ?? '0');
        $combustible_raw = str_replace(',', '.', $_POST['combustible'] ?? '0');
        $umbral_raw      = str_replace(',', '.', $_POST['umbral']      ?? '500');
        $descripcion     = htmlspecialchars(trim($_POST['descripcion'] ?? ''));
        $fecha           = $_POST['fecha'] ?? date('Y-m-d');

        $total_ingresos    = floatval($ingresos_raw);
        $total_combustible = floatval($combustible_raw);
        $umbral_combustible = floatval($umbral_raw) ?: 500;

        // ── Validaciones ──────────────────────────────────────────────────────
        if ($total_ingresos <= 0) {
            $mensaje     = 'Por favor ingresa un valor de ingresos mayor a 0.';
            $tipo_alerta = 'warn';
        } elseif ($total_combustible < 0) {
            $mensaje     = 'El gasto en combustible no puede ser negativo.';
            $tipo_alerta = 'warn';
        } else {
            $ganancia_real   = $total_ingresos - $total_combustible;
            $consumo_anormal = $total_combustible > $umbral_combustible;

            // min(100,...) evita desbordar la barra de proporción
            $pct_combustible = min(100, round(($total_combustible / $total_ingresos) * 100, 1));

            $_SESSION['registros'][] = [
                'fecha'       => $fecha,
                'descripcion' => $descripcion ?: 'Sin descripción',
                'ingresos'    => $total_ingresos,
                'combustible' => $total_combustible,
                'ganancia'    => $ganancia_real,
                'anormal'     => $consumo_anormal,
                'pct'         => $pct_combustible,
                'umbral'      => $umbral_combustible,
            ];

            $guardado    = true;
            $tipo_alerta = $consumo_anormal ? 'warn' : 'ok';
            $mensaje     = $consumo_anormal
                ? 'Registro guardado. Consumo de combustible por encima del umbral normal.'
                : 'Registro guardado correctamente.';
        }
    }
}

// ── Totales acumulados ───────────────────────────────────────────────────────
$hist_ingresos    = array_sum(array_column($_SESSION['registros'], 'ingresos'));
$hist_combustible = array_sum(array_column($_SESSION['registros'], 'combustible'));
$hist_ganancia    = array_sum(array_column($_SESSION['registros'], 'ganancia'));
$total_registros  = count($_SESSION['registros']);
$dias_anormales   = count(array_filter($_SESSION['registros'], fn($r) => $r['anormal']));

// ── Helper de formato Q ──────────────────────────────────────────────────────
function q(float $v): string {
    return 'Q&nbsp;' . number_format($v, 2, '.', ',');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Financiero · Natalia</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
  --bg:         #0d0f14;
  --surface:    #13161e;
  --surface2:   #1a1e29;
  --border:     #252a38;
  --gold:       #e8c96d;
  --gold-dim:   #9c8540;
  --green:      #52d68a;
  --green-dim:  #1e4a33;
  --red:        #f0614a;
  --red-dim:    #4a1e1a;
  --text:       #eceef5;
  --text-muted: #6b7490;
  --radius:     12px;
  --radius-sm:  8px;
  font-size: 15px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: 'DM Mono', monospace;
  min-height: 100vh;
  line-height: 1.6;
  background-image:
    linear-gradient(rgba(232,201,109,.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(232,201,109,.03) 1px, transparent 1px);
  background-size: 40px 40px;
}

.shell {
  max-width: 960px;
  margin: 0 auto;
  padding: 2.5rem 1.5rem 4rem;
}

/* ── Header ── */
header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 2.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--border);
}
.logo-block h1 {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  font-weight: 900;
  color: var(--gold);
  letter-spacing: -.02em;
  line-height: 1.1;
}
.logo-block p {
  font-size: .75rem;
  color: var(--text-muted);
  letter-spacing: .12em;
  text-transform: uppercase;
  margin-top: .2rem;
}
.header-date { text-align: right; font-size: .8rem; color: var(--text-muted); }
.header-date strong { display: block; font-size: 1rem; color: var(--text); }

/* ── Alerta ── */
.alert {
  padding: .9rem 1.2rem;
  border-radius: var(--radius-sm);
  font-size: .85rem;
  margin-bottom: 1.8rem;
  border-left: 3px solid;
  animation: slideIn .3s ease;
}
.alert-warn { background: var(--red-dim);   border-color: var(--red);   color: var(--red); }
.alert-ok   { background: var(--green-dim); border-color: var(--green); color: var(--green); }
.alert-info { background: var(--surface2);  border-color: var(--gold);  color: var(--gold); }
@keyframes slideIn { from { opacity:0; transform:translateY(-6px) } to { opacity:1; transform:none } }

/* ── KPI Cards ── */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}
.kpi {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.4rem 1.6rem;
  position: relative;
  overflow: hidden;
  transition: transform .2s, border-color .2s;
}
.kpi:hover { transform: translateY(-2px); border-color: var(--gold-dim); }
.kpi::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(232,201,109,.04), transparent 60%);
  pointer-events: none;
}
.kpi-label {
  font-size: .65rem;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: .5rem;
}
.kpi-value {
  font-family: 'Playfair Display', serif;
  font-size: 1.6rem;
  font-weight: 700;
  line-height: 1;
}
.kpi-value.green { color: var(--green); }
.kpi-value.gold  { color: var(--gold); }
.kpi-value.red   { color: var(--red); }
.kpi-sub { font-size: .7rem; color: var(--text-muted); margin-top: .4rem; }

.anomaly-badge {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .3rem .8rem;
  border-radius: 20px;
  font-size: .7rem;
  font-weight: 500;
  letter-spacing: .06em;
  text-transform: uppercase;
  margin-top: .7rem;
}
.anomaly-badge.warn { background: var(--red-dim);   color: var(--red); }
.anomaly-badge.ok   { background: var(--green-dim); color: var(--green); }
.pulse {
  width: 7px; height: 7px;
  border-radius: 50%;
  animation: pulse 1.6s infinite;
}
.warn .pulse { background: var(--red); }
.ok   .pulse { background: var(--green); }
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 currentColor }
  50%       { box-shadow: 0 0 0 5px transparent }
}

.ratio-bar {
  margin-top: .8rem;
  background: var(--border);
  border-radius: 4px;
  height: 5px;
  overflow: hidden;
}
.ratio-fill {
  height: 100%;
  border-radius: 4px;
  transition: width .6s ease;
}

/* ── Formulario ── */
.form-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.8rem 2rem;
  margin-bottom: 2rem;
}
.form-card h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1.1rem;
  color: var(--gold);
  margin-bottom: 1.4rem;
  padding-bottom: .8rem;
  border-bottom: 1px solid var(--border);
}
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  margin-bottom: 1.2rem;
}
label {
  display: block;
  font-size: .65rem;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: .4rem;
}
input[type=text],
input[type=number],
input[type=date] {
  width: 100%;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text);
  font-family: 'DM Mono', monospace;
  font-size: .9rem;
  padding: .65rem .9rem;
  outline: none;
  transition: border-color .2s, box-shadow .2s;
}
input:focus {
  border-color: var(--gold-dim);
  box-shadow: 0 0 0 3px rgba(232,201,109,.1);
}
.btn-row { display: flex; gap: .8rem; flex-wrap: wrap; }
.btn {
  padding: .7rem 1.6rem;
  border-radius: var(--radius-sm);
  font-family: 'DM Mono', monospace;
  font-size: .8rem;
  font-weight: 500;
  letter-spacing: .06em;
  text-transform: uppercase;
  cursor: pointer;
  border: none;
  transition: opacity .2s, transform .15s;
}
.btn:hover  { opacity: .85; transform: translateY(-1px); }
.btn:active { transform: none; }
.btn-primary { background: var(--gold); color: #0d0f14; }
.btn-danger  { background: transparent; border: 1px solid var(--red); color: var(--red); }

/* ── Totales ── */
.totals-section { margin-bottom: 2rem; }
.totals-section h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
  letter-spacing: .06em;
}
.totals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: .8rem;
}
.total-chip {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 1rem 1.2rem;
  display: flex;
  flex-direction: column;
  gap: .3rem;
}
.total-chip .tc-label { font-size: .6rem; letter-spacing: .14em; text-transform: uppercase; color: var(--text-muted); }
.total-chip .tc-val   { font-family: 'Playfair Display', serif; font-size: 1.2rem; }
.tc-val.gold  { color: var(--gold); }
.tc-val.green { color: var(--green); }
.tc-val.red   { color: var(--red); }

/* ── Historial ── */
.hist-section h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
  letter-spacing: .06em;
}
.table-wrap { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--border); }
table { width: 100%; border-collapse: collapse; font-size: .8rem; }
thead th {
  background: var(--surface2);
  padding: .75rem 1rem;
  text-align: left;
  font-size: .6rem;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: var(--surface2); }
tbody td { padding: .7rem 1rem; white-space: nowrap; }
.tag {
  display: inline-block;
  padding: .15rem .55rem;
  border-radius: 20px;
  font-size: .65rem;
  letter-spacing: .06em;
  text-transform: uppercase;
}
.tag-warn { background: var(--red-dim);   color: var(--red); }
.tag-ok   { background: var(--green-dim); color: var(--green); }
.empty-row td { text-align: center; color: var(--text-muted); padding: 2rem; font-size: .8rem; }

/* ── Responsive ── */
@media (max-width: 520px) {
  .logo-block h1 { font-size: 1.4rem; }
  .form-card { padding: 1.2rem 1rem; }
  .btn-row { flex-direction: column; }
  .btn { text-align: center; }
}
</style>
</head>
<body>
<div class="shell">

  <!-- ── HEADER ─────────────────────────────────────────── -->
  <header>
    <div class="logo-block">
      <h1>Natalia</h1>
      <p>Dashboard Financiero</p>
    </div>
    <div class="header-date">
      <strong><?= date('d/m/Y') ?></strong>
      <?= $fecha_hoy ?>
    </div>
  </header>

  <!-- ── ALERTA ─────────────────────────────────────────── -->
  <?php if ($mensaje): ?>
    <div class="alert alert-<?= htmlspecialchars($tipo_alerta) ?>">
      <?= htmlspecialchars($mensaje) ?>
    </div>
  <?php endif; ?>

  <!-- ── KPIs (solo tras guardar) ───────────────────────── -->
  <?php if ($guardado): ?>
    <?php
      $pct        = min(100, round(($total_combustible / max($total_ingresos, 0.01)) * 100, 1));
      $fill_color = $consumo_anormal ? 'var(--red)' : 'var(--green)';
    ?>
    <div class="kpi-grid">

      <div class="kpi">
        <div class="kpi-label">Total ingresos del día</div>
        <div class="kpi-value gold"><?= q($total_ingresos) ?></div>
        <div class="kpi-sub">Ingreso bruto registrado</div>
      </div>

      <div class="kpi">
        <div class="kpi-label">Gasto combustible</div>
        <div class="kpi-value <?= $consumo_anormal ? 'red' : 'gold' ?>"><?= q($total_combustible) ?></div>
        <div class="kpi-sub">Umbral: <?= q($umbral_combustible) ?></div>
        <div class="ratio-bar">
          <div class="ratio-fill" style="width:<?= $pct ?>%; background:<?= $fill_color ?>;"></div>
        </div>
        <div class="kpi-sub"><?= $pct ?>% del ingreso</div>
        <div class="anomaly-badge <?= $consumo_anormal ? 'warn' : 'ok' ?>">
          <span class="pulse"></span>
          <?= $consumo_anormal ? 'Consumo anormal' : 'Consumo normal' ?>
        </div>
      </div>

      <div class="kpi">
        <div class="kpi-label">Ganancia real</div>
        <div class="kpi-value <?= $ganancia_real >= 0 ? 'green' : 'red' ?>"><?= q($ganancia_real) ?></div>
        <div class="kpi-sub">Ingresos − Combustible</div>
      </div>

    </div>
  <?php endif; ?>

  <!-- ── FORMULARIO ─────────────────────────────────────── -->
  <div class="form-card">
    <h2>▸ Nuevo registro del día</h2>
    <form method="POST" action="">
      <div class="form-grid">
        <div>
          <label for="fecha">Fecha</label>
          <input type="date" id="fecha" name="fecha"
                 value="<?= htmlspecialchars($_POST['fecha'] ?? date('Y-m-d')) ?>">
        </div>
        <div>
          <label for="ingresos">Ingresos del día (Q)</label>
          <input type="number" id="ingresos" name="ingresos" min="0" step="0.01"
                 placeholder="0.00"
                 value="<?= htmlspecialchars($_POST['ingresos'] ?? '') ?>">
        </div>
        <div>
          <label for="combustible">Gasto combustible (Q)</label>
          <input type="number" id="combustible" name="combustible" min="0" step="0.01"
                 placeholder="0.00"
                 value="<?= htmlspecialchars($_POST['combustible'] ?? '') ?>">
        </div>
        <div>
          <label for="umbral">Umbral normal combustible (Q)</label>
          <input type="number" id="umbral" name="umbral" min="0" step="0.01"
                 placeholder="500.00"
                 value="<?= htmlspecialchars($_POST['umbral'] ?? '500') ?>">
        </div>
      </div>
      <div style="margin-bottom:1rem;">
        <label for="descripcion">Descripción / Nota</label>
        <input type="text" id="descripcion" name="descripcion"
               placeholder="Ej. Ruta norte, vehículo #3…"
               value="<?= htmlspecialchars($_POST['descripcion'] ?? '') ?>">
      </div>
      <div class="btn-row">
        <button type="submit" class="btn btn-primary">Guardar registro</button>
      </div>
    </form>
  </div>

  <!-- ── TOTALES ACUMULADOS ─────────────────────────────── -->
  <?php if ($total_registros > 0): ?>
  <div class="totals-section">
    <h2>Acumulado de la sesión (<?= $total_registros ?> registro<?= $total_registros !== 1 ? 's' : '' ?>)</h2>
    <div class="totals-grid">
      <div class="total-chip">
        <span class="tc-label">Total ingresos</span>
        <span class="tc-val gold"><?= q($hist_ingresos) ?></span>
      </div>
      <div class="total-chip">
        <span class="tc-label">Total combustible</span>
        <span class="tc-val red"><?= q($hist_combustible) ?></span>
      </div>
      <div class="total-chip">
        <span class="tc-label">Ganancia total</span>
        <span class="tc-val <?= $hist_ganancia >= 0 ? 'green' : 'red' ?>"><?= q($hist_ganancia) ?></span>
      </div>
      <div class="total-chip">
        <span class="tc-label">Días con consumo anormal</span>
        <span class="tc-val <?= $dias_anormales > 0 ? 'red' : 'green' ?>"><?= $dias_anormales ?> / <?= $total_registros ?></span>
      </div>
    </div>
  </div>

  <!-- ── HISTORIAL ──────────────────────────────────────── -->
  <div class="hist-section">
    <h2>Historial de registros</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Descripción</th>
            <th>Ingresos</th>
            <th>Combustible</th>
            <th>% Ingreso</th>
            <th>Ganancia</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php
            $registros_rev = array_reverse($_SESSION['registros'], true);
            $n = count($_SESSION['registros']);
            foreach ($registros_rev as $i => $r):
              $pos = $n - $i;
          ?>
          <tr>
            <td style="color:var(--text-muted)"><?= $pos ?></td>
            <td><?= htmlspecialchars($r['fecha']) ?></td>
            <td style="color:var(--text-muted);font-size:.75rem;"><?= htmlspecialchars($r['descripcion']) ?></td>
            <td style="color:var(--gold)"><?= q($r['ingresos']) ?></td>
            <td style="color:<?= $r['anormal'] ? 'var(--red)' : 'var(--text)' ?>"><?= q($r['combustible']) ?></td>
            <td style="color:var(--text-muted)"><?= $r['pct'] ?>%</td>
            <td style="color:<?= $r['ganancia'] >= 0 ? 'var(--green)' : 'var(--red)' ?>"><?= q($r['ganancia']) ?></td>
            <td>
              <span class="tag <?= $r['anormal'] ? 'tag-warn' : 'tag-ok' ?>">
                <?= $r['anormal'] ? 'Anormal' : 'Normal' ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <form method="POST" style="margin-top:1rem;">
      <input type="hidden" name="accion" value="limpiar">
      <button type="submit" class="btn btn-danger"
              onclick="return confirm('¿Limpiar todo el historial de la sesión?')">
        Limpiar historial
      </button>
    </form>
  </div>

  <?php else: ?>
    <div class="table-wrap" style="border:1px solid var(--border);border-radius:var(--radius);">
      <table><tbody>
        <tr class="empty-row">
          <td>Aún no hay registros. Ingresa el primero arriba ↑</td>
        </tr>
      </tbody></table>
    </div>
  <?php endif; ?>

</div><!-- /shell -->
</body>
</html>