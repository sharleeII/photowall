<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Photo Entity
 *
 * @property int $id
 * @property int $event_id
 * @property string $filename_original
 * @property string $filename_thumb
 * @property string|null $uploader_name
 * @property string|null $uploader_ip
 * @property string $status
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Event $event
 */
class Photo extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'event_id' => true,
        'filename_original' => true,
        'filename_thumb' => true,
        'uploader_name' => true,
        'uploader_ip' => true,
        'status' => true,
        'created' => true,
        'event' => true,
    ];
}
