<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

function thaiDate($date)
{
    $months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];

    $d = date('j', strtotime($date));
    $m = $months[(int)date('n', strtotime($date))];
    $y = date('Y', strtotime($date)) + 543;

    return "$d $m $y";
}

function thaiMonthYear($ym)
{
    $months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];

    [$y, $m] = explode('-', $ym);
    return $months[(int)$m] . ' ' . ($y + 543);
}


/* ====== ลบข้อมูล ====== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM cash_records WHERE id=$id");
    header("Location: index.php");
    exit;
}

/* ====== โหลดข้อมูลเพื่อแก้ไข ====== */
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM cash_records WHERE id=$id");
    $edit = $res->fetch_assoc();
}

/* ====== เพิ่ม / แก้ไข ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date   = $_POST['record_date'];
    $type   = $_POST['type'];
    $title  = $_POST['title'];
    $amount = floatval($_POST['amount']);

    if (!empty($_POST['id'])) {
        $stmt = $conn->prepare("
            UPDATE cash_records
            SET record_date=?, type=?, title=?, amount=?
            WHERE id=?
        ");
        $stmt->bind_param("sssdi", $date, $type, $title, $amount, $_POST['id']);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO cash_records (record_date,type,title,amount)
            VALUES (?,?,?,?)
        ");
        $stmt->bind_param("sssd", $date, $type, $title, $amount);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit;
}

/* ====== เดือนที่เลือก ====== */
$month = $_GET['month'] ?? date('Y-m');

/* ====== ดึงข้อมูล ====== */
$stmt = $conn->prepare("
    SELECT * FROM cash_records
    WHERE DATE_FORMAT(record_date,'%Y-%m')=?
    ORDER BY record_date ASC
");
$stmt->bind_param("s", $month);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ====== สรุป + กราฟ ====== */
$income = $expense = 0;
$daily = [];

foreach ($rows as $r) {
    $d = $r['record_date'];
    if (!isset($daily[$d])) $daily[$d] = ['in' => 0, 'out' => 0];
    if ($r['type'] == 'IN') {
        $income += $r['amount'];
        $daily[$d]['in'] += $r['amount'];
    } else {
        $expense += $r['amount'];
        $daily[$d]['out'] += $r['amount'];
    }
}
$balance = $income - $expense;
$thaiLabels = [];
foreach (array_keys($daily) as $d) {
    $thaiLabels[] = thaiDate($d);
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <title>บันทึกเงินร้าน</title>

    <link rel="stylesheet" href="css/flatpickr.min.css">
    <link rel="stylesheet" href="css/monthSelect.css">
    <script src="js/flatpickr.min.js"></script>
    <script src="js/th.js"></script>
    <script src="js/monthSelect.js"></script>
    <script src="js/chart.min.js"></script>

    <style>
        body {
            font-family: Sarabun;
            background: linear-gradient(135deg, #FFE4EC, #EDE9FE);
            padding: 30px
        }

        .box {
            background: #fff;
            padding: 24px;
            border-radius: 20px;
            margin-bottom: 26px
        }

        .summary {
            display: flex;
            gap: 40px;
            margin-top: 20px
        }

        .summary span {
            padding: 20px 30px;
            border-radius: 18px;
            min-width: 200px;
            text-align: center
        }

        .summary span {
            transition: transform .2s ease;
        }

        .summary span:hover {
            transform: translateY(-3px);
        }


        .in {
            background: #E9F8F1;
            color: #1b7f5c
        }

        .out {
            background: #FFECEC;
            color: #a83232
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px dashed #ddd;
            text-align: center;
            vertical-align: middle;
        }

        /* คอลัมน์จัดการ */
        td.actions {
            display: flex;
            justify-content: center;
            /* จัดกึ่งกลางแนวนอน */
            align-items: center;
            /* จัดกึ่งกลางแนวตั้ง */
            gap: 12px;
            /* ระยะห่างไอคอน */
        }

        .actions a {
            margin-right: 10px;
            font-size: 18px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            transition: all .2s ease;
        }

        /* hover ให้ดูน่ากด */
        td.actions a:hover {
            background: #FFE4EC;
            transform: translateY(-2px);
        }

        input,
        select {
            padding: 12px 14px;
            border-radius: 14px;
            border: 1.5px solid #f2c6d8;
            font-size: 16px;
            background: #fff;
            transition: all .2s ease;
        }

        /* เอฟเฟกต์ตอนโฟกัส */
        input:focus,
        select:focus {
            outline: none;
            border-color: #FF8FAB;
            box-shadow: 0 0 0 3px rgba(255, 143, 171, .25);
        }

        /* ดรอปดาวน์เฉพาะ */
        select {
            cursor: pointer;
            background-image:
                linear-gradient(45deg, transparent 50%, #FF8FAB 50%),
                linear-gradient(135deg, #FF8FAB 50%, transparent 50%);
            background-position:
                calc(100% - 18px) calc(50% - 3px),
                calc(100% - 12px) calc(50% - 3px);
            background-size: 6px 6px;
            background-repeat: no-repeat;
            appearance: none;
            padding-right: 40px;
        }

        .balance {
            background: linear-gradient(135deg, #FDE2F3, #EDE9FE);
            color: #6B21A8;
        }

        /* ===== ปุ่มทั้งหมด ===== */
        button {
            padding: 12px 26px;
            border: none;
            border-radius: 18px;
            background: linear-gradient(135deg, #FF8FAB, #F9A8D4);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(255, 143, 171, .35);
            transition: all .25s ease;
        }

        /* เอฟเฟกต์ hover */
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(255, 143, 171, .45);
            background: linear-gradient(135deg, #F472B6, #FB7185);
        }

        /* เอฟเฟกต์ตอนกด */
        button:active {
            transform: translateY(0);
            box-shadow: 0 6px 14px rgba(255, 143, 171, .35);
        }

        /* ===== ปุ่มดู (ในสรุปประจำเดือน) ให้ต่างนิดนึง ===== */
        form button {
            margin-left: 6px;
        }

        /* ===== ปุ่มยกเลิก (ลิงก์) ===== */
        form a {
            margin-left: 12px;
            text-decoration: none;
            font-size: 15px;
            color: #9D174D;
            font-weight: 600;
        }

        form a:hover {
            text-decoration: underline;
        }

        /* ช่องที่ผู้ใช้เห็นจริง (altInput) */
        .flatpickr-alt-input {
            padding: 12px 14px;
            border-radius: 14px;
            border: 1.5px solid #f2c6d8;
            font-size: 16px;
            background: #fff;
            transition: all .2s ease;
            min-width: 220px;
        }

        /* โฟกัส */
        .flatpickr-alt-input:focus {
            outline: none;
            border-color: #FF8FAB;
            box-shadow: 0 0 0 3px rgba(255, 143, 171, .25);
        }

        /* ===== ปุ่มออกจากระบบ (โทนอ่อน) ===== */
        .logout-wrap {
            text-align: right;
            margin-top: 30px;
        }

        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 999px;

            /* โทนอ่อน นุ่ม */
            background: linear-gradient(135deg, #FCE7F3, #EDE9FE);
            color: #7C3AED;

            font-size: 15px;
            font-weight: 600;
            text-decoration: none;

            border: 1.5px solid #F5D0FE;
            box-shadow: 0 4px 10px rgba(124, 58, 237, 0.15);
            transition: all .25s ease;
        }

        /* hover */
        .logout-btn:hover {
            background: linear-gradient(135deg, #FBCFE8, #DDD6FE);
            box-shadow: 0 6px 14px rgba(124, 58, 237, 0.25);
            transform: translateY(-1px);
        }

        /* ตอนกด */
        .logout-btn:active {
            transform: translateY(0);
            box-shadow: 0 3px 8px rgba(124, 58, 237, 0.2);
        }
    </style>
</head>

<body>

    <div class="box">
        <h2>📝 <?= $edit ? 'แก้ไขรายการ' : 'บันทึกรายรับ–รายจ่าย' ?></h2>
        <form method="post">
            <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
            <input type="text" id="record_date" name="record_date"
                value="<?= $edit['record_date'] ?? date('Y-m-d') ?>" required>
            <select name="type">
                <option value="IN" <?= ($edit['type'] ?? '') == 'IN' ? 'selected' : '' ?>>รายรับ</option>
                <option value="OUT" <?= ($edit['type'] ?? '') == 'OUT' ? 'selected' : '' ?>>รายจ่าย</option>
            </select>
            <input type="text" name="title"
                value="<?= $edit['title'] ?? '' ?>"
                placeholder="รายการ">

            <input type="number" id="qty"
                placeholder="จำนวน"
                min="1" step="1">

            <input type="number" id="price"
                placeholder="ราคาต่อหน่วย"
                step="1">

            <input type="number" id="amount" name="amount"
                value="<?= $edit['amount'] ?? '' ?>"
                placeholder="จำนวนเงินรวม"
                readonly required>

            <button><?= $edit ? 'บันทึกการแก้ไข' : 'บันทึก' ?></button>
            <?php if ($edit): ?><a href="index.php">ยกเลิก</a><?php endif; ?>
        </form>
    </div>

    <div class="box">
        <h2>📊 สรุปประจำเดือน</h2>
        <form method="get">
            <input type="text" id="month_picker" name="month" value="<?= $month ?>" required>
            <button>ดู</button>
            <a href="export_excel.php?month=<?= $month ?>"
                style="margin-left:10px;">
                <button type="button">📥 Export Excel</button>
            </a>

        </form>

        <div class="summary">
            <span class="in">รายรับ<br><?= number_format($income, 2) ?></span>
            <span class="out">รายจ่าย<br><?= number_format($expense, 2) ?></span>
            <span class="balance">คงเหลือ<br><?= number_format($balance, 2) ?></span>
        </div>
    </div>

    <div class="box">
        <h2>📈 กราฟรายวัน</h2>
        <canvas id="chart"></canvas>
    </div>

    <div class="box">
        <h2>📁 รายการย้อนหลัง</h2>
        <table>
            <tr>
                <th>วันที่</th>
                <th>ประเภท</th>
                <th>รายการ</th>
                <th>จำนวนเงิน</th>
                <th>จัดการ</th>
            </tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= thaiDate($r['record_date']) ?></td>
                    <td><?= $r['type'] == 'IN' ? 'รายรับ' : 'รายจ่าย' ?></td>
                    <td><?= htmlspecialchars($r['title']) ?></td>
                    <td><?= number_format($r['amount'], 2) ?></td>
                    <td class="actions">
                        <a href="?edit=<?= $r['id'] ?>">✏️</a>
                        <a href="?delete=<?= $r['id'] ?>" onclick="return confirm('ลบรายการนี้?')">🗑</a>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>
    </div>

    <div class="logout-wrap">
        <a href="logout.php" class="logout-btn">
            🚪 ออกจากระบบ
        </a>
    </div>

    <script>
        flatpickr("#record_date", {
            locale: "th",
            dateFormat: "Y-m-d", // ค่าที่ส่งเข้า PHP
            altInput: true,
            altFormat: "d F Y", // วัน เดือน ปี (ภาษาไทย)
            defaultDate: "today",
            onReady: function(selectedDates, dateStr, instance) {
                // แปลงปีเป็น พ.ศ.
                const year = instance.altInput.value.match(/\d{4}$/);
                if (year) {
                    instance.altInput.value =
                        instance.altInput.value.replace(year[0], parseInt(year[0]) + 543);
                }
            },
            onChange: function(selectedDates, dateStr, instance) {
                const year = instance.altInput.value.match(/\d{4}$/);
                if (year) {
                    instance.altInput.value =
                        instance.altInput.value.replace(year[0], parseInt(year[0]) + 543);
                }
            }
        });

        flatpickr("#month_picker", {
            locale: "th",
            plugins: [
                new monthSelectPlugin({
                    shorthand: false,
                    dateFormat: "Y-m", // ค่าที่ส่งเข้า PHP
                    altInput: true,
                    altFormat: "F Y" // เดือน ปี (ภาษาไทย)
                })
            ]
        });


        new Chart(document.getElementById('chart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($thaiLabels) ?>,
                datasets: [{
                        label: 'รายรับ',
                        data: <?= json_encode(array_column($daily, 'in')) ?>,
                        backgroundColor: '#95D5B2'
                    },
                    {
                        label: 'รายจ่าย',
                        data: <?= json_encode(array_column($daily, 'out')) ?>,
                        backgroundColor: '#F28482'
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10 // 👈 เพิ่มทีละ 10
                        }
                    }
                }
            }
        });

        /* ===== คำนวณจำนวนเงินอัตโนมัติ ===== */
        const qtyInput = document.getElementById('qty');
        const priceInput = document.getElementById('price');
        const amountInput = document.getElementById('amount');

        function calcTotal() {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            amountInput.value = (qty * price).toFixed(2);
        }

        qtyInput.addEventListener('input', calcTotal);
        priceInput.addEventListener('input', calcTotal);
    </script>

</body>

</html>