<?php
$password = 'password'; // Change this to your desired password
$hash = password_hash($password, PASSWORD_DEFAULT);

$auth_content = <<<PHP
<?php
return [
    'username' => 'admin',
    'password' => '$hash'
]; 
PHP;

file_put_contents('../data/auth.php', $auth_content);

echo "New password hash generated and saved.\n";
echo "Username: admin\n";
echo "Password: $password\n"; 