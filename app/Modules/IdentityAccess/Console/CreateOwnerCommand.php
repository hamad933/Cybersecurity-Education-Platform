<?php

namespace App\Modules\IdentityAccess\Console;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

class CreateOwnerCommand extends Command
{
    protected $signature = 'owner:create';

    protected $description = 'Interactively create the single local owner';

    public function handle(CreateOwner $action): int
    {
        $name = (string) $this->ask('Display name');
        $email = (string) $this->ask('Email address');
        $password = (string) $this->secret('Password (14+ characters, mixed case, number, symbol)');
        $confirmation = (string) $this->secret('Confirm password');
        $validator = Validator::make(compact('name', 'email', 'password', 'confirmation'), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:320'],
            'password' => ['required', 'same:confirmation', Password::min(14)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        try {
            $owner = $action->execute($name, $email, $password, (string) Str::uuid7());
            $this->info("Owner created: {$owner->display_name}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
