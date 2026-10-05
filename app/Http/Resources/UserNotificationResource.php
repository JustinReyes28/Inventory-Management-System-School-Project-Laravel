<?php

namespace App\Http\Resources;

use App\Enums\NotificationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type instanceof NotificationType
                ? $this->type->value
                : (string) $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'link' => is_string($this->link) && $this->link !== '' ? '/'.ltrim($this->link, '/') : null,
            'is_read' => (bool) $this->is_read,
            'created_at' => $this->created_at?->toIso8601String(),
            'timeago' => $this->created_at?->diffForHumans(),
        ];
    }
}
