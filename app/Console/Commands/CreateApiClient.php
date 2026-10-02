<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CreateApiClient extends Command
{
    protected $signature = 'api-client:create {client_id} {--name=} {--permissions=personas:buscar} {--ips=} {--rotate}';
    protected $description = 'Crea o rota las credenciales de una aplicación cliente';

    public function handle(): int
    {
        $clientId = trim($this->argument('client_id'));
        if (!preg_match('/^[a-z0-9._-]{3,80}$/', $clientId)) {
            $this->error('client_id sólo puede contener minúsculas, números, punto, guion y guion bajo.');
            return 1;
        }

        $client = ApiClient::where('client_id', $clientId)->first();
        if ($client && !$this->option('rotate')) {
            $this->error('El cliente ya existe. Usa --rotate para emitir una nueva clave.');
            return 1;
        }

        $secret = Str::random(64);
        $permissions = array_values(array_filter(array_map('trim', explode(',', $this->option('permissions')))));
        $ips = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('ips')))));

        $client = $client ?: new ApiClient(['client_id' => $clientId]);
        $client->fill([
            'name' => $this->option('name') ?: $clientId,
            'secret_encrypted' => Crypt::encryptString($secret),
            'permissions' => $permissions,
            'allowed_ips' => $ips ?: null,
            'enabled' => true,
        ])->save();

        $this->info('Cliente creado correctamente. Guarda la clave ahora; no volverá a mostrarse.');
        $this->line('CLIENT_ID='.$clientId);
        $this->line('SHARED_SECRET='.$secret);
        return 0;
    }
}
