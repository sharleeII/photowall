<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddFrameFilenameToEvents extends BaseMigration
{
    public function change(): void
    {
        $this->table('events')
            ->addColumn('frame_filename', 'string', [
                'null'    => true,
                'default' => null,
                'limit'   => 255,
                'after'   => 'theme_color',
            ])
            ->update();
    }
}
