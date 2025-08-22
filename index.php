<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: dashboard/{$role}Review.php");
} else {
    header("Location: auth/login.php");
}
exit();
?>