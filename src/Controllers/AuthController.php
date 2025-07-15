<?php 
namespace Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Medoo\Medoo;
use Slim\Views\Twig;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class AuthController
{
    private Twig $view;
    private Medoo $db;

    public function __construct(Twig $view, Medoo $db)
    {
        $this->view = $view;
        $this->db = $db;
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'auth/login.twig');
    }

public function login(Request $request, Response $response): Response
{
    $isAjax = str_contains($request->getHeaderLine('Accept'), 'application/json');
    $contentType = $request->getHeaderLine('Content-Type');

    if (str_contains($contentType, 'application/json')) {
        $input = $request->getBody()->getContents();

        error_log("Content-Type: " . $contentType);
        error_log("Body: " . $input);

        $data = json_decode($input, true);
        if (!$data) {
            $response->getBody()->write('Data JSON tidak terbaca');
            return $response->withStatus(400);
        }
    } else {
        $data = (array)$request->getParsedBody();
    }

    if (empty($data['username']) || empty($data['password'])) {
        $error = 'Username dan password wajib diisi!';
        return $this->respondWithError($response, $error, $isAjax, 400);
    }

    $user = $this->db->get("users", "*", [
        "username" => $data['username'],
        "deleted_at" => null
    ]);

    if ($user) {
        session_unset();
        session_regenerate_id(true);
        $_SESSION['user'] = $user;

        $response->getBody()->write(json_encode(['redirect' => '/']));
return $response
    ->withHeader('Content-Type', 'application/json')
    ->withStatus(200);

    }

    $error = 'Username atau password salah!';
    return $this->respondWithError($response, $error, $isAjax, 401);
}


    private function respondWithError(Response $response, string $error, bool $isAjax, int $status): Response
    {
        if ($isAjax) {
            $response->getBody()->write(json_encode(['error' => $error]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
        }

        return $this->view->render($response, 'auth/login.twig', [
            'error' => $error
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        session_destroy();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    // Menampilkan halaman form register
public function showRegister(Request $request, Response $response): Response
{
    return $this->view->render($response, 'auth/register.twig');
}

// Menyimpan data registrasi user
public function register(Request $request, Response $response): Response
{
    $data = (array)$request->getParsedBody();

    // Cek apakah email sudah terdaftar
    $existingEmail = $this->db->get('users', '*', [
        'email' => $data['email']
    ]);

    // Cek apakah username sudah terdaftar
    $existingUsername = $this->db->get('users', '*', [
        'username' => $data['username']
    ]);

    if ($existingEmail) {
        return $this->view->render($response, 'auth/register.twig', [
            'error' => 'Email sudah digunakan. Gunakan email lain.'
        ]);
    }

    if ($existingUsername) {
        return $this->view->render($response, 'auth/register.twig', [
            'error' => 'Username sudah digunakan. Coba yang lain.'
        ]);
    }

    // Jika lolos semua, lanjut proses simpan
    $token = bin2hex(random_bytes(16));
    $this->db->insert('users', [
        'username' => $data['username'],
        'email' => $data['email'],
        'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        'verification_token' => $token
    ]);

    // Kirim email verifikasi seperti sebelumnya
    $verifyLink = 'http://localhost:8082/verify-email/' . $token;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Username = '...';
        $mail->Password = '...';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 2525;

        $mail->setFrom('noreply@your-app.com', 'Your App');
        $mail->addAddress($data['email'], $data['username']);
        $mail->isHTML(true);
        $mail->Subject = 'Verifikasi Email';
        $mail->Body = "Klik link berikut untuk verifikasi email Anda: <a href=\"$verifyLink\">$verifyLink</a>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email gagal dikirim. Error: {$mail->ErrorInfo}");
    }

    return $this->view->render($response, 'auth/register_success.twig');
}





public function verifyEmail(Request $request, Response $response, array $args): Response
{
    $token = $args['token'];

    $user = $this->db->get('users', '*', ['verification_token' => $token]);
    if ($user) {
        $this->db->update('users', [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null
        ], ['id' => $user['id']]);
    }

    return $response->withHeader('Location', '/login')->withStatus(302);
}

public function sendResetLink(Request $request, Response $response): Response
{
    $data = $request->getParsedBody();
    $user = $this->db->get('users', '*', ['email' => $data['email']]);

    if ($user) {
        $token = bin2hex(random_bytes(16));
        $this->db->update('users', [
            'reset_token' => $token,
            'reset_token_expired_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ], ['id' => $user['id']]);

        // Ganti dengan PHPMailer agar masuk ke Mailtrap
        $resetLink = 'http://localhost:8082/reset-password/' . $token;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.mailtrap.io';
            $mail->SMTPAuth = true;
            $mail->Username = '30f625197ecea8'; // dari mailtrap
            $mail->Password = 'f2255a2156e684';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 2525;

            $mail->setFrom('noreply@your-app.com', 'Your App');
            $mail->addAddress($user['email'], $user['username']);
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password';
            $mail->Body = "Klik link berikut untuk reset password Anda: <a href=\"$resetLink\">$resetLink</a>";

            $mail->send();
        } catch (Exception $e) {
            error_log("Email gagal dikirim. Error: {$mail->ErrorInfo}");
        }

        return $this->view->render($response, 'auth/forgot.twig', [
            'success' => 'Link reset password sudah dikirim ke email Anda.'
        ]);
    }

    return $this->view->render($response, 'auth/forgot.twig', [
        'error' => 'Email tidak ditemukan.'
    ]);
}


public function resetPassword(Request $request, Response $response, array $args): Response
{
    $token = $args['token'];
    $data = $request->getParsedBody();

    $user = $this->db->get('users', '*', [
        'reset_token' => $token,
        'reset_token_expired_at[>]' => date('Y-m-d H:i:s')
    ]);

    if ($user && $data['password'] === $data['confirm_password']) {
        $this->db->update('users', [
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_token_expired_at' => null
        ], ['id' => $user['id']]);
    }

    return $response->withHeader('Location', '/login')->withStatus(302);
}

public function showForgot(Request $request, Response $response): Response
{
    return $this->view->render($response, 'auth/forgot.twig');
}


public function showResetForm(Request $request, Response $response, array $args): Response
{
    $token = $args['token'];

    $user = $this->db->get('users', '*', [
        'reset_token' => $token,
        'reset_token_expired_at[>]' => date('Y-m-d H:i:s')
    ]);

    if (!$user) {
        return $this->view->render($response, 'auth/reset.twig', [
            'error' => 'Token reset tidak valid atau sudah kadaluarsa.'
        ]);
    }

    return $this->view->render($response, 'auth/reset.twig');
}


}
