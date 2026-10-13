<?php
// ============================================================
// Configuração de conexão com o banco (ajuste para seu XAMPP)
// ============================================================
$host = 'localhost';
$db   = 'sistema_provas';
$user = 'root';
$pass = '';          // XAMPP costuma ser vazio por padrão
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die('Erro de conexão com o banco: ' . $e->getMessage() .
        '<br>Confira se o MySQL do XAMPP está ligado e se você rodou o schema.sql.');
}

// Estilo compartilhado pelas páginas PHP (mesma identidade visual do gerador/corretor)
function pageStyle(){
    return <<<CSS
    <style>
      :root{
        --navy:#1B3355; --navy-2:#2A4A73; --blue:#3E7CB8; --blue-light:#EAF2FA; --blue-border:#4A90C4;
        --ink:#1F2937; --ink-soft:#5B6470; --paper:#FFFFFF; --canvas:#F3F5F8; --line:#E1E6ED;
        --green:#1C8A5C; --green-bg:#E6F7EE; --red:#C13B2E; --red-bg:#FBEAE8;
      }
      *{box-sizing:border-box;}
      body{margin:0; font-family:'Source Sans 3', Arial, sans-serif; background:var(--canvas); color:var(--ink); padding:40px 16px 80px;}
      .wrap{max-width:840px; margin:0 auto;}
      .tag{display:inline-block; font-size:11px; font-weight:600; letter-spacing:.06em; text-transform:uppercase; color:var(--blue); background:var(--blue-light); padding:3px 9px; border-radius:20px; margin-bottom:8px;}
      h1{font-family:'Space Grotesk','Segoe UI',sans-serif; font-size:26px; color:var(--navy); margin:0 0 24px;}
      .card{background:var(--paper); border-radius:14px; box-shadow:0 1px 3px rgba(20,30,50,.06), 0 12px 32px rgba(20,30,50,.05); padding:28px 32px; margin-bottom:22px;}
      .card h2{font-family:'Space Grotesk','Segoe UI',sans-serif; font-size:15px; color:var(--navy); margin:0 0 16px;}
      input[type=file]{width:100%; font-size:14px; padding:10px; border:1.5px dashed var(--line); border-radius:8px; background:#fff;}
      button, .btn{font-family:inherit; font-weight:600; font-size:14px; border:none; border-radius:8px; padding:12px 20px; cursor:pointer; background:var(--navy); color:#fff; margin-top:16px; display:inline-block; text-decoration:none;}
      button:hover, .btn:hover{background:var(--navy-2);}
      table{width:100%; border-collapse:collapse; margin-top:10px;}
      th, td{text-align:left; padding:10px 14px; font-size:13.5px;}
      thead th{background:#F7F9FB; color:var(--ink-soft); font-weight:600; font-size:11.5px; text-transform:uppercase; letter-spacing:.03em; border-bottom:1px solid var(--line);}
      tbody tr{border-bottom:1px solid var(--line);}
      .ok{color:var(--green); font-weight:600;}
      .err{color:var(--red); font-weight:600;}
      .nota-alta{color:var(--green); font-weight:700;}
      .nota-media{color:#B9791E; font-weight:700;}
      .nota-baixa{color:var(--red); font-weight:700;}
      .muted{color:var(--ink-soft); font-size:12.5px;}
      nav{margin-bottom:20px; font-size:13px;}
      nav a{color:var(--blue); text-decoration:none; margin-right:16px; font-weight:600;}
    </style>
    CSS;
}

function notaClass($nota){
    if($nota >= 7) return 'nota-alta';
    if($nota >= 5) return 'nota-media';
    return 'nota-baixa';
}
