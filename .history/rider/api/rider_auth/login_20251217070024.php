<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once('../../config/db.php');

$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['email'], $data['password'])) {
    $email = $conn->real_escape_string($data['email']);
    $password = $data['password'];

    $result = $conn->query("SELECT * FROM rider_accounts WHERE email='$email'");
    if($result->num_rows > 0) {
        $rider = $result->fetch_assoc();
        if(password_verify($password, $rider['password'])) {
            $_SESSION['rider_id'] = $rider['rider_id'];
            $_SESSION['rider_name'] = $rider['rider_name'];
            $_SESSION['rider_email'] = $rider['email'];
            $_SESSION['rider_phone'] = $rider['phone'];
            echo json_encode(['status'=>'success','message'=>'Login successful']);
        } else {
            echo json_encode(['status'=>'error','message'=>'Invalid password']);
        }
    } else {
        echo json_encode(['status'=>'error','message'=>'Email not found']);
    }

} else {
    echo json_encode(['status'=>'error','message'=>'Missing email or password']);
}
?>
