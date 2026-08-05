<?php
/**
 * config/firebase_helper.php
 * Helper untuk sinkronisasi Firebase Auth via Identity Toolkit REST API
 */

if (!defined('FIREBASE_API_KEY')) {
    define('FIREBASE_API_KEY', 'AIzaSyAt_WIKnzhPRX_rU2u9MTcMAhjjT4FFkJ8');
}

/**
 * Mendaftarkan akun email biasa ke Firebase Auth
 */
function firebaseRegisterEmailUser($email, $password) {
    $url = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=" . FIREBASE_API_KEY;
    $data = [
        'email'             => strtolower(trim($email)),
        'password'          => $password,
        'returnSecureToken' => true
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resData = json_decode($response, true);

    if ($httpCode === 200 && isset($resData['localId'])) {
        return [
            'success' => true,
            'uid'     => $resData['localId'],
            'idToken' => $resData['idToken']
        ];
    } else {
        $errorMsg = $resData['error']['message'] ?? 'Gagal mendaftar di Firebase Auth';
        if (strpos($errorMsg, 'EMAIL_EXISTS') !== false) {
            return ['success' => true, 'already_exists' => true, 'error' => $errorMsg];
        }
        return ['success' => false, 'error' => $errorMsg];
    }
}

/**
 * Sign In ke Firebase Auth untuk mendapatkan ID Token
 */
function firebaseSignInUser($email, $password) {
    $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=" . FIREBASE_API_KEY;
    $data = [
        'email'             => strtolower(trim($email)),
        'password'          => $password,
        'returnSecureToken' => true
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resData = json_decode($response, true);

    if ($httpCode === 200 && isset($resData['idToken'])) {
        return [
            'success' => true,
            'uid'     => $resData['localId'],
            'idToken' => $resData['idToken']
        ];
    }
    return ['success' => false, 'error' => $resData['error']['message'] ?? 'Login Firebase Auth gagal'];
}

/**
 * Memperbarui Password pengguna Email Biasa di Firebase Auth (Reset Sandi)
 */
function firebaseUpdatePassword($email, $newPassword, $oldPassword = null) {
    $idToken = null;
    if ($oldPassword) {
        $loginRes = firebaseSignInUser($email, $oldPassword);
        if ($loginRes['success']) {
            $idToken = $loginRes['idToken'];
        }
    }

    if ($idToken) {
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:update?key=" . FIREBASE_API_KEY;
        $data = [
            'idToken'           => $idToken,
            'password'          => $newPassword,
            'returnSecureToken' => true
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resData = json_decode($response, true);
        return ($httpCode === 200);
    }
    return false;
}

/**
 * Menghapus Akun pengguna dari Firebase Auth (Hapus Akun oleh Admin)
 */
function firebaseDeleteUserByEmailAndPassword($email, $password) {
    $loginRes = firebaseSignInUser($email, $password);
    if ($loginRes['success']) {
        $idToken = $loginRes['idToken'];
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:delete?key=" . FIREBASE_API_KEY;
        $data = ['idToken' => $idToken];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200);
    }
    return false;
}
