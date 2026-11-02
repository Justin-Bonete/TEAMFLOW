<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use PDOException;
use RuntimeException;
use finfo;
use JsonException;

final class SettingsController
{
    private const MAX_AVATAR_SIZE = 5_242_880;
    private const MAX_AVATAR_DIMENSION = 4096;

    public function index(): void
    {
        $profile = (new User())->profileForSettings((int) $_SESSION['user_id']);
        if (!$profile) {
            http_response_code(404);
            exit('The profile could not be found.');
        }

        $name = $_SESSION['user_name'] ?? $profile['first_name'] . ' ' . $profile['last_name'];
        $role = $_SESSION['role'] ?? 'User';
        $githubConfigured = $this->githubConfigured();
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
        if (($_GET['fragment'] ?? null) === '1') {
            header('Content-Type: text/html; charset=utf-8');
            require BASE_PATH . '/app/Views/settings/_panel.php';
            return;
        }
        require BASE_PATH . '/app/Views/settings/index.php';
    }

    public function updateProfile(): void
    {
        verify_csrf();
        $userId = (int) $_SESSION['user_id'];
        $user = new User();
        $profile = $user->profileForSettings($userId);
        if (!$profile) {
            http_response_code(404);
            exit('The profile could not be found.');
        }

        $firstNameInput = $_POST['first_name'] ?? null;
        $lastNameInput = $_POST['last_name'] ?? null;
        $emailInput = $_POST['email'] ?? null;
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $passwordConfirmation = $_POST['new_password_confirmation'] ?? '';
        if (
            !is_string($firstNameInput) || !is_string($lastNameInput) || !is_string($emailInput)
            || !is_string($currentPassword) || !is_string($newPassword) || !is_string($passwordConfirmation)
        ) {
            $_SESSION['flash_error'] = 'Submit valid values for your profile.';
            $this->redirectSettings();
        }

        $firstName = trim($firstNameInput);
        $lastName = trim($lastNameInput);
        $email = strtolower(trim($emailInput));
        if (
            $firstName === '' || strlen($firstName) > 80
            || $lastName === '' || strlen($lastName) > 80
            || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190
        ) {
            $_SESSION['flash_error'] = 'Enter a valid first name, last name, and email address.';
            $this->redirectSettings();
        }

        $emailChanged = strcasecmp($email, (string) $profile['email']) !== 0;
        $passwordChanged = $newPassword !== '';
        if ($passwordChanged && (strlen($newPassword) < 12 || $newPassword !== $passwordConfirmation)) {
            $_SESSION['flash_error'] = 'New passwords must match and contain at least 12 characters.';
            $this->redirectSettings();
        }
        if (($emailChanged || $passwordChanged) && !password_verify($currentPassword, $profile['password_hash'])) {
            $_SESSION['flash_error'] = 'Enter your current password to change your email or password.';
            $this->redirectSettings();
        }
        if ($user->emailExists($email, $userId)) {
            $_SESSION['flash_error'] = 'An account with that email address already exists.';
            $this->redirectSettings();
        }

        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ];
        if ($passwordChanged) {
            $data['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        try {
            $user->updateProfile($userId, $data);
        } catch (PDOException $error) {
            if ($error->getCode() !== '23000' || (int) ($error->errorInfo[1] ?? 0) !== 1062) {
                throw $error;
            }
            $_SESSION['flash_error'] = 'An account with that email address already exists.';
            $this->redirectSettings();
        }

        $_SESSION['flash_success'] = 'Your profile settings were saved.';
        $this->redirectSettings();
    }

    public function uploadAvatar(): void
    {
        verify_csrf();
        $file = $_FILES['avatar'] ?? null;
        if (!is_array($file) || !isset($file['error']) || !is_int($file['error'])) {
            $_SESSION['flash_error'] = 'Choose a profile photo to upload.';
            $this->redirectSettings();
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = $this->uploadErrorMessage($file['error']);
            $this->redirectSettings();
        }
        if (
            !isset($file['tmp_name'], $file['size'])
            || !is_string($file['tmp_name']) || !is_int($file['size'])
            || !is_uploaded_file($file['tmp_name'])
        ) {
            $_SESSION['flash_error'] = 'The uploaded profile photo is invalid.';
            $this->redirectSettings();
        }
        if ($file['size'] < 1 || $file['size'] > self::MAX_AVATAR_SIZE) {
            $_SESSION['flash_error'] = 'Profile photos must be 5 MB or smaller.';
            $this->redirectSettings();
        }

        $imageBytes = file_get_contents($file['tmp_name']);
        if ($imageBytes === false) {
            throw new RuntimeException('The uploaded profile photo could not be read.');
        }
        try {
            $storedPath = $this->storeValidatedImage($imageBytes);
        } catch (RuntimeException $error) {
            $_SESSION['flash_error'] = $error->getMessage();
            $this->redirectSettings();
        }

        try {
            $previousPath = (new User())->updateUploadedAvatar((int) $_SESSION['user_id'], $storedPath);
        } catch (\Throwable $error) {
            $this->deletePhoto($storedPath);
            throw $error;
        }
        $this->deletePhoto($previousPath);
        $_SESSION['flash_success'] = 'Your profile photo was updated.';
        $this->redirectSettings();
    }

    public function removeAvatar(): void
    {
        verify_csrf();
        $previousPath = (new User())->updateUploadedAvatar((int) $_SESSION['user_id'], null);
        $this->deletePhoto($previousPath);
        $_SESSION['flash_success'] = 'Your uploaded profile photo was removed.';
        $this->redirectSettings();
    }

    public function selectAvatarSource(): void
    {
        verify_csrf();
        $source = $_POST['avatar_source'] ?? null;
        if (!is_string($source)) {
            $_SESSION['flash_error'] = 'Choose an available profile photo.';
            $this->redirectSettings();
        }
        try {
            (new User())->setAvatarSource((int) $_SESSION['user_id'], $source);
        } catch (RuntimeException $error) {
            $_SESSION['flash_error'] = $error->getMessage();
            $this->redirectSettings();
        }
        $_SESSION['flash_success'] = 'Your profile photo preference was saved.';
        $this->redirectSettings();
    }

    public function avatar(): void
    {
        $path = (new User())->profileAvatarPath((int) $_SESSION['user_id']);
        if ($path === null || !preg_match('/^[a-f0-9]{48}\.(?:jpg|png|webp)$/', $path)) {
            http_response_code(404);
            exit;
        }
        $filePath = BASE_PATH . '/storage/profile-photos/' . $path;
        if (!is_file($filePath)) {
            http_response_code(404);
            exit;
        }

        $mime = match (pathinfo($path, PATHINFO_EXTENSION)) {
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        };
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($filePath));
        header('Content-Disposition: inline');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, max-age=0');
        if (readfile($filePath) === false) {
            throw new RuntimeException('The profile photo could not be served.');
        }
    }

    public function connectGithub(): void
    {
        $returnToPanel = ($_GET['return_to'] ?? null) === 'panel';
        $_SESSION['github_settings_return_to_panel'] = $returnToPanel;
        if (!$this->githubConfigured()) {
            $_SESSION['flash_error'] = 'GitHub linking is not configured. Add the OAuth client ID and secret to the application environment.';
            $this->redirectSettingsTo($returnToPanel);
        }

        $state = bin2hex(random_bytes(32));
        $_SESSION['github_oauth_state'] = ['value' => $state, 'created_at' => time()];
        $query = http_build_query([
            'client_id' => $_ENV['GITHUB_CLIENT_ID'],
            'redirect_uri' => $this->githubRedirectUri(),
            'scope' => 'read:user',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
        header('Location: https://github.com/login/oauth/authorize?' . $query, true, 302);
        exit;
    }

    public function githubCallback(): void
    {
        $returnToPanel = ($_SESSION['github_settings_return_to_panel'] ?? false) === true;
        unset($_SESSION['github_settings_return_to_panel']);
        $storedState = $_SESSION['github_oauth_state'] ?? null;
        unset($_SESSION['github_oauth_state']);
        $returnedState = $_GET['state'] ?? null;
        if (
            !is_array($storedState) || !isset($storedState['value'], $storedState['created_at'])
            || !is_string($storedState['value']) || !is_string($returnedState)
            || !is_int($storedState['created_at'])
            || $storedState['created_at'] < time() - 600
            || !hash_equals($storedState['value'], $returnedState)
        ) {
            $_SESSION['flash_error'] = 'GitHub authorization could not be verified. Please try again.';
            $this->redirectSettingsTo($returnToPanel);
        }
        if (!$this->githubConfigured()) {
            $_SESSION['flash_error'] = 'GitHub linking is not configured.';
            $this->redirectSettingsTo($returnToPanel);
        }
        if (isset($_GET['error'])) {
            $_SESSION['flash_error'] = 'GitHub authorization was not completed.';
            $this->redirectSettingsTo($returnToPanel);
        }
        $code = $_GET['code'] ?? null;
        if (!is_string($code) || !preg_match('/^[A-Za-z0-9._-]{1,256}$/', $code)) {
            $_SESSION['flash_error'] = 'GitHub did not return a valid authorization code.';
            $this->redirectSettingsTo($returnToPanel);
        }

        $storedPath = null;
        try {
            $token = $this->exchangeGithubCode($code);
            $githubProfile = $this->fetchGithubProfile($token);
            $avatarBytes = $this->fetchGithubAvatar($githubProfile['id']);
            $storedPath = $this->storeValidatedImage($avatarBytes);
            $previousPath = (new User())->connectGithub(
                (int) $_SESSION['user_id'],
                $githubProfile['id'],
                $githubProfile['login'],
                $storedPath
            );
            $this->deletePhoto($previousPath);
        } catch (RuntimeException $error) {
            $this->deletePhoto($storedPath);
            error_log('GitHub profile connection failed: ' . $error->getMessage());
            $_SESSION['flash_error'] = $error->getMessage();
            $this->redirectSettingsTo($returnToPanel);
        } catch (PDOException $error) {
            $this->deletePhoto($storedPath);
            if ($error->getCode() === '23000') {
                $_SESSION['flash_error'] = 'That GitHub account is already connected to another TEAMFLOW account.';
                $this->redirectSettingsTo($returnToPanel);
            }
            throw $error;
        }

        $_SESSION['flash_success'] = 'GitHub account @' . $githubProfile['login'] . ' connected. Its avatar is now selected.';
        $this->redirectSettingsTo($returnToPanel);
    }

    public function disconnectGithub(): void
    {
        verify_csrf();
        $previousPath = (new User())->disconnectGithub((int) $_SESSION['user_id']);
        $this->deletePhoto($previousPath);
        $_SESSION['flash_success'] = 'Your GitHub account was disconnected.';
        $this->redirectSettings();
    }

    private function redirectSettings(): never
    {
        $this->redirectSettingsTo(($_POST['return_to'] ?? null) === 'panel');
    }

    private function redirectSettingsTo(bool $returnToPanel): never
    {
        redirect('/settings' . ($returnToPanel ? '?panel=1' : ''));
    }

    private function githubConfigured(): bool
    {
        return isset($_ENV['GITHUB_CLIENT_ID'], $_ENV['GITHUB_CLIENT_SECRET'])
            && $_ENV['GITHUB_CLIENT_ID'] !== '' && $_ENV['GITHUB_CLIENT_SECRET'] !== '';
    }

    private function githubRedirectUri(): string
    {
        $appUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        $parts = parse_url($appUrl);
        $localHttp = is_array($parts)
            && ($parts['scheme'] ?? '') === 'http'
            && in_array($parts['host'] ?? '', ['localhost', '127.0.0.1'], true);
        if (!is_array($parts) || (($parts['scheme'] ?? '') !== 'https' && !$localHttp)) {
            throw new RuntimeException('GitHub linking requires HTTPS outside local development.');
        }
        return $appUrl . '/settings/github/callback';
    }

    private function exchangeGithubCode(string $code): string
    {
        $response = $this->curlRequest('https://github.com/login/oauth/access_token', [
            'Accept: application/json',
            'User-Agent: TEAMFLOW',
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query([
            'client_id' => $_ENV['GITHUB_CLIENT_ID'],
            'client_secret' => $_ENV['GITHUB_CLIENT_SECRET'],
            'code' => $code,
            'redirect_uri' => $this->githubRedirectUri(),
        ], '', '&', PHP_QUERY_RFC3986));
        try {
            $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException('GitHub returned an unreadable authorization response.', 0, $error);
        }
        if (!is_array($payload) || isset($payload['error']) || !is_string($payload['access_token'] ?? null)) {
            throw new RuntimeException('GitHub authorization failed. Check the OAuth app configuration and try again.');
        }
        return $payload['access_token'];
    }

    private function fetchGithubProfile(string $token): array
    {
        $response = $this->curlRequest('https://api.github.com/user', [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $token,
            'User-Agent: TEAMFLOW',
            'X-GitHub-Api-Version: 2022-11-28',
        ]);
        try {
            $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException('GitHub returned an unreadable profile response.', 0, $error);
        }
        if (
            !is_array($payload) || !is_int($payload['id'] ?? null) || $payload['id'] < 1
            || !is_string($payload['login'] ?? null)
            || !preg_match('/^[A-Za-z0-9-]{1,39}$/', $payload['login'])
        ) {
            throw new RuntimeException('GitHub did not return a valid account profile.');
        }
        return ['id' => $payload['id'], 'login' => $payload['login']];
    }

    private function fetchGithubAvatar(int $githubId): string
    {
        return $this->curlRequest(
            'https://avatars.githubusercontent.com/u/' . $githubId . '?v=4',
            ['Accept: image/png,image/jpeg,image/webp', 'User-Agent: TEAMFLOW'],
            null,
            self::MAX_AVATAR_SIZE
        );
    }

    private function curlRequest(string $url, array $headers, ?string $postBody = null, int $maxBytes = 1_048_576): string
    {
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('The secure GitHub connection could not be initialized.');
        }
        $body = '';
        $options = [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body, $maxBytes, &$sizeExceeded): int {
                if (strlen($body) + strlen($chunk) > $maxBytes) {
                    $sizeExceeded = true;
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ];
        if ($postBody !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $postBody;
        }
        curl_setopt_array($handle, $options);
        $result = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if ($result === false) {
            throw new RuntimeException(
                $sizeExceeded ? 'The GitHub response exceeded the allowed size.' : 'The secure GitHub request failed.'
            );
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('GitHub returned an unsuccessful response. Try again later.');
        }
        return $body;
    }

    private function storeValidatedImage(string $bytes): string
    {
        if (strlen($bytes) < 1 || strlen($bytes) > self::MAX_AVATAR_SIZE) {
            throw new RuntimeException('Profile photos must be 5 MB or smaller.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $image = getimagesizefromstring($bytes);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (
            !is_string($mime) || !isset($extensions[$mime]) || !is_array($image)
            || ($image['mime'] ?? null) !== $mime
            || ($image[0] ?? 0) < 1 || ($image[1] ?? 0) < 1
            || $image[0] > self::MAX_AVATAR_DIMENSION || $image[1] > self::MAX_AVATAR_DIMENSION
        ) {
            throw new RuntimeException('Use a valid JPEG, PNG, or WebP image up to 4096 × 4096 pixels.');
        }

        $directory = BASE_PATH . '/storage/profile-photos';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('The private profile photo directory could not be created.');
        }
        $path = bin2hex(random_bytes(24)) . '.' . $extensions[$mime];
        if (file_put_contents($directory . '/' . $path, $bytes, LOCK_EX) !== strlen($bytes)) {
            if (is_file($directory . '/' . $path)) {
                unlink($directory . '/' . $path);
            }
            throw new RuntimeException('The profile photo could not be saved.');
        }
        return $path;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path === null || !preg_match('/^[a-f0-9]{48}\.(?:jpg|png|webp)$/', $path)) {
            return;
        }
        $filePath = BASE_PATH . '/storage/profile-photos/' . $path;
        if (is_file($filePath) && !unlink($filePath)) {
            error_log('A replaced profile photo could not be removed from private storage.');
        }
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Profile photos must be 5 MB or smaller.',
            UPLOAD_ERR_PARTIAL => 'The profile photo upload was incomplete. Try again.',
            UPLOAD_ERR_NO_FILE => 'Choose a profile photo to upload.',
            default => 'The profile photo upload failed. Try again.',
        };
    }
}
