<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePhotos extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('photos');
        $table
            ->addColumn('event_id', 'integer', ['null' => false])
            ->addColumn('filename_original', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('filename_thumb', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('uploader_name', 'string', ['limit' => 80, 'null' => true])
            ->addColumn('uploader_ip', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 16, 'null' => false, 'default' => 'approved'])
            ->addColumn('created', 'datetime', ['null' => false])
            ->addIndex(['event_id'], ['name' => 'idx_photos_event'])
            ->addIndex(['event_id', 'status', 'created'], ['name' => 'idx_photos_event_status_created'])
            ->addForeignKey('event_id', 'events', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_photos_event',
            ])
            ->create();
    }
}
