<?php
// Veritabanı Bağlantısı (XAMPP MySQL)
try {
    $db = new PDO("mysql:host=localhost;charset=utf8", "root", "");
    $db->exec("CREATE DATABASE IF NOT EXISTS fatura_db CHARACTER SET utf8 COLLATE utf8_general_ci");
    $db = new PDO("mysql:host=localhost;dbname=fatura_db;charset=utf8", "root", "");
    
    // Faturalar Tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS faturalar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fatura_adi VARCHAR(255) NOT NULL,
        tutar DECIMAL(10,2) NOT NULL,
        son_tarih DATE NOT NULL,
        kategori VARCHAR(100) NOT NULL,
        durum VARCHAR(50) DEFAULT 'Ödenmedi',
        tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
    die("Veritabanı Hatası: " . $e->getMessage());
}

// Fatura Ekleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ekle'])) {
    $faturaAdi = trim($_POST['fatura_adi']);
    $tutar = floatval($_POST['tutar']);
    $sonTarih = $_POST['son_tarih'];
    $kategori = $_POST['kategori'];

    if (!empty($faturaAdi) && $tutar > 0 && !empty($sonTarih)) {
        $ekle = $db->prepare("INSERT INTO faturalar (fatura_adi, tutar, son_tarih, kategori) VALUES (?, ?, ?, ?)");
        $ekle->execute([$faturaAdi, $tutar, $sonTarih, $kategori]);
        header("Location: index.php");
        exit;
    }
}

// Fatura Silme / Ödendi İşlemi
if (isset($_GET['sil'])) {
    $id = $_GET['sil'];
    $sil = $db->prepare("DELETE FROM faturalar WHERE id = ?");
    $sil->execute([$id]);
    header("Location: index.php");
    exit;
}

// Faturaları Listeleme
$goster = $db->query("SELECT * FROM faturalar ORDER BY son_tarih ASC");
$faturalar = $goster->fetchAll(PDO::FETCH_ASSOC);

// Toplam Borç Hesabı
$toplamBorc = 0;
foreach($faturalar as $f) {
    $toplamBorc += $f['tutar'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kişisel Fatura & Ödeme Hatırlatma Asistanı</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold text-primary"><i class="fa-solid fa-wallet"></i> Fatura & Ödeme Hatırlatma Asistanı</h1>
            <p class="text-muted">Bütçe Kontrol ve Akıllı Vade Takip Otomasyonu</p>
        </div>

        <!-- Özet Kartı -->
        <div class="row mb-4">
            <div class="col-md-4 mx-auto">
                <div class="card bg-primary text-white p-3 text-center">
                    <h6 class="text-uppercase small fw-bold">Toplam Aktif Ödeme / Borç</h6>
                    <h3 class="fw-bold m-0"><?php echo number_format($toplamBorc, 2, ',', '.'); ?> ₺</h3>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sol Taraf: Fatura Ekleme Formu -->
            <div class="col-md-4">
                <div class="card bg-white p-4 mb-4">
                    <h4 class="fw-bold mb-3 text-secondary"><i class="fa-solid fa-plus-circle"></i> Yeni Ödeme Ekle</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fatura / Ödeme Adı</label>
                            <input type="text" name="fatura_adi" class="form-control" placeholder="Örn: Elektrik Faturası" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tutar (₺)</label>
                            <input type="number" step="0.01" name="tutar" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Son Ödeme Tarihi</label>
                            <input type="date" name="son_tarih" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kategori</label>
                            <select name="kategori" class="form-select">
                                <option value="Ev Faturaları">Ev Faturaları (Elektrik, Su vb.)</option>
                                <option value="Abonelikler">Abonelikler (Netflix, İnternet vb.)</option>
                                <option value="Kredi / Kart">Kredi / Kredi Kartı</option>
                                <option value="Diğer">Diğer</option>
                            </select>
                        </div>
                        <button type="submit" name="ekle" class="btn btn-primary w-100 fw-bold py-2">Hatırlatıcıya Kaydet</button>
                    </form>
                </div>
            </div>

            <!-- Sağ Taraf: Fatura Listesi ve Vade Uyarıları -->
            <div class="col-md-8">
                <div class="card bg-white p-4">
                    <h4 class="fw-bold mb-3 text-secondary"><i class="fa-solid fa-list-check"></i> Ödeme Takip Listesi</h4>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Ödeme Adı</th>
                                    <th>Kategori</th>
                                    <th>Tutar</th>
                                    <th>Son Tarih</th>
                                    <th>Durum / İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($faturalar)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Harika! Kayıtlı aktif fatura veya ödemeniz bulunmuyor.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $bugun = date('Y-m-d');
                                    foreach ($faturalar as $f): 
                                        $geciktiMi = ($f['son_tarih'] < $bugun);
                                    ?>
                                        <tr class="<?php echo $geciktiMi ? 'table-danger' : ''; ?>">
                                            <td class="fw-bold"><?php echo htmlspecialchars($f['fatura_adi']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($f['kategori']); ?></span></td>
                                            <td class="fw-bold text-dark"><?php echo number_format($f['tutar'], 2, ',', '.'); ?> ₺</td>
                                            <td>
                                                <?php echo $f['son_tarih']; ?>
                                                <?php if($geciktiMi): ?>
                                                    <span class="badge bg-danger ms-1">Vadesi Geçti!</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?sil=<?php echo $f['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Bu faturayı ödediniz mi? Listeden kaldırılacak.');"><i class="fa-solid fa-check"></i> Ödendi (Sil)</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>