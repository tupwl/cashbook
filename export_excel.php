<?php
require 'db.php';

function thaiDate($date){
    $months = [
        1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',
        5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',
        9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'
    ];
    $d = date('j', strtotime($date));
    $m = $months[(int)date('n', strtotime($date))];
    $y = date('Y', strtotime($date)) + 543;
    return "$d $m $y";
}

$month = $_GET['month'] ?? date('Y-m');

$stmt = $conn->prepare("
    SELECT * FROM cash_records
    WHERE DATE_FORMAT(record_date,'%Y-%m')=?
    ORDER BY record_date ASC
");
$stmt->bind_param("s", $month);
$stmt->execute();
$rows = $stmt->get_result();

/* ===== CSV Header ===== */
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=report_$month.csv");

/* ===== BOM กันภาษาไทยเพี้ยน ===== */
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

/* หัวตาราง */
fputcsv($output, ['วันที่', 'ประเภท', 'รายการ', 'จำนวนเงิน']);

while ($r = $rows->fetch_assoc()) {
    fputcsv($output, [
        thaiDate($r['record_date']),
        $r['type'] == 'IN' ? 'รายรับ' : 'รายจ่าย',
        $r['title'],
        $r['amount']
    ]);
}

fclose($output);
exit;
