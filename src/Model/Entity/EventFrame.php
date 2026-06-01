<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * EventFrame Entity
 *
 * @property int $id
 * @property int $event_id
 * @property string $filename      PNG stored in webroot/files/frames/{event_id}/{filename}
 * @property string|null $label    Optional display name shown to guests
 * @property int $sort_order
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Event $event
 */
class EventFrame extends Entity
{
    protected array $_accessible = [
        'event_id'   => true,
        'filename'   => true,
        'label'      => true,
        'sort_order' => true,
        'created'    => true,
    ];
}
