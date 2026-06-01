<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * EventFrames Model
 *
 * @property \App\Model\Table\EventsTable&\Cake\ORM\Association\BelongsTo $Events
 *
 * @method \App\Model\Entity\EventFrame newEmptyEntity()
 * @method \App\Model\Entity\EventFrame newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\EventFrame|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\EventFrame get(mixed $primaryKey, array|string $finder = 'all', mixed ...$args)
 */
class EventFramesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('event_frames');
        $this->setPrimaryKey('id');

        $this->belongsTo('Events', [
            'foreignKey' => 'event_id',
        ]);
    }
}
