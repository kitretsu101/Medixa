<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\File;

class ServeMedixa extends ServeCommand
{
    /**
     * Keep upload settings local to Medixa's development server process.
     *
     * @return list<string>
     */
    protected function serverCommand(): array
    {
        $temporaryDirectory = storage_path('framework/php-temp');
        File::ensureDirectoryExists($temporaryDirectory);

        $command = parent::serverCommand();
        array_splice($command, 1, 0, [
            '-d', 'sys_temp_dir='.$temporaryDirectory,
            '-d', 'upload_tmp_dir='.$temporaryDirectory,
            '-d', 'upload_max_filesize=10M',
            '-d', 'post_max_size=12M',
        ]);

        return $command;
    }
}
