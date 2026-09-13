<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireAdmin();
$db = getDB();

$groupBy = $_GET['group_by'] ?? 'material'; // material | department | program | year_level | semester
$materialId = (int)($_GET['material_id'] ?? 0);

$groupCols = [
    'material'   => ['m.title AS label', 'm.id'],
    'department' => ['d.name AS label', 'd.id'],
    'program'    => ['p.name AS label', 'p.id'],
    'year_level' => ['s.year_level AS label', 's.year_level'],
    'semester'   => ['b.semester AS label', 'b.semester'],
];
if (!isset($groupCols[$groupBy])) $groupBy = 'material';
[$selectLabel, $groupField] = $groupCols[$groupBy];

$sql = "SELECT $selectLabel, COUNT(*) AS total_subscriptions, COALESCE(SUM(bi.price),0) AS total_revenue
        FROM subscriptions sub
        JOIN instructional_materials m ON m.id = sub.material_id
        JOIN students s ON s.id = sub.student_id
        LEFT JOIN college_departments d ON d.id = s.department_id
        LEFT JOIN college_programs p ON p.id = s.program_id
        LEFT JOIN billing_statements b ON b.id = sub.billing_id
        LEFT JOIN billing_statement_items bi ON bi.billing_id = sub.billing_id AND bi.material_id = sub.material_id
        WHERE 1=1";
$params = [];
if ($materialId > 0) { $sql .= " AND m.id = ?"; $params[] = $materialId; }
$sql .= " GROUP BY $groupField ORDER BY total_subscriptions DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
respond(true, ['group_by' => $groupBy, 'report' => $stmt->fetchAll()]);
