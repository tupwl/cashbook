<?php
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];

    // ตั้งรหัสผ่านตรงนี้ (เปลี่ยนได้)
    $correct_password = '042511';

    if ($password === $correct_password) {
        $_SESSION['logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = 'รหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>เข้าสู่ระบบ</title>
<style>
body{
    font-family:Sarabun;
    background:#FFE4EC;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
.box{
    background:#fff;
    padding:30px;
    border-radius:20px;
    text-align:center;
}
input{
    padding:12px;
    border-radius:12px;
    border:1px solid #ddd;
    width:200px;
}
button{
    margin-top:10px;
    padding:12px 24px;
    border:none;
    border-radius:14px;
    background:#FF8FAB;
    color:#fff;
    font-size:16px;
}
.error{
    color:red;
    margin-top:10px;
}
</style>
</head>
<body>

<div class="box">
<h2>🔐 ใส่รหัสผ่าน</h2>
<form method="post">
<input type="password" name="password" placeholder="รหัสผ่าน" required>
<br>
<button>เข้าสู่ระบบ</button>
</form>
<?php if ($error): ?>
<div class="error"><?= $error ?></div>
<?php endif; ?>
</div>

</body>
</html>
