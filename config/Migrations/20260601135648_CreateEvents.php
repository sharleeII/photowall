<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateEvents extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('events');
        $table
            ->addColumn('slug', 'string', ['limit' => 80, 'null' => false])
            ->addColumn('title', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('theme_color', 'string', ['limit' => 7, 'null' => false, 'default' => '#7c3aed'])
            ->addColumn('moderation_enabled', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('is_open', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addColumn('modified', 'datetime', ['null' => false])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uk_events_slug'])
            ->create();
    }
}
