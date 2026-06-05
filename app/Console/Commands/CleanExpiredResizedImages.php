<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:clean-expired-resized-images')]
#[Description('Command description')]
class CleanExpiredResizedImages extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
