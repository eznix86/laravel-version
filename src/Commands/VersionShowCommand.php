<?php

namespace Eznix86\Version\Commands;

use Eznix86\Version\Version;
use Illuminate\Console\Command;

class VersionShowCommand extends Command
{
    protected $signature = 'version:show';

    protected $description = 'Display the current application version';

    public function handle(Version $version): int
    {
        $this->info("Current version: {$version->get()}");

        $this->table(
            ['Component', 'Value'],
            [
                ['Major', $version->major()],
                ['Minor', $version->minor()],
                ['Patch', $version->patch()],
                ['Pre-release', $version->preRelease() ?? '-'],
            ]
        );

        return self::SUCCESS;
    }
}
