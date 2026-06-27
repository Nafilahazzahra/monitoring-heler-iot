<?php

namespace App\Console\Commands;

use App\Services\TelegramAlertService;
use Illuminate\Console\Command;
use Throwable;

class TelegramTestCommand extends Command
{
    protected $signature = 'telegram:test {message? : Pesan yang ingin dikirim}';

    protected $description = 'Send a test notification to Telegram';

    public function handle(TelegramAlertService $telegramAlertService): int
    {
        $message = (string) ($this->argument('message') ?: "Tes notifikasi Telegram berhasil.\nBot monitoring mesin heler aktif.");

        try {
            $telegramAlertService->sendMessage($message);
            $this->info('Notifikasi test berhasil dikirim ke Telegram.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
