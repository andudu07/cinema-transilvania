<?php
require_once 'config.php';
require_role(['admin','editor']);

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="tickets_export_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

$stmt = $pdo->query("
  SELECT
    t.id,
    t.ticket_code,
    t.qty,
    t.ticket_type,
    t.status,
    t.movie_id,
    m.title AS movie_title,
    m.show_date,
    m.show_time,
    u.username AS buyer_username,
    u.email AS buyer_email
  FROM tickets t
  JOIN movies m ON m.id = t.movie_id
  JOIN users u ON u.id = t.user_id
  ORDER BY t.id DESC
");

$rows = $stmt->fetchAll();

echo "\xEF\xBB\xBF"; // BOM pentru diacritice în Excel
?>
<html>
<head><meta charset="utf-8"></head>
<body>
  <table border="1" cellpadding="6" cellspacing="0">
    <thead>
      <tr>
        <th>ID</th>
        <th>Cod</th>
        <th>Film</th>
        <th>Data</th>
        <th>Ora</th>
        <th>Cantitate</th>
        <th>Tip</th>
        <th>Status</th>
        <th>Buyer</th>
        <th>Email buyer</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['ticket_code']) ?></td>
          <td><?= e($r['movie_title']) ?></td>
          <td><?= e($r['show_date']) ?></td>
          <td><?= e(substr((string)$r['show_time'], 0, 5)) ?></td>
          <td><?= (int)$r['qty'] ?></td>
          <td><?= e($r['ticket_type']) ?></td>
          <td><?= e($r['status']) ?></td>
          <td><?= e($r['buyer_username']) ?></td>
          <td><?= e($r['buyer_email']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>

