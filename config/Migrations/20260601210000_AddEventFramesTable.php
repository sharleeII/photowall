<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddEventFramesTable extends BaseMigration
{
    public function change(): void
    {
        // Drop the single-frame column added in previous migration.
        $this->table('events')
            ->removeColumn('frame_filename')
            ->update();

        // Multi-frame table — one event can have N frames for guests to choose from.
        $this->table('event_frames')
            ->addColumn('event_id',   'integer',  ['null' => false])
            ->addColumn('filename',   'string',   ['limit' => 255, 'null' => false])
            ->addColumn('label',      'string',   ['limit' => 100, 'null' => true, 'default' => null])
            ->addColumn('sort_order', 'integer',  ['null' => false, 'default' => 0])
            ->addColumn('created',    'datetime', ['null' => false])
            ->addIndex(['event_id'])
            ->create();
    }
}
