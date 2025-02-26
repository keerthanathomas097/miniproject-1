<?php
session_start();
include 'connect.php';

// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

// require 'vendor/autoload.php'; // You'll need to install PHPMailer via Composer

require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Now you can safely access user information if they're logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;

// Function to send verification email
function sendVerificationEmail($email, $name, $token) {
    $mail = new PHPMailer(true);
    try {
        // Enable debug output
        $mail->SMTPDebug = 2;  // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer: $str");
        };

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cloveroutfitrentals@gmail.com'; // Updated email
        $mail->Password = 'dxrh atrf fgqr dlzp'; // Updated app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('cloveroutfitrentals@gmail.com', 'Clover Outfit Rentals');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Email Verification';
        $verificationLink = "http://localhost/miniproject1/verify.php?token=" . $token;
        
        $mail->Body = "
            <h2>Welcome to Our Website!</h2>
            <p>Hi $name,</p>
            <p>Please click the link below to verify your email address:</p>
            <p><a href='$verificationLink'>Verify Email</a></p>
            <p>If you didn't create an account, you can ignore this email.</p>
        ";

        $result = $mail->send();
        error_log("Email sending attempt result: " . ($result ? "Success" : "Failed"));
        return $result;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle Signup
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    $name = trim($_POST['signup_name']);
    $email = trim($_POST['signup_email']);
    $password = $_POST['signup_password'];
    $mobile = trim($_POST['signup_mobile']);
    
    $errors = [];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($mobile)) {
        $errors[] = "All fields are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Mobile number must be 10 digits.";
    }

    // Check for duplicate email and phone
    if (empty($errors)) {
        // Check email
        $stmt = $conn->prepare("SELECT email FROM tbl_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "This email is already registered.";
        }
        $stmt->close();

        // Check phone
        $stmt = $conn->prepare("SELECT phone FROM tbl_users WHERE phone = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "This phone number is already registered.";
        }
        $stmt->close();
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32)); // Generate unique token
        
        $stmt = $conn->prepare("INSERT INTO tbl_users (name, email, password, phone, verification_token) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $mobile, $verification_token);
        
        if ($stmt->execute()) {
            // Send verification email
            if (sendVerificationEmail($email, $name, $verification_token)) {
                echo "<script>
                    alert('Registration successful! Please check your email to verify your account.');
                    window.location.href = 'ls.php';
                </script>";
            } else {
                error_log("Failed to send verification email to: " . $email);
                echo "<script>
                    alert('Registration successful but email verification failed. Error has been logged.');
                    window.location.href = 'ls.php';
                </script>";
            }
            $stmt->close();
            exit;
        } else {
            $errors[] = "Registration failed: " . $conn->error;
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        echo "<script>alert('" . addslashes(implode("\\n", $errors)) . "');</script>";
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signin'])) {
    $email = trim($_POST['signin_email']);
    $password = $_POST['signin_password'];
    
    $errors = [];

    if (empty($email) || empty($password)) {
        $errors[] = "Both email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, password, is_verified, role FROM tbl_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Allow admin to login without email verification
            if (!$user['is_verified'] && $user['role'] !== 'admin') {
                $errors[] = "Please verify your email address before logging in.";
            } else if (password_verify($password, $user['password'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['user_id'];
                $_SESSION['username'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['show_welcome'] = true;
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['role'] === 'lender') {
                    header("Location: lender_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $errors[] = "Invalid password.";
            }
        } else {
            $errors[] = "No account found with this email.";
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        echo "<script>alert('" . addslashes(implode("\\n", $errors)) . "');</script>";
    }
}

// Make sure your database table structure is correct:
/*
CREATE TABLE IF NOT EXISTS tbl_users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

// Initialize variables
$signupName = $signupEmail = $signupMobile = $signinEmail = '';
$errors = ['signup' => '', 'signin' => ''];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In / Sign Up</title>
    <meta name="google-signin-client_id" content="YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com">
    <style>
    @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

    * {
        box-sizing: border-box;
    }

    body {
        background: #f6f5f7;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        font-family: 'Montserrat', sans-serif;
        height: 100vh;
        margin: 0;
    }

    h1 {
        font-weight: bold;
        margin: 0;
    }

    h2 {
        text-align: center;
    }

    p {
        font-size: 14px;
        font-weight: 100;
        line-height: 20px;
        letter-spacing: 0.5px;
        margin: 20px 0 30px;
    }

    span {
        font-size: 12px;
    }

    a {
        color: #333;
        font-size: 14px;
        text-decoration: none;
        margin: 15px 0;
    }

    button {
        border-radius: 20px;
        border: 1px solid #FF4B2B;
        background-color: #FF4B2B;
        color: #FFFFFF;
        font-size: 12px;
        font-weight: bold;
        padding: 12px 45px;
        letter-spacing: 1px;
        text-transform: uppercase;
        transition: transform 80ms ease-in;
    }

    button:active {
        transform: scale(0.95);
    }

    button:focus {
        outline: none;
    }

    button.ghost {
        background-color: transparent;
        border-color: #FFFFFF;
    }

    form {
        background-color: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center; 
        flex-direction: column;
        padding: 0 80px;
        height: 100%;
        text-align: center;
    }

    input {
        background-color: #eee;
        border: none;
        padding: 15px 15px;
        margin: 10px 0;
        width: 100%;
    }

    .container {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 14px 28px rgba(0,0,0,0.25), 
                0 10px 10px rgba(0,0,0,0.22);
        position: relative;
        overflow: hidden;
        width: 900px;
        max-width: 100%;
        min-height: 600px;
    }

    .form-container {
        position: absolute;
        top: 0;
        height: 100%;
        transition: all 0.6s ease-in-out;
    }

    .sign-in-container {
        left: 0;
        width: 50%;
        z-index: 2;
    }

    .container.right-panel-active .sign-in-container {
        transform: translateX(100%);
    }

    .sign-up-container {
        left: 0;
        width: 50%;
        opacity: 0;
        z-index: 1;
    }

    .container.right-panel-active .sign-up-container {
        transform: translateX(100%);
        opacity: 1;
        z-index: 5;
        animation: show 0.6s;
    }

    @keyframes show {
        0%, 49.99% {
            opacity: 0;
            z-index: 1;
        }
        
        50%, 100% {
            opacity: 1;
            z-index: 5;
        }
    }

    .overlay-container {
        position: absolute;
        top: 0;
        left: 50%;
        width: 50%;
        height: 100%;
        overflow: hidden;
        transition: transform 0.6s ease-in-out;
        z-index: 100;
    }

    .container.right-panel-active .overlay-container{
        transform: translateX(-100%);
    }

    .overlay {
        background: none; /* Remove the gradient background */
        position: relative;
        left: -100%;
        height: 100%;
        width: 200%;
        transform: translateX(0);
        transition: transform 0.6s ease-in-out;
        overflow: hidden; /* Ensures the image stays within bounds */
    }

    .overlay::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background-image: url('https://i.pinimg.com/736x/07/a2/37/07a237c375a62de5a1788185afb3b996.jpg'); /* Replace 'your-image-url.jpg' with the actual image path */
        background-size: cover;
        background-position: center;
        filter: brightness(0.8);  
        animation: zoomEffect 6s infinite alternate; /* Apply zoom animation */
        z-index: -1; /* Keep the image behind the text */
    }

    /* Define the zoom animation */
    @keyframes zoomEffect {
        0% {
            transform: scale(1); /* Normal size */
        }
        100% {
            transform: scale(1.1); /* Slightly enlarged */
        }
    }

    .container.right-panel-active .overlay {
        transform: translateX(50%);
    }

    .overlay-panel {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 0 40px;
        text-align: center;
        top: 0;
        height: 100%;
        width: 50%;
        transform: translateX(0);
        transition: transform 0.6s ease-in-out;
    }

    .overlay-left {
        transform: translateX(-20%);
        color:white;
    }

    .container.right-panel-active .overlay-left {
        transform: translateX(0);
    }

    .overlay-right {
        right: 0;
        transform: translateX(0);
        color:white;
    }

    .container.right-panel-active .overlay-right {
        transform: translateX(20%);
    }

    .social-container {
        margin: 25px 0;
        width: 100%;
    }

    .google-btn {
        width: 100%;
        height: 42px;
        background-color: #fff;
        border-radius: 20px;  /* Matching your existing button style */
        border: 1px solid #ddd;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        cursor: pointer;
        display: flex;
        align-items: center;
        padding: 1px;
        transition: all 0.3s ease;
        margin: 0 auto;
    }

    .google-btn:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        background-color: #f8f8f8;
        transform: scale(0.98);
    }

    .google-icon-wrapper {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .google-icon {
        width: 18px;
        height: 18px;
    }

    .btn-text {
        margin: 0;
        color: #757575;
        font-size: 14px;
        font-weight: 500;
        flex-grow: 1;
        text-align: center;
        padding-right: 40px;
    }

    /* Add a divider */
    .social-container::before {
        content: "or";
        display: block;
        text-align: center;
        color: #777;
        margin: 15px 0;
        position: relative;
    }

    social-container::after {
        content: "";
        display: block;
        margin: 15px auto;
        width: 100%;
        height: 1px;
        background: #ddd;
    }

    .error-message {
        color: red;
        font-size: 0.8em;
        margin-top: 5px;
        text-align: left;
        width: 100%;
    }

    .forgot-password {
        color: #8d2626;
        text-decoration: none;
        margin: 15px 0;
        font-size: 14px;
        transition: color 0.3s ease;
    }

    .forgot-password:hover {
        color: #702020;
        text-decoration: underline;
    }
    </style>
</head>
<body>
    <div class="container" id="container">
        <!-- Sign Up Form -->
        <div class="form-container sign-up-container">
            <form method="POST" action="" id="signupForm">
                <h1>Create Account</h1>
                <input type="text" id="signup_name" name="signup_name" placeholder="Name" />
                <div id="name-error" class="error-message"></div>
                
                <input type="email" id="signup_email" name="signup_email" placeholder="Email" />
                <div id="email-error" class="error-message"></div>
                
                <input type="password" id="signup_password" name="signup_password" placeholder="Password" />
                <div id="password-error" class="error-message"></div>
                
                <input type="text" id="signup_mobile" name="signup_mobile" placeholder="Mobile" />
                <div id="mobile-error" class="error-message"></div>
                
                <div class="social-container">
                    <div class="google-btn" id="googleSignIn">
                        <div class="google-icon-wrapper">
                            <img class="google-icon" src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg"/>
                        </div>
                        <p class="btn-text">Continue with Google</p>
                    </div>
                </div>
                
                <button type="submit" name="signup">Sign Up</button>
            </form>
        </div>

        <!-- Sign In Form -->
        <div class="form-container sign-in-container">
            <form method="POST" action="" id="signinForm">
                <h1>Sign In</h1>
                <input type="email" id="signin_email" name="signin_email" placeholder="Email" />
                <div id="signin-email-error" class="error-message"></div>
                
                <input type="password" id="signin_password" name="signin_password" placeholder="Password" />
                <div id="signin-password-error" class="error-message"></div>
                
                <a href="forgot_password.php" class="forgot-password">Forgot your password?</a>
                
                <div class="social-container">
                    <div class="google-btn" id="googleSignIn">
                        <div class="google-icon-wrapper">
                            <img class="google-icon" src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg"/>
                        </div>
                        <p class="btn-text">Continue with Google</p>
                    </div>
                </div>
                
                <button type="submit" name="signin">Sign In</button>
            </form>
        </div>

        <!-- Overlay Container -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Enter your personal details and start your journey with us</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Existing panel toggle script
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });

        // Live validation functions
        function validateName(input, errorElement) {
            const value = input.value.trim();
            
            if (value === '') {
                errorElement.textContent = 'Name is required.';
                return false;
            } else if (value.startsWith(' ')) {
                errorElement.textContent = 'Name should not start with a space.';
                return false;
            } else if (/^[^a-zA-Z]/.test(value)) {
                errorElement.textContent = 'Name should not start with a special character.';
                return false;
            } else if (value.length < 3) {
                errorElement.textContent = 'Name must be at least 3 characters long.';
                return false;
            }
            
            errorElement.textContent = '';
            return true;
        }

        function validateEmail(input, errorElement) {
            const value = input.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value === '') {
                errorElement.textContent = 'Email is required.';
                return false;
            } else if (!emailRegex.test(value)) {
                errorElement.textContent = 'Invalid email format.';
                return false;
            }
            errorElement.textContent = '';
            return true;
        }

        function validatePassword(input, errorElement) {
            const value = input.value;
            
            // Check if password is empty
            if (value === '') {
                errorElement.textContent = 'Password is required.';
                return false;
            }
            
            // Check length
            if (value.length < 6) {
                errorElement.textContent = 'Password must be at least 6 characters long.';
                return false;
            }
            
            // Check if starts with special character
            if (/^[^a-zA-Z0-9]/.test(value)) {
                errorElement.textContent = 'Password should not start with a special character.';
                return false;
            }
            
            // Check for uppercase
            if (!/[A-Z]/.test(value)) {
                errorElement.textContent = 'Password must contain at least one uppercase letter.';
                return false;
            }
            
            // Check for lowercase
            if (!/[a-z]/.test(value)) {
                errorElement.textContent = 'Password must contain at least one lowercase letter.';
                return false;
            }
            
            // Check for special character
            if (!/[^a-zA-Z0-9]/.test(value)) {
                errorElement.textContent = 'Password must contain at least one special character.';
                return false;
            }
            
            // If all checks pass
            errorElement.textContent = '';
            return true;
        }

        function validateMobile(input, errorElement) {
            const value = input.value.trim();
            
            if (value === '') {
                errorElement.textContent = 'Mobile number is required.';
                return false;
            } else if (value.startsWith('0')) {
                errorElement.textContent = 'Mobile number should not start with 0.';
                return false;
            } else if (!/^\d{10}$/.test(value)) {
                errorElement.textContent = 'Mobile number must be exactly 10 digits.';
                return false;
            }
            
            errorElement.textContent = '';
            return true;
        }

        // Add live validation listeners for signup form only
        document.addEventListener('DOMContentLoaded', function() {
            // Signup form elements
            const nameInput = document.getElementById('signup_name');
            const emailInput = document.getElementById('signup_email');
            const passwordInput = document.getElementById('signup_password');
            const mobileInput = document.getElementById('signup_mobile');
            
            const nameError = document.getElementById('name-error');
            const emailError = document.getElementById('email-error');
            const passwordError = document.getElementById('password-error');
            const mobileError = document.getElementById('mobile-error');
            
            // Add input event listeners for live validation
            nameInput.addEventListener('input', () => validateName(nameInput, nameError));
            emailInput.addEventListener('input', () => validateEmail(emailInput, emailError));
            passwordInput.addEventListener('input', () => validatePassword(passwordInput, passwordError));
            mobileInput.addEventListener('input', () => validateMobile(mobileInput, mobileError));
            
            // Form submission validation for signup
            document.getElementById('signupForm').addEventListener('submit', function(event) {
                // Validate all fields on submission
                const isNameValid = validateName(nameInput, nameError);
                const isEmailValid = validateEmail(emailInput, emailError);
                const isPasswordValid = validatePassword(passwordInput, passwordError);
                const isMobileValid = validateMobile(mobileInput, mobileError);
                
                // Prevent form submission if any validation fails
                if (!(isNameValid && isEmailValid && isPasswordValid && isMobileValid)) {
                    event.preventDefault();
                }
            });
            
            // Form submission validation for signin - only validate on submission, not live
            document.getElementById('signinForm').addEventListener('submit', function(event) {
                const signinEmailInput = document.getElementById('signin_email');
                const signinPasswordInput = document.getElementById('signin_password');
                const signinEmailError = document.getElementById('signin-email-error');
                const signinPasswordError = document.getElementById('signin-password-error');
                
                // Basic validation on submission
                let isValid = true;
                
                if (signinEmailInput.value.trim() === '') {
                    signinEmailError.textContent = 'Email is required.';
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(signinEmailInput.value.trim())) {
                    signinEmailError.textContent = 'Invalid email format.';
                    isValid = false;
                } else {
                    signinEmailError.textContent = '';
                }
                
                if (signinPasswordInput.value === '') {
                    signinPasswordError.textContent = 'Password is required.';
                    isValid = false;
                } else {
                    signinPasswordError.textContent = '';
                }
                
                // Prevent form submission if validation fails
                if (!isValid) {
                    event.preventDefault();
                }
            });
        });

        // Google Sign-In Initialization
        function initGoogleSignIn() {
            gapi.load('auth2', function() {
                gapi.auth2.init({
                    client_id: '628282840516-vdmofrmhm0ubipbpb2hafj6m3ptsacve.apps.googleusercontent.com'
                }).then(function(auth2) {
                    // Attach click handler to both sign-in and sign-up buttons
                    document.querySelectorAll('.google-btn').forEach(function(button) {
                        button.addEventListener('click', function() {
                            auth2.signIn().then(function(googleUser) {
                                // Handle successful sign-in
                                const profile = googleUser.getBasicProfile();
                                const userData = {
                                    id: profile.getId(),
                                    name: profile.getName(),
                                    email: profile.getEmail(),
                                    imageUrl: profile.getImageUrl()
                                };
                                // Send to your server for processing
                                handleGoogleSignIn(userData);
                            }).catch(function(error) {
                                console.error('Google Sign-In error:', error);
                            });
                        });
                    });
                });
            });
        }

        function handleGoogleSignIn(userData) {
            // Send the data to your PHP backend
            fetch('handle_google_signin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php'; // Redirect after successful sign-in
                } else {
                    alert('Sign-in failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during sign-in');
            });
        }

        // Load Google Sign-In API
        const script = document.createElement('script');
        script.src = 'https://apis.google.com/js/platform.js';
        script.onload = initGoogleSignIn;
        document.head.appendChild(script);
    </script>
</body>
</html>