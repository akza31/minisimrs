<?php
$sessionPath = __DIR__ . '/data/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);
session_start();

date_default_timezone_set('Asia/Jakarta');

$dataFile = __DIR__ . '/data/store.json';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value)
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function seed_data()
{
    return [
        'users' => [
            [
                'id' => 1,
                'nama' => 'Administrator',
                'username' => 'admin',
                'role' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
            ],
        ],
        'pasien' => [
            ['id' => 1, 'no_rm' => 'RM-000001', 'nama' => 'Siti Aminah', 'nik' => '3276010101800001', 'gender' => 'Perempuan', 'tgl_lahir' => '1980-01-01', 'alamat' => 'Jl. Melati No. 12', 'no_bpjs' => '0001234567890'],
            ['id' => 2, 'no_rm' => 'RM-000002', 'nama' => 'Budi Santoso', 'nik' => '3276020202900002', 'gender' => 'Laki-laki', 'tgl_lahir' => '1990-02-02', 'alamat' => 'Jl. Kenanga No. 7', 'no_bpjs' => '0009876543210'],
        ],
        'dokter' => [
            ['id' => 1, 'nama' => 'dr. Raka Pratama', 'spesialis' => 'Umum', 'sip' => 'SIP-UM-001', 'telepon' => '081234567890'],
            ['id' => 2, 'nama' => 'dr. Maya Lestari, Sp.PD', 'spesialis' => 'Penyakit Dalam', 'sip' => 'SIP-PD-002', 'telepon' => '081298765432'],
        ],
        'poli' => [
            ['id' => 1, 'nama' => 'Poli Umum', 'lantai' => '1', 'status' => 'Aktif'],
            ['id' => 2, 'nama' => 'Poli Penyakit Dalam', 'lantai' => '2', 'status' => 'Aktif'],
        ],
        'bangsal' => [
            ['id' => 1, 'nama' => 'Mawar', 'kelas' => 'Kelas 1', 'penanggung_jawab' => 'Ns. Lina'],
            ['id' => 2, 'nama' => 'Anggrek', 'kelas' => 'Kelas 2', 'penanggung_jawab' => 'Ns. Putri'],
        ],
        'tempat_tidur' => [
            ['id' => 1, 'bangsal' => 'Mawar', 'nomor' => 'MW-01', 'kelas' => 'Kelas 1', 'status' => 'Tersedia'],
            ['id' => 2, 'bangsal' => 'Mawar', 'nomor' => 'MW-02', 'kelas' => 'Kelas 1', 'status' => 'Terisi'],
            ['id' => 3, 'bangsal' => 'Anggrek', 'nomor' => 'AG-01', 'kelas' => 'Kelas 2', 'status' => 'Tersedia'],
        ],
        'tarif' => [
            ['id' => 1, 'nama' => 'Konsultasi Dokter Umum', 'kategori' => 'Rawat Jalan', 'harga' => 75000],
            ['id' => 2, 'nama' => 'Pemeriksaan Darah Lengkap', 'kategori' => 'Laborat', 'harga' => 125000],
            ['id' => 3, 'nama' => 'Foto Thorax', 'kategori' => 'Radiologi', 'harga' => 180000],
        ],
        'obat' => [
            ['id' => 1, 'nama' => 'Paracetamol 500 mg', 'satuan' => 'Tablet', 'stok' => 240, 'harga' => 1200],
            ['id' => 2, 'nama' => 'Amoxicillin 500 mg', 'satuan' => 'Kapsul', 'stok' => 120, 'harga' => 2500],
        ],
        'pendaftaran' => [],
        'layanan' => [],
        'billing' => [],
        'kasir' => [],
        'penjualan_obat' => [],
        'sep_checks' => [],
    ];
}

function db_load()
{
    global $dataFile;
    if (!file_exists($dataFile)) {
        $seed = seed_data();
        file_put_contents($dataFile, json_encode($seed, JSON_PRETTY_PRINT));
        return $seed;
    }

    $data = json_decode(file_get_contents($dataFile), true);
    if (!is_array($data)) {
        $data = seed_data();
    }

    foreach (seed_data() as $key => $value) {
        if (!array_key_exists($key, $data)) {
            $data[$key] = $value;
        }
    }
    return $data;
}

function db_save($data)
{
    global $dataFile;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
}

function next_id($rows)
{
    $max = 0;
    foreach ($rows as $row) {
        $max = max($max, (int) ($row['id'] ?? 0));
    }
    return $max + 1;
}

function find_row($rows, $id)
{
    foreach ($rows as $row) {
        if ((int) $row['id'] === (int) $id) {
            return $row;
        }
    }
    return null;
}

function label_by_id($rows, $id, $field = 'nama')
{
    $row = find_row($rows, $id);
    return $row ? ($row[$field] ?? '-') : '-';
}

function upsert_row(&$rows, $row)
{
    if (!empty($row['id'])) {
        foreach ($rows as $index => $existing) {
            if ((int) $existing['id'] === (int) $row['id']) {
                $rows[$index] = array_merge($existing, $row);
                return $row['id'];
            }
        }
    }

    $row['id'] = next_id($rows);
    $rows[] = $row;
    return $row['id'];
}

function delete_row(&$rows, $id)
{
    $rows = array_values(array_filter($rows, fn($row) => (int) $row['id'] !== (int) $id));
}

function post($key, $default = '')
{
    return trim((string) ($_POST[$key] ?? $default));
}

function require_login()
{
    if (empty($_SESSION['user'])) {
        header('Location: ?m=login');
        exit;
    }
}

$db = db_load();
$module = $_GET['m'] ?? (empty($_SESSION['user']) ? 'login' : 'dashboard');
$action = $_GET['a'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($module === 'login') {
        $username = post('username');
        $password = post('password');
        foreach ($db['users'] as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                $_SESSION['user'] = ['id' => $user['id'], 'nama' => $user['nama'], 'username' => $user['username'], 'role' => $user['role']];
                header('Location: ?m=dashboard');
                exit;
            }
        }
        $message = 'Username atau password tidak sesuai.';
    } else {
        require_login();

        if ($action === 'save_user') {
            $row = [
                'id' => post('id'),
                'nama' => post('nama'),
                'username' => post('username'),
                'role' => post('role', 'petugas'),
            ];
            $existing = $row['id'] ? find_row($db['users'], $row['id']) : null;
            $row['password'] = post('password') !== '' ? password_hash(post('password'), PASSWORD_DEFAULT) : ($existing['password'] ?? password_hash('user123', PASSWORD_DEFAULT));
            upsert_row($db['users'], $row);
            db_save($db);
            header('Location: ?m=users&saved=1');
            exit;
        }

        if ($action === 'save_pasien') {
            $row = [
                'id' => post('id'),
                'no_rm' => post('no_rm') ?: 'RM-' . str_pad((string) next_id($db['pasien']), 6, '0', STR_PAD_LEFT),
                'nama' => post('nama'),
                'nik' => post('nik'),
                'gender' => post('gender'),
                'tgl_lahir' => post('tgl_lahir'),
                'alamat' => post('alamat'),
                'no_bpjs' => post('no_bpjs'),
            ];
            upsert_row($db['pasien'], $row);
            db_save($db);
            header('Location: ?m=pasien&saved=1');
            exit;
        }

        if ($action === 'save_dokter') {
            upsert_row($db['dokter'], ['id' => post('id'), 'nama' => post('nama'), 'spesialis' => post('spesialis'), 'sip' => post('sip'), 'telepon' => post('telepon')]);
            db_save($db);
            header('Location: ?m=dokter&saved=1');
            exit;
        }

        if ($action === 'save_poli') {
            upsert_row($db['poli'], ['id' => post('id'), 'nama' => post('nama'), 'lantai' => post('lantai'), 'status' => post('status')]);
            db_save($db);
            header('Location: ?m=poli_master&saved=1');
            exit;
        }

        if ($action === 'save_bangsal') {
            upsert_row($db['bangsal'], ['id' => post('id'), 'nama' => post('nama'), 'kelas' => post('kelas'), 'penanggung_jawab' => post('penanggung_jawab')]);
            db_save($db);
            header('Location: ?m=bangsal&saved=1');
            exit;
        }

        if ($action === 'save_bed') {
            upsert_row($db['tempat_tidur'], ['id' => post('id'), 'bangsal' => post('bangsal'), 'nomor' => post('nomor'), 'kelas' => post('kelas'), 'status' => post('status')]);
            db_save($db);
            header('Location: ?m=tempat_tidur&saved=1');
            exit;
        }

        if ($action === 'save_tarif') {
            upsert_row($db['tarif'], ['id' => post('id'), 'nama' => post('nama'), 'kategori' => post('kategori'), 'harga' => (float) post('harga')]);
            db_save($db);
            header('Location: ?m=tarif&saved=1');
            exit;
        }

        if ($action === 'save_obat') {
            upsert_row($db['obat'], ['id' => post('id'), 'nama' => post('nama'), 'satuan' => post('satuan'), 'stok' => (int) post('stok'), 'harga' => (float) post('harga')]);
            db_save($db);
            header('Location: ?m=obat&saved=1');
            exit;
        }

        if ($action === 'save_daftar') {
            upsert_row($db['pendaftaran'], [
                'id' => post('id'),
                'tanggal' => post('tanggal') ?: date('Y-m-d'),
                'jenis' => post('jenis'),
                'pasien_id' => (int) post('pasien_id'),
                'poli_id' => (int) post('poli_id'),
                'dokter_id' => (int) post('dokter_id'),
                'bangsal_id' => (int) post('bangsal_id'),
                'keluhan' => post('keluhan'),
                'status' => post('status') ?: 'Terdaftar',
            ]);
            db_save($db);
            header('Location: ?m=' . urlencode(post('redirect', 'daftar_baru')) . '&saved=1');
            exit;
        }

        if ($action === 'save_sep') {
            $valid = post('no_kartu') !== '' && post('no_rujukan') !== '';
            upsert_row($db['sep_checks'], [
                'id' => '',
                'tanggal' => date('Y-m-d H:i:s'),
                'pasien_id' => (int) post('pasien_id'),
                'no_kartu' => post('no_kartu'),
                'no_rujukan' => post('no_rujukan'),
                'tujuan' => post('tujuan'),
                'hasil' => $valid ? 'Layak dibuat SEP' : 'Data belum lengkap',
            ]);
            db_save($db);
            header('Location: ?m=cek_sep&saved=1');
            exit;
        }

        if ($action === 'save_layanan') {
            upsert_row($db['layanan'], [
                'id' => post('id'),
                'tanggal' => post('tanggal') ?: date('Y-m-d'),
                'unit' => post('unit'),
                'pendaftaran_id' => (int) post('pendaftaran_id'),
                'dokter_id' => (int) post('dokter_id'),
                'diagnosa' => post('diagnosa'),
                'tindakan' => post('tindakan'),
                'catatan' => post('catatan'),
            ]);
            db_save($db);
            header('Location: ?m=' . urlencode(post('redirect', 'layanan_poli')) . '&saved=1');
            exit;
        }

        if ($action === 'save_billing') {
            $tarif = find_row($db['tarif'], post('tarif_id'));
            $qty = max(1, (int) post('qty'));
            $harga = (float) ($tarif['harga'] ?? 0);
            upsert_row($db['billing'], [
                'id' => post('id'),
                'tanggal' => post('tanggal') ?: date('Y-m-d'),
                'pendaftaran_id' => (int) post('pendaftaran_id'),
                'tarif_id' => (int) post('tarif_id'),
                'qty' => $qty,
                'harga' => $harga,
                'total' => $qty * $harga,
                'status' => post('status') ?: 'Belum Lunas',
            ]);
            db_save($db);
            header('Location: ?m=billing&saved=1');
            exit;
        }

        if ($action === 'save_kasir') {
            $total = (float) post('total');
            $bayar = (float) post('bayar');
            upsert_row($db['kasir'], [
                'id' => post('id'),
                'tanggal' => post('tanggal') ?: date('Y-m-d'),
                'jenis' => post('jenis'),
                'pasien_id' => (int) post('pasien_id'),
                'total' => $total,
                'bayar' => $bayar,
                'kembalian' => max(0, $bayar - $total),
                'status' => $bayar >= $total ? 'Lunas' : 'Kurang Bayar',
            ]);
            db_save($db);
            header('Location: ?m=' . urlencode(post('redirect', 'kasir_rj')) . '&saved=1');
            exit;
        }

        if ($action === 'save_penjualan_obat') {
            $obat = find_row($db['obat'], post('obat_id'));
            $qty = max(1, (int) post('qty'));
            $harga = (float) ($obat['harga'] ?? 0);
            upsert_row($db['penjualan_obat'], [
                'id' => post('id'),
                'tanggal' => post('tanggal') ?: date('Y-m-d'),
                'pasien_id' => (int) post('pasien_id'),
                'obat_id' => (int) post('obat_id'),
                'qty' => $qty,
                'harga' => $harga,
                'total' => $qty * $harga,
            ]);
            foreach ($db['obat'] as &$item) {
                if ((int) $item['id'] === (int) post('obat_id')) {
                    $item['stok'] = max(0, (int) $item['stok'] - $qty);
                }
            }
            unset($item);
            db_save($db);
            header('Location: ?m=transaksi_obat&saved=1');
            exit;
        }
    }
}

if ($module === 'logout') {
    session_destroy();
    header('Location: ?m=login');
    exit;
}

if ($action === 'delete') {
    require_login();
    $map = [
        'users' => 'users',
        'pasien' => 'pasien',
        'dokter' => 'dokter',
        'poli_master' => 'poli',
        'bangsal' => 'bangsal',
        'tempat_tidur' => 'tempat_tidur',
        'tarif' => 'tarif',
        'obat' => 'obat',
        'billing' => 'billing',
        'transaksi_obat' => 'penjualan_obat',
    ];
    if (isset($map[$module])) {
        delete_row($db[$map[$module]], $_GET['id'] ?? 0);
        db_save($db);
    }
    header('Location: ?m=' . urlencode($module) . '&deleted=1');
    exit;
}

function page_title($module)
{
    $titles = [
        'dashboard' => 'Dashboard Kunjungan Pasien',
        'users' => 'Master User',
        'pasien' => 'Master Pasien',
        'dokter' => 'Master Dokter',
        'poli_master' => 'Master Poli',
        'bangsal' => 'Master Bangsal',
        'tempat_tidur' => 'Ketersediaan Tempat Tidur',
        'daftar_baru' => 'Pendaftaran Pasien Baru',
        'daftar_poli' => 'Pendaftaran Poli',
        'daftar_inap' => 'Pendaftaran Rawat Inap',
        'daftar_ugd' => 'Pendaftaran UGD',
        'cek_sep' => 'Cek SEP BPJS',
        'layanan_poli' => 'Menu Poli',
        'layanan_ugd' => 'Menu UGD',
        'layanan_inap' => 'Menu Rawat Inap',
        'laborat' => 'Laborat',
        'radiologi' => 'Radiologi',
        'billing' => 'Transaksi Billing',
        'tarif' => 'Master Tarif',
        'laporan_billing' => 'Laporan Billing',
        'kasir_rj' => 'Kasir Rawat Jalan',
        'kasir_ugd' => 'Kasir UGD',
        'kasir_ri' => 'Kasir Rawat Inap',
        'transaksi_obat' => 'Transaksi Obat',
        'obat' => 'Master Obat',
        'laporan_obat' => 'Laporan Penjualan Obat',
    ];
    return $titles[$module] ?? 'Mini SIMRS';
}

function active($name, $module)
{
    return $name === $module ? 'active' : '';
}

function options($rows, $selected = '', $label = 'nama')
{
    $html = '<option value="">Pilih data</option>';
    foreach ($rows as $row) {
        $isSelected = (string) ($row['id'] ?? '') === (string) $selected ? ' selected' : '';
        $html .= '<option value="' . e($row['id']) . '"' . $isSelected . '>' . e($row[$label] ?? '-') . '</option>';
    }
    return $html;
}

function selected($value, $selected)
{
    return (string) $value === (string) $selected ? ' selected' : '';
}

function current_edit($rows)
{
    return !empty($_GET['edit']) ? find_row($rows, $_GET['edit']) : [];
}

function pendaftaran_label($db, $id)
{
    $reg = find_row($db['pendaftaran'], $id);
    if (!$reg) {
        return '-';
    }
    return '#' . $reg['id'] . ' - ' . label_by_id($db['pasien'], $reg['pasien_id']) . ' (' . $reg['jenis'] . ')';
}

function flash()
{
    if (!empty($_GET['saved'])) {
        return '<div class="alert alert-success">Data berhasil disimpan.</div>';
    }
    if (!empty($_GET['deleted'])) {
        return '<div class="alert alert-warning">Data berhasil dihapus.</div>';
    }
    return '';
}

function render_table($headers, $rows, $module, $formatter = null, $deletable = true)
{
    echo '<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr>';
    foreach ($headers as $header => $label) {
        echo '<th>' . e($label) . '</th>';
    }
    echo '<th class="text-end">Aksi</th></tr></thead><tbody>';
    if (!$rows) {
        echo '<tr><td colspan="' . (count($headers) + 1) . '" class="text-center text-muted py-4">Belum ada data.</td></tr>';
    }
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($headers as $key => $label) {
            $value = $formatter ? $formatter($key, $row) : ($row[$key] ?? '');
            echo '<td>' . $value . '</td>';
        }
        echo '<td class="text-end table-actions"><a class="btn btn-sm btn-light" href="?m=' . e($module) . '&edit=' . e($row['id']) . '">Edit</a>';
        if ($deletable) {
            echo '<a class="btn btn-sm btn-outline-danger" data-confirm="Hapus data ini?" href="?m=' . e($module) . '&a=delete&id=' . e($row['id']) . '">Hapus</a>';
        }
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
}

if ($module !== 'login') {
    require_login();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(page_title($module)); ?> - Mini SIMRS</title>
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=4">
</head>
<body>
<?php if ($module === 'login'): ?>
    <main class="login-shell">
        <section class="login-panel">
            <div>
                <span class="brand-mark">RS</span>
                <h1>Mini SIMRS</h1>
                <p>Sistem sederhana untuk pendaftaran, layanan, billing, kasir, farmasi, dan monitoring kunjungan pasien.</p>
            </div>
            <form method="post" class="login-card">
                <h2>Masuk</h2>
                <?php if ($message): ?><div class="alert alert-danger"><?= e($message); ?></div><?php endif; ?>
                <label class="form-label">Username</label>
                <input class="form-control" name="username" value="admin" required>
                <label class="form-label mt-3">Password</label>
                <input class="form-control" name="password" type="password" value="admin123" required>
                <button class="btn btn-primary w-100 mt-4" type="submit">Login</button>
                <p class="hint">Default: admin / admin123</p>
            </form>
        </section>
    </main>
<?php else: ?>
    <div class="app-shell" id="appShell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-head">
                <a class="app-brand" href="?m=dashboard"><span>RS</span><strong>Mini SIMRS</strong></a>
                <button class="sidebar-close" id="sidebarClose" type="button">Hide</button>
            </div>
            <nav class="slide-nav">
                <?php $mainOpen = in_array($module, ['dashboard', 'users'], true); ?>
                <div class="nav-group <?= $mainOpen ? 'open' : ''; ?>" data-nav-group="utama">
                    <button class="nav-toggle" type="button" aria-expanded="<?= $mainOpen ? 'true' : 'false'; ?>">
                        <span>Utama</span><span class="nav-chevron">&gt;</span>
                    </button>
                    <div class="nav-panel">
                        <a class="<?= active('dashboard', $module); ?>" href="?m=dashboard">Dashboard</a>
                        <a class="<?= active('users', $module); ?>" href="?m=users">Master User</a>
                    </div>
                </div>

                <?php $regOpen = in_array($module, ['daftar_baru', 'daftar_poli', 'daftar_inap', 'daftar_ugd', 'pasien', 'dokter', 'poli_master', 'bangsal', 'tempat_tidur', 'cek_sep'], true); ?>
                <div class="nav-group <?= $regOpen ? 'open' : ''; ?>" data-nav-group="pendaftaran">
                    <button class="nav-toggle" type="button" aria-expanded="<?= $regOpen ? 'true' : 'false'; ?>">
                        <span>Pendaftaran</span><span class="nav-chevron">&gt;</span>
                    </button>
                    <div class="nav-panel">
                        <a class="<?= active('daftar_baru', $module); ?>" href="?m=daftar_baru">Daftar Baru</a>
                        <a class="<?= active('daftar_poli', $module); ?>" href="?m=daftar_poli">Daftar Poli</a>
                        <a class="<?= active('daftar_inap', $module); ?>" href="?m=daftar_inap">Daftar Inap</a>
                        <a class="<?= active('daftar_ugd', $module); ?>" href="?m=daftar_ugd">Daftar UGD</a>
                        <a class="<?= active('pasien', $module); ?>" href="?m=pasien">Master Pasien</a>
                        <a class="<?= active('dokter', $module); ?>" href="?m=dokter">Master Dokter</a>
                        <a class="<?= active('poli_master', $module); ?>" href="?m=poli_master">Master Poli</a>
                        <a class="<?= active('bangsal', $module); ?>" href="?m=bangsal">Master Bangsal</a>
                        <a class="<?= active('tempat_tidur', $module); ?>" href="?m=tempat_tidur">Tempat Tidur</a>
                        <a class="<?= active('cek_sep', $module); ?>" href="?m=cek_sep">Cek SEP BPJS</a>
                    </div>
                </div>

                <?php $serviceOpen = in_array($module, ['layanan_poli', 'layanan_ugd', 'layanan_inap', 'laborat', 'radiologi'], true); ?>
                <div class="nav-group <?= $serviceOpen ? 'open' : ''; ?>" data-nav-group="pelayanan">
                    <button class="nav-toggle" type="button" aria-expanded="<?= $serviceOpen ? 'true' : 'false'; ?>">
                        <span>Pelayanan</span><span class="nav-chevron">&gt;</span>
                    </button>
                    <div class="nav-panel">
                        <a class="<?= active('layanan_poli', $module); ?>" href="?m=layanan_poli">Poli</a>
                        <a class="<?= active('layanan_ugd', $module); ?>" href="?m=layanan_ugd">UGD</a>
                        <a class="<?= active('layanan_inap', $module); ?>" href="?m=layanan_inap">Rawat Inap</a>
                        <a class="<?= active('laborat', $module); ?>" href="?m=laborat">Laborat</a>
                        <a class="<?= active('radiologi', $module); ?>" href="?m=radiologi">Radiologi</a>
                    </div>
                </div>

                <?php $financeOpen = in_array($module, ['billing', 'tarif', 'laporan_billing', 'kasir_rj', 'kasir_ugd', 'kasir_ri'], true); ?>
                <div class="nav-group <?= $financeOpen ? 'open' : ''; ?>" data-nav-group="keuangan">
                    <button class="nav-toggle" type="button" aria-expanded="<?= $financeOpen ? 'true' : 'false'; ?>">
                        <span>Billing & Keuangan</span><span class="nav-chevron">&gt;</span>
                    </button>
                    <div class="nav-panel">
                        <a class="<?= active('billing', $module); ?>" href="?m=billing">Transaksi Billing</a>
                        <a class="<?= active('tarif', $module); ?>" href="?m=tarif">Master Tarif</a>
                        <a class="<?= active('laporan_billing', $module); ?>" href="?m=laporan_billing">Laporan Billing</a>
                        <a class="<?= active('kasir_rj', $module); ?>" href="?m=kasir_rj">Kasir RJ</a>
                        <a class="<?= active('kasir_ugd', $module); ?>" href="?m=kasir_ugd">Kasir UGD</a>
                        <a class="<?= active('kasir_ri', $module); ?>" href="?m=kasir_ri">Kasir RI</a>
                    </div>
                </div>

                <?php $pharmacyOpen = in_array($module, ['transaksi_obat', 'obat', 'laporan_obat'], true); ?>
                <div class="nav-group <?= $pharmacyOpen ? 'open' : ''; ?>" data-nav-group="farmasi">
                    <button class="nav-toggle" type="button" aria-expanded="<?= $pharmacyOpen ? 'true' : 'false'; ?>">
                        <span>Farmasi</span><span class="nav-chevron">&gt;</span>
                    </button>
                    <div class="nav-panel">
                        <a class="<?= active('transaksi_obat', $module); ?>" href="?m=transaksi_obat">Transaksi Obat</a>
                        <a class="<?= active('obat', $module); ?>" href="?m=obat">Master Obat</a>
                        <a class="<?= active('laporan_obat', $module); ?>" href="?m=laporan_obat">Laporan Obat</a>
                    </div>
                </div>
            </nav>
        </aside>
        <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Tutup menu"></button>

        <main class="content">
            <header class="topbar">
                <button class="btn btn-light icon-btn" id="sidebarToggle" type="button">Menu</button>
                <div>
                    <h1><?= e(page_title($module)); ?></h1>
                    <span><?= e(date('d M Y H:i')); ?> WIB</span>
                </div>
                <div class="user-chip">
                    <span><?= e($_SESSION['user']['nama']); ?></span>
                    <a href="?m=logout">Logout</a>
                </div>
            </header>

            <?= flash(); ?>

            <?php if ($module === 'dashboard'): ?>
                <?php
                $today = date('Y-m-d');
                $todayVisits = array_filter($db['pendaftaran'], fn($row) => ($row['tanggal'] ?? '') === $today);
                $bedsAvailable = count(array_filter($db['tempat_tidur'], fn($row) => ($row['status'] ?? '') === 'Tersedia'));
                $billingTotal = array_sum(array_map(fn($row) => (float) ($row['total'] ?? 0), $db['billing']));
                $drugSales = array_sum(array_map(fn($row) => (float) ($row['total'] ?? 0), $db['penjualan_obat']));
                $visitMap = ['Poli' => 0, 'Rawat Inap' => 0, 'UGD' => 0, 'Pasien Baru' => 0];
                foreach ($db['pendaftaran'] as $row) {
                    $visitMap[$row['jenis'] ?? 'Poli'] = ($visitMap[$row['jenis'] ?? 'Poli'] ?? 0) + 1;
                }
                ?>
                <section class="stats-grid">
                    <div class="stat-card"><span>Kunjungan Hari Ini</span><strong><?= count($todayVisits); ?></strong></div>
                    <div class="stat-card"><span>Total Pasien</span><strong><?= count($db['pasien']); ?></strong></div>
                    <div class="stat-card"><span>TT Tersedia</span><strong><?= $bedsAvailable; ?></strong></div>
                    <div class="stat-card"><span>Total Billing</span><strong><?= rupiah($billingTotal + $drugSales); ?></strong></div>
                </section>
                <section class="grid-2">
                    <div class="panel">
                        <div class="panel-heading"><h2>Grafik Kunjungan</h2></div>
                        <canvas id="visitChart" height="220" data-labels='<?= e(json_encode(array_keys($visitMap))); ?>' data-values='<?= e(json_encode(array_values($visitMap))); ?>'></canvas>
                    </div>
                    <div class="panel">
                        <div class="panel-heading"><h2>Pendaftaran Terbaru</h2></div>
                        <?php
                        $latest = array_slice(array_reverse($db['pendaftaran']), 0, 6);
                        render_table(['tanggal' => 'Tanggal', 'pasien_id' => 'Pasien', 'jenis' => 'Jenis', 'status' => 'Status'], $latest, 'dashboard', function ($key, $row) use ($db) {
                            if ($key === 'pasien_id') return e(label_by_id($db['pasien'], $row['pasien_id']));
                            return e($row[$key] ?? '');
                        }, false);
                        ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($module === 'users'): ?>
                <?php $edit = current_edit($db['users']); ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Master User</h2></div>
                    <form method="post" action="?m=users&a=save_user" class="form-grid">
                        <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                        <div><label class="form-label">Nama</label><input class="form-control" name="nama" value="<?= e($edit['nama'] ?? ''); ?>" required></div>
                        <div><label class="form-label">Username</label><input class="form-control" name="username" value="<?= e($edit['username'] ?? ''); ?>" required></div>
                        <div><label class="form-label">Role</label><select class="form-select" name="role"><option<?= selected('admin', $edit['role'] ?? ''); ?>>admin</option><option<?= selected('petugas', $edit['role'] ?? ''); ?>>petugas</option><option<?= selected('kasir', $edit['role'] ?? ''); ?>>kasir</option></select></div>
                        <div><label class="form-label">Password</label><input class="form-control" name="password" type="password" placeholder="<?= $edit ? 'Kosongkan jika tidak diganti' : 'Minimal isi password'; ?>"></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan User</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['nama' => 'Nama', 'username' => 'Username', 'role' => 'Role'], $db['users'], 'users'); ?></section>
            <?php endif; ?>

            <?php if ($module === 'pasien'): ?>
                <?php $edit = current_edit($db['pasien']); ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Pasien</h2></div>
                    <form method="post" action="?m=pasien&a=save_pasien" class="form-grid">
                        <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                        <div><label class="form-label">No RM</label><input class="form-control" name="no_rm" value="<?= e($edit['no_rm'] ?? ''); ?>" placeholder="Otomatis jika kosong"></div>
                        <div><label class="form-label">Nama</label><input class="form-control" name="nama" value="<?= e($edit['nama'] ?? ''); ?>" required></div>
                        <div><label class="form-label">NIK</label><input class="form-control" name="nik" value="<?= e($edit['nik'] ?? ''); ?>"></div>
                        <div><label class="form-label">Gender</label><select class="form-select" name="gender"><option<?= selected('Laki-laki', $edit['gender'] ?? ''); ?>>Laki-laki</option><option<?= selected('Perempuan', $edit['gender'] ?? ''); ?>>Perempuan</option></select></div>
                        <div><label class="form-label">Tanggal Lahir</label><input class="form-control" type="date" name="tgl_lahir" value="<?= e($edit['tgl_lahir'] ?? ''); ?>"></div>
                        <div><label class="form-label">No BPJS</label><input class="form-control" name="no_bpjs" value="<?= e($edit['no_bpjs'] ?? ''); ?>"></div>
                        <div class="span-2"><label class="form-label">Alamat</label><textarea class="form-control" name="alamat"><?= e($edit['alamat'] ?? ''); ?></textarea></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Pasien</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['no_rm' => 'No RM', 'nama' => 'Nama', 'nik' => 'NIK', 'gender' => 'Gender', 'no_bpjs' => 'BPJS'], $db['pasien'], 'pasien'); ?></section>
            <?php endif; ?>

            <?php if ($module === 'dokter'): ?>
                <?php $edit = current_edit($db['dokter']); ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Dokter</h2></div>
                    <form method="post" action="?m=dokter&a=save_dokter" class="form-grid">
                        <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                        <div><label class="form-label">Nama Dokter</label><input class="form-control" name="nama" value="<?= e($edit['nama'] ?? ''); ?>" required></div>
                        <div><label class="form-label">Spesialis</label><input class="form-control" name="spesialis" value="<?= e($edit['spesialis'] ?? ''); ?>"></div>
                        <div><label class="form-label">SIP</label><input class="form-control" name="sip" value="<?= e($edit['sip'] ?? ''); ?>"></div>
                        <div><label class="form-label">Telepon</label><input class="form-control" name="telepon" value="<?= e($edit['telepon'] ?? ''); ?>"></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Dokter</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['nama' => 'Nama', 'spesialis' => 'Spesialis', 'sip' => 'SIP', 'telepon' => 'Telepon'], $db['dokter'], 'dokter'); ?></section>
            <?php endif; ?>

            <?php if ($module === 'poli_master'): ?>
                <?php $edit = current_edit($db['poli']); ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Poli</h2></div>
                    <form method="post" action="?m=poli_master&a=save_poli" class="form-grid">
                        <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                        <div><label class="form-label">Nama Poli</label><input class="form-control" name="nama" value="<?= e($edit['nama'] ?? ''); ?>" required></div>
                        <div><label class="form-label">Lantai</label><input class="form-control" name="lantai" value="<?= e($edit['lantai'] ?? ''); ?>"></div>
                        <div><label class="form-label">Status</label><select class="form-select" name="status"><option<?= selected('Aktif', $edit['status'] ?? ''); ?>>Aktif</option><option<?= selected('Nonaktif', $edit['status'] ?? ''); ?>>Nonaktif</option></select></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Poli</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['nama' => 'Nama Poli', 'lantai' => 'Lantai', 'status' => 'Status'], $db['poli'], 'poli_master'); ?></section>
            <?php endif; ?>

            <?php if ($module === 'bangsal'): ?>
                <?php $edit = current_edit($db['bangsal']); ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Bangsal</h2></div>
                    <form method="post" action="?m=bangsal&a=save_bangsal" class="form-grid">
                        <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                        <div><label class="form-label">Nama Bangsal</label><input class="form-control" name="nama" value="<?= e($edit['nama'] ?? ''); ?>" required></div>
                        <div><label class="form-label">Kelas</label><input class="form-control" name="kelas" value="<?= e($edit['kelas'] ?? ''); ?>"></div>
                        <div><label class="form-label">Penanggung Jawab</label><input class="form-control" name="penanggung_jawab" value="<?= e($edit['penanggung_jawab'] ?? ''); ?>"></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Bangsal</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['nama' => 'Bangsal', 'kelas' => 'Kelas', 'penanggung_jawab' => 'Penanggung Jawab'], $db['bangsal'], 'bangsal'); ?></section>
            <?php endif; ?>

            <?php if ($module === 'tempat_tidur'): ?>
                <?php $edit = current_edit($db['tempat_tidur']); ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Tempat Tidur</h2></div>
                    <form method="post" action="?m=tempat_tidur&a=save_bed" class="form-grid">
                        <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                        <div><label class="form-label">Bangsal</label><input class="form-control" name="bangsal" value="<?= e($edit['bangsal'] ?? ''); ?>" required></div>
                        <div><label class="form-label">Nomor Bed</label><input class="form-control" name="nomor" value="<?= e($edit['nomor'] ?? ''); ?>" required></div>
                        <div><label class="form-label">Kelas</label><input class="form-control" name="kelas" value="<?= e($edit['kelas'] ?? ''); ?>"></div>
                        <div><label class="form-label">Status</label><select class="form-select" name="status"><option<?= selected('Tersedia', $edit['status'] ?? ''); ?>>Tersedia</option><option<?= selected('Terisi', $edit['status'] ?? ''); ?>>Terisi</option><option<?= selected('Perbaikan', $edit['status'] ?? ''); ?>>Perbaikan</option></select></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Bed</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['bangsal' => 'Bangsal', 'nomor' => 'Nomor', 'kelas' => 'Kelas', 'status' => 'Status'], $db['tempat_tidur'], 'tempat_tidur', function ($key, $row) {
                    if ($key === 'status') return '<span class="badge text-bg-' . (($row[$key] ?? '') === 'Tersedia' ? 'success' : 'secondary') . '">' . e($row[$key]) . '</span>';
                    return e($row[$key] ?? '');
                }); ?></section>
            <?php endif; ?>

            <?php if (in_array($module, ['daftar_baru', 'daftar_poli', 'daftar_inap', 'daftar_ugd'], true)): ?>
                <?php
                $jenisMap = ['daftar_baru' => 'Pasien Baru', 'daftar_poli' => 'Poli', 'daftar_inap' => 'Rawat Inap', 'daftar_ugd' => 'UGD'];
                $jenis = $jenisMap[$module];
                ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form <?= e($jenis); ?></h2></div>
                    <form method="post" action="?m=<?= e($module); ?>&a=save_daftar" class="form-grid">
                        <input type="hidden" name="jenis" value="<?= e($jenis); ?>">
                        <input type="hidden" name="redirect" value="<?= e($module); ?>">
                        <div><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal" value="<?= e(date('Y-m-d')); ?>"></div>
                        <div><label class="form-label">Pasien</label><select class="form-select" name="pasien_id" required><?= options($db['pasien']); ?></select></div>
                        <div><label class="form-label">Poli</label><select class="form-select" name="poli_id"><?= options($db['poli']); ?></select></div>
                        <div><label class="form-label">Dokter</label><select class="form-select" name="dokter_id"><?= options($db['dokter']); ?></select></div>
                        <div><label class="form-label">Bangsal</label><select class="form-select" name="bangsal_id"><?= options($db['bangsal']); ?></select></div>
                        <div><label class="form-label">Status</label><select class="form-select" name="status"><option>Terdaftar</option><option>Dalam Layanan</option><option>Selesai</option></select></div>
                        <div class="span-2"><label class="form-label">Keluhan</label><textarea class="form-control" name="keluhan"></textarea></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Pendaftaran</button></div>
                    </form>
                </section>
                <section class="panel">
                    <?php
                    $rows = array_values(array_filter($db['pendaftaran'], fn($row) => ($row['jenis'] ?? '') === $jenis));
                    render_table(['tanggal' => 'Tanggal', 'pasien_id' => 'Pasien', 'poli_id' => 'Poli', 'dokter_id' => 'Dokter', 'status' => 'Status'], $rows, $module, function ($key, $row) use ($db) {
                        if ($key === 'pasien_id') return e(label_by_id($db['pasien'], $row['pasien_id']));
                        if ($key === 'poli_id') return e(label_by_id($db['poli'], $row['poli_id']));
                        if ($key === 'dokter_id') return e(label_by_id($db['dokter'], $row['dokter_id']));
                        return e($row[$key] ?? '');
                    }, false);
                    ?>
                </section>
            <?php endif; ?>

            <?php if ($module === 'cek_sep'): ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Cek SEP BPJS</h2></div>
                    <form method="post" action="?m=cek_sep&a=save_sep" class="form-grid">
                        <div><label class="form-label">Pasien</label><select class="form-select" name="pasien_id" required><?= options($db['pasien']); ?></select></div>
                        <div><label class="form-label">No Kartu BPJS</label><input class="form-control" name="no_kartu" required></div>
                        <div><label class="form-label">No Rujukan/Kontrol</label><input class="form-control" name="no_rujukan" required></div>
                        <div><label class="form-label">Tujuan Layanan</label><input class="form-control" name="tujuan" placeholder="Poli/UGD/Rawat Inap"></div>
                        <div class="form-actions"><button class="btn btn-primary">Cek SEP</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['tanggal' => 'Tanggal', 'pasien_id' => 'Pasien', 'no_kartu' => 'No Kartu', 'tujuan' => 'Tujuan', 'hasil' => 'Hasil'], array_reverse($db['sep_checks']), 'cek_sep', function ($key, $row) use ($db) {
                    if ($key === 'pasien_id') return e(label_by_id($db['pasien'], $row['pasien_id']));
                    return e($row[$key] ?? '');
                }, false); ?></section>
            <?php endif; ?>

            <?php if (in_array($module, ['layanan_poli', 'layanan_ugd', 'layanan_inap', 'laborat', 'radiologi'], true)): ?>
                <?php
                $unitMap = ['layanan_poli' => 'Poli', 'layanan_ugd' => 'UGD', 'layanan_inap' => 'Rawat Inap', 'laborat' => 'Laborat', 'radiologi' => 'Radiologi'];
                $unit = $unitMap[$module];
                $regs = $db['pendaftaran'];
                ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Layanan <?= e($unit); ?></h2></div>
                    <form method="post" action="?m=<?= e($module); ?>&a=save_layanan" class="form-grid">
                        <input type="hidden" name="unit" value="<?= e($unit); ?>">
                        <input type="hidden" name="redirect" value="<?= e($module); ?>">
                        <div><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal" value="<?= e(date('Y-m-d')); ?>"></div>
                        <div><label class="form-label">Pendaftaran</label><select class="form-select" name="pendaftaran_id" required><?php foreach ($regs as $reg): ?><option value="<?= e($reg['id']); ?>"><?= e(pendaftaran_label($db, $reg['id'])); ?></option><?php endforeach; ?></select></div>
                        <div><label class="form-label">Dokter/Petugas</label><select class="form-select" name="dokter_id"><?= options($db['dokter']); ?></select></div>
                        <div><label class="form-label">Diagnosa/Hasil</label><input class="form-control" name="diagnosa"></div>
                        <div class="span-2"><label class="form-label">Tindakan</label><textarea class="form-control" name="tindakan"></textarea></div>
                        <div class="span-2"><label class="form-label">Catatan</label><textarea class="form-control" name="catatan"></textarea></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Layanan</button></div>
                    </form>
                </section>
                <section class="panel">
                    <?php
                    $rows = array_values(array_filter($db['layanan'], fn($row) => ($row['unit'] ?? '') === $unit));
                    render_table(['tanggal' => 'Tanggal', 'pendaftaran_id' => 'Pasien', 'dokter_id' => 'Dokter', 'diagnosa' => 'Diagnosa/Hasil', 'tindakan' => 'Tindakan'], $rows, $module, function ($key, $row) use ($db) {
                        if ($key === 'pendaftaran_id') return e(pendaftaran_label($db, $row['pendaftaran_id']));
                        if ($key === 'dokter_id') return e(label_by_id($db['dokter'], $row['dokter_id']));
                        return e($row[$key] ?? '');
                    }, false);
                    ?>
                </section>
            <?php endif; ?>

            <?php if ($module === 'tarif'): ?>
                <?php $edit = current_edit($db['tarif']); ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Tarif</h2></div>
                    <form method="post" action="?m=tarif&a=save_tarif" class="form-grid">
                        <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                        <div><label class="form-label">Nama Tarif</label><input class="form-control" name="nama" value="<?= e($edit['nama'] ?? ''); ?>" required></div>
                        <div><label class="form-label">Kategori</label><input class="form-control" name="kategori" value="<?= e($edit['kategori'] ?? ''); ?>"></div>
                        <div><label class="form-label">Harga</label><input class="form-control" type="number" name="harga" value="<?= e($edit['harga'] ?? ''); ?>" required></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Tarif</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['nama' => 'Nama Tarif', 'kategori' => 'Kategori', 'harga' => 'Harga'], $db['tarif'], 'tarif', fn($key, $row) => $key === 'harga' ? e(rupiah($row[$key])) : e($row[$key] ?? '')); ?></section>
            <?php endif; ?>

            <?php if ($module === 'billing'): ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Billing</h2></div>
                    <form method="post" action="?m=billing&a=save_billing" class="form-grid">
                        <div><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal" value="<?= e(date('Y-m-d')); ?>"></div>
                        <div><label class="form-label">Pendaftaran</label><select class="form-select" name="pendaftaran_id" required><?php foreach ($db['pendaftaran'] as $reg): ?><option value="<?= e($reg['id']); ?>"><?= e(pendaftaran_label($db, $reg['id'])); ?></option><?php endforeach; ?></select></div>
                        <div><label class="form-label">Tarif</label><select class="form-select" name="tarif_id" required><?= options($db['tarif']); ?></select></div>
                        <div><label class="form-label">Qty</label><input class="form-control" type="number" name="qty" value="1" min="1"></div>
                        <div><label class="form-label">Status</label><select class="form-select" name="status"><option>Belum Lunas</option><option>Lunas</option></select></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Billing</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['tanggal' => 'Tanggal', 'pendaftaran_id' => 'Pasien', 'tarif_id' => 'Tarif', 'qty' => 'Qty', 'total' => 'Total', 'status' => 'Status'], $db['billing'], 'billing', function ($key, $row) use ($db) {
                    if ($key === 'pendaftaran_id') return e(pendaftaran_label($db, $row['pendaftaran_id']));
                    if ($key === 'tarif_id') return e(label_by_id($db['tarif'], $row['tarif_id']));
                    if ($key === 'total') return e(rupiah($row[$key]));
                    return e($row[$key] ?? '');
                }); ?></section>
            <?php endif; ?>

            <?php if ($module === 'laporan_billing'): ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Rekap Billing</h2><strong><?= rupiah(array_sum(array_map(fn($row) => (float) $row['total'], $db['billing']))); ?></strong></div>
                    <?php render_table(['tanggal' => 'Tanggal', 'pendaftaran_id' => 'Pasien', 'tarif_id' => 'Tarif', 'qty' => 'Qty', 'total' => 'Total', 'status' => 'Status'], $db['billing'], 'laporan_billing', function ($key, $row) use ($db) {
                        if ($key === 'pendaftaran_id') return e(pendaftaran_label($db, $row['pendaftaran_id']));
                        if ($key === 'tarif_id') return e(label_by_id($db['tarif'], $row['tarif_id']));
                        if ($key === 'total') return e(rupiah($row[$key]));
                        return e($row[$key] ?? '');
                    }, false); ?>
                </section>
            <?php endif; ?>

            <?php if (in_array($module, ['kasir_rj', 'kasir_ugd', 'kasir_ri'], true)): ?>
                <?php $jenisKasir = ['kasir_rj' => 'Rawat Jalan', 'kasir_ugd' => 'UGD', 'kasir_ri' => 'Rawat Inap'][$module]; ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form <?= e($jenisKasir); ?></h2></div>
                    <form method="post" action="?m=<?= e($module); ?>&a=save_kasir" class="form-grid">
                        <input type="hidden" name="jenis" value="<?= e($jenisKasir); ?>">
                        <input type="hidden" name="redirect" value="<?= e($module); ?>">
                        <div><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal" value="<?= e(date('Y-m-d')); ?>"></div>
                        <div><label class="form-label">Pasien</label><select class="form-select" name="pasien_id" required><?= options($db['pasien']); ?></select></div>
                        <div><label class="form-label">Total Tagihan</label><input class="form-control" type="number" name="total" required></div>
                        <div><label class="form-label">Bayar</label><input class="form-control" type="number" name="bayar" required></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Pembayaran</button></div>
                    </form>
                </section>
                <section class="panel">
                    <?php
                    $rows = array_values(array_filter($db['kasir'], fn($row) => ($row['jenis'] ?? '') === $jenisKasir));
                    render_table(['tanggal' => 'Tanggal', 'pasien_id' => 'Pasien', 'total' => 'Total', 'bayar' => 'Bayar', 'kembalian' => 'Kembalian', 'status' => 'Status'], $rows, $module, function ($key, $row) use ($db) {
                        if ($key === 'pasien_id') return e(label_by_id($db['pasien'], $row['pasien_id']));
                        if (in_array($key, ['total', 'bayar', 'kembalian'], true)) return e(rupiah($row[$key]));
                        return e($row[$key] ?? '');
                    }, false);
                    ?>
                </section>
            <?php endif; ?>

            <?php if ($module === 'obat'): ?>
                <?php $edit = current_edit($db['obat']); ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Obat</h2></div>
                    <form method="post" action="?m=obat&a=save_obat" class="form-grid">
                        <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                        <div><label class="form-label">Nama Obat</label><input class="form-control" name="nama" value="<?= e($edit['nama'] ?? ''); ?>" required></div>
                        <div><label class="form-label">Satuan</label><input class="form-control" name="satuan" value="<?= e($edit['satuan'] ?? ''); ?>"></div>
                        <div><label class="form-label">Stok</label><input class="form-control" type="number" name="stok" value="<?= e($edit['stok'] ?? '0'); ?>"></div>
                        <div><label class="form-label">Harga</label><input class="form-control" type="number" name="harga" value="<?= e($edit['harga'] ?? '0'); ?>"></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Obat</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['nama' => 'Nama Obat', 'satuan' => 'Satuan', 'stok' => 'Stok', 'harga' => 'Harga'], $db['obat'], 'obat', fn($key, $row) => $key === 'harga' ? e(rupiah($row[$key])) : e($row[$key] ?? '')); ?></section>
            <?php endif; ?>

            <?php if ($module === 'transaksi_obat'): ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Form Penjualan Obat</h2></div>
                    <form method="post" action="?m=transaksi_obat&a=save_penjualan_obat" class="form-grid">
                        <div><label class="form-label">Tanggal</label><input class="form-control" type="date" name="tanggal" value="<?= e(date('Y-m-d')); ?>"></div>
                        <div><label class="form-label">Pasien</label><select class="form-select" name="pasien_id" required><?= options($db['pasien']); ?></select></div>
                        <div><label class="form-label">Obat</label><select class="form-select" name="obat_id" required><?= options($db['obat']); ?></select></div>
                        <div><label class="form-label">Qty</label><input class="form-control" type="number" name="qty" value="1" min="1"></div>
                        <div class="form-actions"><button class="btn btn-primary">Simpan Transaksi</button></div>
                    </form>
                </section>
                <section class="panel"><?php render_table(['tanggal' => 'Tanggal', 'pasien_id' => 'Pasien', 'obat_id' => 'Obat', 'qty' => 'Qty', 'total' => 'Total'], $db['penjualan_obat'], 'transaksi_obat', function ($key, $row) use ($db) {
                    if ($key === 'pasien_id') return e(label_by_id($db['pasien'], $row['pasien_id']));
                    if ($key === 'obat_id') return e(label_by_id($db['obat'], $row['obat_id']));
                    if ($key === 'total') return e(rupiah($row[$key]));
                    return e($row[$key] ?? '');
                }); ?></section>
            <?php endif; ?>

            <?php if ($module === 'laporan_obat'): ?>
                <section class="panel">
                    <div class="panel-heading"><h2>Rekap Penjualan Obat</h2><strong><?= rupiah(array_sum(array_map(fn($row) => (float) $row['total'], $db['penjualan_obat']))); ?></strong></div>
                    <?php render_table(['tanggal' => 'Tanggal', 'pasien_id' => 'Pasien', 'obat_id' => 'Obat', 'qty' => 'Qty', 'total' => 'Total'], $db['penjualan_obat'], 'laporan_obat', function ($key, $row) use ($db) {
                        if ($key === 'pasien_id') return e(label_by_id($db['pasien'], $row['pasien_id']));
                        if ($key === 'obat_id') return e(label_by_id($db['obat'], $row['obat_id']));
                        if ($key === 'total') return e(rupiah($row[$key]));
                        return e($row[$key] ?? '');
                    }, false); ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
<?php endif; ?>
<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js?v=4"></script>
</body>
</html>
