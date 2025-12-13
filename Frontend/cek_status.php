<?php
require '../Backend/config.php';

echo "<h1>🔍 Diagnosa Status Server</h1>";
echo "<table border='1' cellpadding='10'>";

// 1. Cek PHP Limits
echo "<tr><td>PHP upload_max_filesize</td><td>" . ini_get('upload_max_filesize') . " 
      <br>(Target: <b>100M</b> atau lebih)</td></tr>";
echo "<tr><td>PHP post_max_size</td><td>" . ini_get('post_max_size') . " 
      <br>(Target: <b>100M</b> atau lebih)</td></tr>";

// 2. Cek MySQL Packet
$q = mysqli_query($conn, "SHOW VARIABLES LIKE 'max_allowed_packet'");
$row = mysqli_fetch_assoc($q);
$mb = $row['Value'] / (1024 * 1024);
$status_sql = ($mb >= 64) ? "✅ AMAN" : "❌ BAHAYA (Kekecilan)";

echo "<tr><td>MySQL max_allowed_packet</td><td>" . number_format($mb, 2) . " MB 
      <br>Status: <b>$status_sql</b></td></tr>";
echo "</table>";

echo "<br><p><i>Jika angka di atas masih 2M / 8M / 1MB, berarti file config yang kamu edit SALAH atau XAMPP belum ter-restart dengan benar.</i></p>";
