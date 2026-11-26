<?php
session_start();
require_once 'Hewan.php';

$imageDir = __DIR__ . '/images';
$fileDir  = __DIR__ . '/files';

if (!is_dir($imageDir)) {
    mkdir($imageDir, 0777, true);
}
if (!is_dir($fileDir)) {
    mkdir($fileDir, 0777, true);
}

// Inisialisasi data temporary di SESSION
if (!isset($_SESSION['daftar_hewan'])) {
    $_SESSION['daftar_hewan'] = [
        new Mamalia("HWN-01", "Simba", 4, 4, null, null),
        new Burung("HWN-02", "Kiko", 2, "Hijau Kuning", null, null),
    ];
}

if (!isset($_SESSION['catatan_temp'])) {
    $_SESSION['catatan_temp'] = "";
}

// Inisialisasi COOKIE temporary (berlaku 1 jam)
$temaAktif = $_COOKIE['temp_tema'] ?? 'terang';
$penandaSesi = $_COOKIE['temp_penanda'] ?? 'Belum diatur';

$pesanSukses = [];
$pesanError  = [];

// Fungsi bantu upload berdasarkan jenis file (gambar -> images/, file biasa -> files/)
function prosesUpload($fileInput, $imageDir, $fileDir, &$pesanError) {
    if (!isset($fileInput) || $fileInput['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($fileInput['error'] !== UPLOAD_ERR_OK) {
        $pesanError[] = "Gagal mengupload file: {$fileInput['name']} (Kode error: {$fileInput['error']}).";
        return null;
    }

    $maxSize = 5 * 1024 * 1024; // 5 MB
    if ($fileInput['size'] > $maxSize) {
        $pesanError[] = "Ukuran file {$fileInput['name']} melebihi batas maksimal 5 MB.";
        return null;
    }

    $ext = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
    $extGambar = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extDokumen = ['pdf', 'doc', 'docx', 'txt', 'csv', 'xlsx'];

    $namaAman = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($fileInput['name'], PATHINFO_FILENAME));
    $namaUnik = time() . '_' . uniqid() . '_' . $namaAman . '.' . $ext;

    if (in_array($ext, $extGambar)) {
        $targetFull = $imageDir . '/' . $namaUnik;
        $relativePath = 'images/' . $namaUnik;
    } elseif (in_array($ext, $extDokumen)) {
        $targetFull = $fileDir . '/' . $namaUnik;
        $relativePath = 'files/' . $namaUnik;
    } else {
        $pesanError[] = "Ekstensi file .{$ext} tidak diizinkan untuk {$fileInput['name']}.";
        return null;
    }

    if (move_uploaded_file($fileInput['tmp_name'], $targetFull)) {
        return $relativePath;
    }

    $pesanError[] = "Terjadi kesalahan saat memindahkan file {$fileInput['name']}.";
    return null;
}

// Tangani aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah_hewan') {
        $id      = trim($_POST['id'] ?? '');
        $nama    = trim($_POST['nama'] ?? '');
        $umur    = (int)($_POST['umur'] ?? 0);
        $jenis   = $_POST['jenis'] ?? 'Mamalia';

        if ($id === '' || $nama === '' || $umur < 0) {
            $pesanError[] = "ID, Nama, dan Umur (minimal 0) wajib diisi dengan benar.";
        } else {
            $gambarPath = prosesUpload($_FILES['file_gambar'] ?? null, $imageDir, $fileDir, $pesanError);
            $filePath   = prosesUpload($_FILES['file_dokumen'] ?? null, $imageDir, $fileDir, $pesanError);

            if ($gambarPath) {
                $pesanSukses[] = "Gambar berhasil disimpan ke folder <strong>images/</strong>.";
            }
            if ($filePath) {
                $pesanSukses[] = "File berhasil disimpan ke folder <strong>files/</strong>.";
            }

            if ($jenis === 'Mamalia') {
                $jumlahKaki = (int)($_POST['jumlah_kaki'] ?? 4);
                $objekBaru = new Mamalia($id, $nama, $umur, $jumlahKaki, $gambarPath, $filePath);
            } else {
                $warnaBulu = trim($_POST['warna_bulu'] ?? 'Tidak diketahui');
                $objekBaru = new Burung($id, $nama, $umur, $warnaBulu, $gambarPath, $filePath);
            }

            $_SESSION['daftar_hewan'][] = $objekBaru;
            $pesanSukses[] = "Data <strong>{$nama}</strong> berhasil ditambahkan ke Session.";
        }
    } elseif ($aksi === 'simpan_temp') {
        $catatan = trim($_POST['catatan_temp'] ?? '');
        $penanda = trim($_POST['penanda_cookie'] ?? '');
        $tema    = ($_POST['tema_cookie'] ?? 'terang') === 'gelap' ? 'gelap' : 'terang';

        $_SESSION['catatan_temp'] = $catatan;
        $penandaFinal = $penanda !== '' ? $penanda : 'Aktif';

        setcookie('temp_tema', $tema, time() + 3600, '/');
        setcookie('temp_penanda', $penandaFinal, time() + 3600, '/');

        $temaAktif = $tema;
        $penandaSesi = $penandaFinal;
        $pesanSukses[] = "Temporary data di Session dan Cookie berhasil diperbarui.";
    } elseif ($aksi === 'reset_session') {
        unset($_SESSION['daftar_hewan'], $_SESSION['catatan_temp']);
        setcookie('temp_tema', '', time() - 3600, '/');
        setcookie('temp_penanda', '', time() - 3600, '/');
        header("Location: index.php");
        exit;
    }
}

// Hapus item tertentu dari session
if (isset($_GET['hapus'])) {
    $idx = (int)$_GET['hapus'];
    if (isset($_SESSION['daftar_hewan'][$idx])) {
        array_splice($_SESSION['daftar_hewan'], $idx, 1);
        header("Location: index.php");
        exit;
    }
}

$daftarHewan = $_SESSION['daftar_hewan'];
$catatanTemp = $_SESSION['catatan_temp'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment II - Aplikasi Web (OOP, Session, Cookie & Upload)</title>
    <style>
        :root {
            --bg: <?= $temaAktif === 'gelap' ? '#12161f' : '#f4f6f9' ?>;
            --card: <?= $temaAktif === 'gelap' ? '#1e2533' : '#ffffff' ?>;
            --text: <?= $temaAktif === 'gelap' ? '#e5e9f0' : '#1f2937' ?>;
            --muted: <?= $temaAktif === 'gelap' ? '#9aa5b4' : '#6b7280' ?>;
            --border: <?= $temaAktif === 'gelap' ? '#2e384d' : '#e5e7eb' ?>;
            --primary: #2563eb;
            --danger: #dc2626;
            --success-bg: <?= $temaAktif === 'gelap' ? '#132a1e' : '#ecfdf5' ?>;
            --success-text: <?= $temaAktif === 'gelap' ? '#6ee7b7' : '#065f46' ?>;
            --err-bg: <?= $temaAktif === 'gelap' ? '#2d1518' : '#fef2f2' ?>;
            --err-text: <?= $temaAktif === 'gelap' ? '#fca5a5' : '#991b1b' ?>;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        .container { max-width: 1050px; margin: 0 auto; }
        header {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        h1 { margin: 0 0 4px; font-size: 1.4rem; }
        .sub { color: var(--muted); font-size: 0.9rem; margin: 0; }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
        }
        .card h2 { margin-top: 0; font-size: 1.1rem; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 0.9rem; font-weight: bold; margin-bottom: 4px; }
        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg);
            color: var(--text);
        }
        input[type="file"] { width: 100%; font-size: 0.88rem; }
        button, .btn-link {
            padding: 9px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 5px 10px; font-size: 0.8rem; }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.92rem;
        }
        .alert-success { background: var(--success-bg); color: var(--success-text); }
        .alert-error { background: var(--err-bg); color: var(--err-text); }
        .meta-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            font-size: 0.88rem;
            margin-bottom: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        th, td {
            border-bottom: 1px solid var(--border);
            padding: 10px 8px;
            text-align: left;
            vertical-align: middle;
        }
        th { color: var(--muted); font-size: 0.82rem; text-transform: uppercase; }
        .thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--border);
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.78rem;
            background: var(--bg);
            border: 1px solid var(--border);
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div>
            <h1>Manajemen Data Hewan (OOP + Session/Cookie + Upload)</h1>
            <p class="sub">ISWD201 - Aplikasi Web | Assessment II</p>
        </div>
        <form method="POST" onsubmit="return confirm('Reset semua session & cookie temporary?');">
            <input type="hidden" name="aksi" value="reset_session">
            <button type="submit" class="btn-danger btn-sm">Reset Temporary Data</button>
        </form>
    </header>

    <?php foreach ($pesanSukses as $msg): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endforeach; ?>

    <?php foreach ($pesanError as $err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>

    <div class="grid-2">
        <!-- Form Tambah Hewan + Upload Gambar & File -->
        <div class="card">
            <h2>1. Tambah Data Hewan & Upload File</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="aksi" value="tambah_hewan">

                <div class="form-group">
                    <label for="id">ID Hewan</label>
                    <input type="text" id="id" name="id" placeholder="Contoh: HWN-03" required>
                </div>

                <div class="form-group">
                    <label for="nama">Nama Hewan</label>
                    <input type="text" id="nama" name="nama" placeholder="Contoh: Harimau / Elang" required>
                </div>

                <div class="form-group">
                    <label for="umur">Umur (Tahun)</label>
                    <input type="number" id="umur" name="umur" min="0" value="1" required>
                </div>

                <div class="form-group">
                    <label for="jenis">Jenis Class OOP</label>
                    <select id="jenis" name="jenis" onchange="toggleAtributJenis()">
                        <option value="Mamalia">Mamalia</option>
                        <option value="Burung">Burung</option>
                    </select>
                </div>

                <div class="form-group" id="group-mamalia">
                    <label for="jumlah_kaki">Jumlah Kaki</label>
                    <input type="number" id="jumlah_kaki" name="jumlah_kaki" min="0" value="4">
                </div>

                <div class="form-group" id="group-burung" style="display:none;">
                    <label for="warna_bulu">Warna Bulu</label>
                    <input type="text" id="warna_bulu" name="warna_bulu" placeholder="Contoh: Coklat Emas">
                </div>

                <div class="form-group">
                    <label for="file_gambar">Upload Gambar (Disimpan ke folder <code>images/</code>)</label>
                    <input type="file" id="file_gambar" name="file_gambar" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
                </div>

                <div class="form-group">
                    <label for="file_dokumen">Upload File Biasa (Disimpan ke folder <code>files/</code>)</label>
                    <input type="file" id="file_dokumen" name="file_dokumen" accept=".pdf,.doc,.docx,.txt,.csv,.xlsx">
                </div>

                <button type="submit" class="btn-primary">Simpan Hewan</button>
            </form>
        </div>

        <!-- Form Temporary Data Session & Cookie -->
        <div class="card">
            <h2>2. Temporary Data (Session & Cookie)</h2>

            <div class="meta-box">
                <div><strong>Session ID:</strong> <code><?= htmlspecialchars(session_id()) ?></code></div>
                <div><strong>Cookie Tema:</strong> <span class="badge"><?= htmlspecialchars($temaAktif) ?></span></div>
                <div><strong>Cookie Penanda:</strong> <span class="badge"><?= htmlspecialchars($penandaSesi) ?></span></div>
                <div><strong>Catatan Session:</strong> <?= $catatanTemp !== '' ? htmlspecialchars($catatanTemp) : '<em>Kosong</em>' ?></div>
            </div>

            <form method="POST">
                <input type="hidden" name="aksi" value="simpan_temp">

                <div class="form-group">
                    <label for="catatan_temp">Catatan Sementara (<code>$_SESSION</code>)</label>
                    <textarea id="catatan_temp" name="catatan_temp" rows="3" placeholder="Tulis catatan sementara selama sesi berlangsung..."><?= htmlspecialchars($catatanTemp) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="penanda_cookie">Label / Penanda Sementara (<code>$_COOKIE</code>)</label>
                    <input type="text" id="penanda_cookie" name="penanda_cookie" value="<?= htmlspecialchars($penandaSesi) ?>" placeholder="Contoh: Shift Pagi">
                </div>

                <div class="form-group">
                    <label for="tema_cookie">Preferensi Tampilan (<code>$_COOKIE</code>)</label>
                    <select id="tema_cookie" name="tema_cookie">
                        <option value="terang" <?= $temaAktif === 'terang' ? 'selected' : '' ?>>Mode Terang</option>
                        <option value="gelap" <?= $temaAktif === 'gelap' ? 'selected' : '' ?>>Mode Gelap</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary">Simpan Temporary Data</button>
            </form>
        </div>
    </div>

    <!-- Daftar Objek Hewan -->
    <div class="card">
        <h2>3. Daftar Data Hewan Tersimpan di Session (<?= count($daftarHewan) ?>)</h2>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Gambar (<code>images/</code>)</th>
                        <th>Info OOP (<code>displayInfo()</code>)</th>
                        <th>Suara (<code>bersuara()</code>)</th>
                        <th>File (<code>files/</code>)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarHewan)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; color:var(--muted);">Belum ada data hewan di session.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarHewan as $index => $hewan): ?>
                            <tr>
                                <td>
                                    <?php if ($hewan->getGambarPath()): ?>
                                        <img src="<?= htmlspecialchars($hewan->getGambarPath()) ?>" alt="Gambar <?= htmlspecialchars($hewan->getNama()) ?>" class="thumb">
                                    <?php else: ?>
                                        <span class="badge">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($hewan->displayInfo()) ?></td>
                                <td><em><?= htmlspecialchars($hewan->bersuara()) ?></em></td>
                                <td>
                                    <?php if ($hewan->getFilePath()): ?>
                                        <a href="<?= htmlspecialchars($hewan->getFilePath()) ?>" target="_blank" class="btn-link btn-primary btn-sm">Lihat File</a>
                                    <?php else: ?>
                                        <span class="badge">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?hapus=<?= $index ?>" class="btn-link btn-danger btn-sm" onclick="return confirm('Hapus data ini dari session?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleAtributJenis() {
    const jenis = document.getElementById('jenis').value;
    document.getElementById('group-mamalia').style.display = (jenis === 'Mamalia') ? 'block' : 'none';
    document.getElementById('group-burung').style.display  = (jenis === 'Burung')  ? 'block' : 'none';
}
</script>
</body>
</html>