<?php

namespace App\Console\Commands;

use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateInvitationCode extends Command
{
    protected $signature = 'invitation:create {--admin-email= : Email of the admin issuing the code (default: first admin)}';

    protected $description = 'Generate a single-use invitation code that lets a new visitor register as beta tester';

    public function handle(): int
    {
        $email = $this->option('admin-email');
        $admin = $email
            ? User::where('email', $email)->where('role', 'admin')->first()
            : User::where('role', 'admin')->orderBy('id')->first();

        if (! $admin) {
            $this->error('Aucun utilisateur admin trouvé'.($email ? " pour l'email {$email}" : '.'));

            return self::FAILURE;
        }

        $code = Str::upper(Str::random(16));

        InvitationCode::create([
            'code' => $code,
            'created_by' => $admin->id,
        ]);

        $this->newLine();
        $this->info('Code d\'invitation créé :');
        $this->line("  <fg=yellow;options=bold>{$code}</>");
        $this->newLine();
        $this->line("Émis par : {$admin->email}");
        $this->line('À usage unique. Le beta tester l\'utilise sur /register.');

        return self::SUCCESS;
    }
}
