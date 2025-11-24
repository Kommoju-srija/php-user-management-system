<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
session_start();

// --------------------------------------------
// DATABASE CONNECTION
// --------------------------------------------
$conn = mysqli_connect("localhost","root","","users_db");
if(!$conn){ die("DB Connection Failed!"); }

// Toast Message
$toast = "";
$page = isset($_GET["page"]) ? $_GET["page"] : "login";

// Redirect if not logged in
if(!isset($_SESSION["user"]) && !in_array($page,["login","register"])) {
    header("Location: project.php?page=login");
    exit;
}

/* ============================================================
   REGISTER
============================================================ */
if($page=="register" && $_SERVER["REQUEST_METHOD"]=="POST"){
    $name = mysqli_real_escape_string($conn,$_POST["fullname"]);
    $email = mysqli_real_escape_string($conn,$_POST["email"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm"];

    if($password != $confirm){
        $toast = "Passwords do not match!";
    } else {
        $chk = mysqli_query($conn,"SELECT id FROM users WHERE email='$email'");
        if(mysqli_num_rows($chk)>0){
            $toast = "Email already registered!";
        } else {
            $hash = password_hash($password,PASSWORD_DEFAULT);
            mysqli_query($conn,
            "INSERT INTO users(fullname,email,password)
            VALUES('$name','$email','$hash')");
            $toast = "Registration Successful!";
            header("refresh:1;url=project.php?page=login");
        }
    }
}

/* ============================================================
   LOGIN
============================================================ */
if($page=="login" && $_SERVER["REQUEST_METHOD"]=="POST"){
    $email = mysqli_real_escape_string($conn,$_POST["email"]);
    $password = $_POST["password"];

    $res = mysqli_query($conn,"SELECT * FROM users WHERE email='$email'");
    if(mysqli_num_rows($res)==1){
        $row = mysqli_fetch_assoc($res);
        if(password_verify($password,$row["password"])){
            $_SESSION["user"]=[
                "id"=>$row["id"],
                "name"=>$row["fullname"],
                "email"=>$row["email"]
            ];
            header("Location: project.php?page=dashboard");
            exit;
        } else $toast="Incorrect Password!";
    } else $toast="Email not found!";
}

/* ============================================================
   LOGOUT
============================================================ */
if($page=="logout"){
    session_destroy();
    header("Location: project.php?page=login");
    exit;
}

/* ============================================================
   DELETE USER
============================================================ */
if($page=="delete" && isset($_GET["id"])){
    $id = intval($_GET["id"]);
    mysqli_query($conn,"DELETE FROM users WHERE id=$id");
    header("Location: project.php?page=users&msg=deleted");
    exit;
}

/* ============================================================
   UPDATE USER (FORM SUBMIT)
============================================================ */
if($page=="update_user" && $_SERVER["REQUEST_METHOD"]=="POST"){
    $id = intval($_POST["id"]);
    $name = mysqli_real_escape_string($conn,$_POST["fullname"]);
    $email = mysqli_real_escape_string($conn,$_POST["email"]);

    mysqli_query($conn,
       "UPDATE users SET fullname='$name', email='$email' WHERE id=$id");

    header("Location: project.php?page=users&msg=updated");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>AI Smart Auth System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">

<style>
body{margin:0;padding:0;font-family:Poppins,sans-serif;background:#111;transition:.4s;}
body.light{background:#cfe2ff;color:#000;}
.container{width:430px;margin:70px auto;padding:35px;border-radius:20px;background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);}
h2{text-align:center;color:#00eaff;margin-bottom:20px;}
input,button{width:100%;padding:12px;border-radius:10px;border:none;outline:none;margin-bottom:12px;}
input{background:rgba(255,255,255,0.2);color:white;}
button{background:#00eaff;font-weight:bold;cursor:pointer;}
button:hover{transform:scale(1.05);}
a{color:#00eaff;text-decoration:none;font-weight:bold;}
#toast{position:fixed;top:15px;right:-300px;background:#00eaff;color:#000;padding:12px 20px;border-radius:10px;transition:0.6s;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th,td{padding:10px;border-bottom:1px solid #444;color:white;}
th{background:#00eaff;color:#000;}
.btn-small{padding:5px 10px;border-radius:6px;font-size:13px;cursor:pointer;}
.edit-btn{background:#4cff4c;color:black;}
.del-btn{background:#ff4c4c;color:black;}
</style>

<script>
function showToast(msg){
    let t=document.getElementById("toast");
    t.innerText=msg;
    t.style.right="20px";
    setTimeout(()=>{t.style.right="-300px";},3000);
}
</script>

</head>
<body>

<div id="toast"></div>

<?php if($toast!=""): ?>
<script>showToast("<?= $toast ?>");</script>
<?php endif; ?>

<!-- LOGIN PAGE -->
<?php if($page=="login"): ?>
<div class="container">
<h2>Login</h2>
<form method="POST">
<input type="email" name="email" placeholder="Email">
<input type="password" name="password" placeholder="Password">
<button>Login</button>
</form>
<p style="text-align:center;">New user? <a href="project.php?page=register">Create Account</a></p>
</div>
<?php endif; ?>

<!-- REGISTER PAGE -->
<?php if($page=="register"): ?>
<div class="container">
<h2>Create Account</h2>
<form method="POST">
<input type="text" name="fullname" placeholder="Full Name">
<input type="email" name="email" placeholder="Email">
<input type="password" name="password" placeholder="Password">
<input type="password" name="confirm" placeholder="Confirm Password">
<button>Create Account</button>
</form>
</div>
<?php endif; ?>

<!-- DASHBOARD -->
<?php if($page=="dashboard"): ?>
<div class="container">
<h2>Welcome <?= $_SESSION["user"]["name"] ?> 👋</h2>
<a href="project.php?page=users"><button>Manage Users</button></a>
<a href="project.php?page=logout"><button style="background:#ff7070;">Logout</button></a>
</div>
<?php endif; ?>

<!-- USERS TABLE (ADMIN) -->
<?php if($page=="users"): ?>
<div class="container">
<h2>User Management</h2>

<table>
<tr>
<th>ID</th><th>Name</th><th>Email</th><th>Actions</th>
</tr>

<?php
$users=mysqli_query($conn,"SELECT * FROM users ORDER BY id DESC");
while($u=mysqli_fetch_assoc($users)){
echo "
<tr>
<td>{$u['id']}</td>
<td>{$u['fullname']}</td>
<td>{$u['email']}</td>
<td>
<a href='project.php?page=edit&id={$u['id']}'><button class='btn-small edit-btn'>Edit</button></a>
<a href='project.php?page=delete&id={$u['id']}' onclick='return confirm(\"Delete user?\")'>
<button class='btn-small del-btn'>Delete</button></a>
</td>
</tr>";
}
?>
</table>

<a href="project.php?page=dashboard"><button>Back</button></a>
</div>
<?php endif; ?>

<!-- EDIT USER PAGE -->
<?php if($page=="edit" && isset($_GET["id"])): 
$id=intval($_GET["id"]);
$usr=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id=$id"));
?>
<div class="container">
<h2>Edit User</h2>
<form method="POST" action="project.php?page=update_user">
<input type="hidden" name="id" value="<?= $usr['id'] ?>">
<input type="text" name="fullname" value="<?= $usr['fullname'] ?>">
<input type="email" name="email" value="<?= $usr['email'] ?>">
<button>Update User</button>
</form>
</div>
<?php endif; ?>

</body>
</html>
