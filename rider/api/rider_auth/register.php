<?php
header('Content-Type: application/json; charset=utf-8');
require_once('../../../db.php');

$conn = getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['rider_name'], $data['email'], $data['password'], $data['phone'])) {

    $rider_name = $conn->real_escape_string($data['rider_name']);
    $email = $conn->real_escape_string($data['email']);
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    $phone = $conn->real_escape_string($data['phone']);

    // Check if email exists
    $check = $conn->query("SELECT * FROM rider_accounts WHERE email='$email'");
    if($check->num_rows > 0) {
        echo json_encode(['status'=>'error','message'=>'Email already registered']);
        exit;
    }

    $sql = "INSERT INTO rider_accounts (rider_name,email,password,phone) 
            VALUES ('$rider_name','$email','$password','$phone')";
    
    if($conn->query($sql)) {
        echo json_encode(['status'=>'success','message'=>'Registration successful']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Database error: '.$conn->error]);
    }

} else {
    echo json_encode(['status'=>'error','message'=>'Missing required fields']);
}
?>
