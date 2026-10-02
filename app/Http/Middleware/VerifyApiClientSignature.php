<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Models\ApiRequestAudit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VerifyApiClientSignature
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $startedAt = microtime(true);
        $clientId = (string) $request->header('X-PR-Client-Id');
        $timestamp = (string) $request->header('X-PR-Timestamp');
        $nonce = (string) $request->header('X-PR-Nonce');
        $signature = strtolower((string) $request->header('X-PR-Signature'));
        $userReference = (string) $request->header('X-PR-User-Reference', '');

        if (!$clientId || !ctype_digit($timestamp) || !preg_match('/^[A-Za-z0-9-]{16,80}$/', $nonce)
            || !preg_match('/^[a-f0-9]{64}$/', $signature)) {
            return response()->json(['ok' => false, 'message' => 'Credenciales de aplicación incompletas.'], 401);
        }
        if (abs(time() - (int) $timestamp) > 300) {
            return response()->json(['ok' => false, 'message' => 'La firma de la solicitud expiró.'], 401);
        }

        $client = ApiClient::where('client_id', $clientId)->where('enabled', true)->first();
        if (!$client || !$client->allows($permission)) {
            return response()->json(['ok' => false, 'message' => 'Aplicación no autorizada para esta operación.'], 403);
        }
        if ($client->allowed_ips && !in_array($request->ip(), $client->allowed_ips, true)) {
            return response()->json(['ok' => false, 'message' => 'Origen no autorizado.'], 403);
        }

        $canonical = $this->canonicalRequest($request, $timestamp, $nonce, $userReference);
        $expected = hash_hmac('sha256', $canonical, $client->secret());
        if (!hash_equals($expected, $signature)) {
            return response()->json(['ok' => false, 'message' => 'Firma de aplicación inválida.'], 401);
        }

        $nonceKey = 'personas-rapidas:nonce:'.$client->id.':'.$nonce;
        if (!Cache::add($nonceKey, true, now()->addMinutes(10))) {
            return response()->json(['ok' => false, 'message' => 'La solicitud ya fue utilizada.'], 409);
        }

        $requestId = (string) Str::uuid();
        $request->attributes->set('api_client', $client);
        $request->attributes->set('api_request_id', $requestId);
        $response = $next($request);

        ApiRequestAudit::create([
            'api_client_id' => $client->id,
            'request_id' => $requestId,
            'user_reference' => $userReference ?: null,
            'method' => $request->method(),
            'path' => $request->getPathInfo(),
            'ip' => $request->ip(),
            'rut_hash' => $request->query('rut') ? hash_hmac('sha256', (string) $request->query('rut'), $client->secret()) : null,
            'response_status' => $response->getStatusCode(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
        $client->forceFill(['last_used_at' => now()])->save();
        $response->headers->set('X-PR-Request-Id', $requestId);

        return $response;
    }

    private function canonicalRequest(Request $request, string $timestamp, string $nonce, string $userReference): string
    {
        $query = $request->query();
        ksort($query);

        return implode("\n", [
            strtoupper($request->method()),
            $request->getPathInfo(),
            http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            hash('sha256', $request->getContent()),
            $timestamp,
            $nonce,
            $userReference,
        ]);
    }
}
