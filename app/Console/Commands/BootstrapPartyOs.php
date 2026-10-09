<?php

namespace App\Console\Commands;

use App\Domain\Events\OfficialCatalog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
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
        $generated = ! is_string($configuredPassword) || $configuredPassword === '';
        $password = $generated ? Str::password(24) : $configuredPassword;

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        $user->markEmailAsVerified();

        if ($generated) {
            $path = storage_path('app/private/initial-admin-password.txt');
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $password.PHP_EOL);
            File::chmod($path, 0600);

            $this->warn('Senha inicial gerada. Ela fica registrada só nesta primeira subida.');
            $this->line('E-mail: '.$email);
            $this->line('Senha: '.$password);
            $this->line('Cópia em storage/app/private/initial-admin-password.txt');
        } else {
            $this->info('Usuário admin criado: '.$email);
        }

        return self::SUCCESS;
    }
}
