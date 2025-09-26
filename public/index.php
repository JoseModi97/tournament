<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Auth\AuthService;
use App\Core\App;
use App\Core\Response;
use App\Services\TournamentService;
use App\Services\WalletService;

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    require __DIR__ . '/landing.php';
    return;
}

$app = new App();
$auth = new AuthService();
$tournaments = new TournamentService();
$wallet = new WalletService();

$app->add('POST', '/api/register', function ($request) use ($auth) {
    $auth->register($request->json());
});

$app->add('POST', '/api/login', function ($request) use ($auth) {
    $auth->login($request->json());
});

$app->add('GET', '/api/me', function () use ($auth) {
    $user = $auth->requireAuth();
    if (!$user) {
        return;
    }
    unset($user['password_hash']);
    Response::json($user);
});

$app->add('GET', '/api/tournaments', function () use ($tournaments) {
    $tournaments->list();
});

$app->add('GET', '/api/tournaments/{id}', function ($request, $params) use ($tournaments) {
    $tournaments->get((int) $params['id']);
});

$app->add('POST', '/api/tournaments', function ($request) use ($auth, $tournaments) {
    $user = $auth->requireAuth(['admin']);
    if (!$user) {
        return;
    }
    $tournaments->create($request->json(), $user);
});

$app->add('POST', '/api/tournaments/{id}/join', function ($request, $params) use ($auth, $tournaments) {
    $user = $auth->requireAuth(['user', 'admin', 'staff']);
    if (!$user) {
        return;
    }
    $tournaments->join((int) $params['id'], $user);
});

$app->add('POST', '/api/matches', function ($request) use ($auth, $tournaments) {
    $user = $auth->requireAuth(['admin']);
    if (!$user) {
        return;
    }
    $tournaments->createMatch($request->json(), $user);
});

$app->add('GET', '/api/staff/matches', function () use ($auth, $tournaments) {
    $user = $auth->requireAuth(['staff']);
    if (!$user) {
        return;
    }
    $tournaments->listMatchesForStaff((int) $user['id']);
});

$app->add('POST', '/api/matches/{id}/result', function ($request, $params) use ($auth, $tournaments) {
    $user = $auth->requireAuth(['staff', 'admin']);
    if (!$user) {
        return;
    }
    $tournaments->updateMatchResult((int) $params['id'], $request->json(), $user);
});

$app->add('GET', '/api/wallet', function () use ($auth, $wallet) {
    $user = $auth->requireAuth(['user', 'admin', 'staff']);
    if (!$user) {
        return;
    }
    $wallet->summary((int) $user['id']);
});

$app->add('POST', '/api/wallet/deposit', function ($request) use ($auth, $wallet) {
    $user = $auth->requireAuth(['user', 'admin', 'staff']);
    if (!$user) {
        return;
    }
    $payload = $request->json();
    $wallet->deposit((int) $user['id'], (float) ($payload['amount'] ?? 0));
});

$app->add('POST', '/api/wallet/withdraw', function ($request) use ($auth, $wallet) {
    $user = $auth->requireAuth(['user', 'admin', 'staff']);
    if (!$user) {
        return;
    }
    $payload = $request->json();
    $wallet->requestWithdrawal((int) $user['id'], (float) ($payload['amount'] ?? 0));
});

$app->add('GET', '/api/admin/withdrawals', function () use ($auth, $wallet) {
    $user = $auth->requireAuth(['admin']);
    if (!$user) {
        return;
    }
    Response::json($wallet->listWithdrawalRequests());
});

$app->add('POST', '/api/admin/withdrawals/{id}/approve', function ($request, $params) use ($auth, $wallet) {
    $user = $auth->requireAuth(['admin']);
    if (!$user) {
        return;
    }
    $wallet->updateWithdrawalStatus((int) $params['id'], 'approved', (int) $user['id']);
});

$app->add('POST', '/api/admin/withdrawals/{id}/reject', function ($request, $params) use ($auth, $wallet) {
    $user = $auth->requireAuth(['admin']);
    if (!$user) {
        return;
    }
    $wallet->updateWithdrawalStatus((int) $params['id'], 'rejected', (int) $user['id']);
});

$app->dispatch();
