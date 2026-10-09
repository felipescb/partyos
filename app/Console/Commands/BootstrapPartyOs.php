<?php

namespace App\Console\Commands;

use App\Domain\Events\OfficialCatalog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BootstrapPartyOs extends Command
{
    protected $signature = 'partyos:bootstrap';

    protected $description = 'Sync the official catalog and create the initial admin user';

    public function handle(): int
    {
        OfficialCatalog::sync();
        $this->info('Catálogo oficial sincronizado.');

        $email = Str::lower(trim((string) config('partyos.admin_email')));
        $name = trim((string) config('partyos.admin_name'));
        $name = $name !== '' ? $name : 'Admin';

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('ADMIN_EMAIL inválido.');

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();

        if ($existing instanceof User) {
            $this->info('Usuário admin já existe: '.$email);

            return self::SUCCESS;
        }

        $configuredPassword = config('partyos.admin_password');
        $password = is_string($configuredPassword) && $configuredPassword !== ''
            ? $configuredPassword
            : 'partyos';

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        $user->markEmailAsVerified();

        $this->info('Usuário admin criado: '.$email);
        $this->line('Senha inicial: '.$password);

        return self::SUCCESS;
    }
}
