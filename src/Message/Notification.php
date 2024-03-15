<?php
// src/Message/Notification.php
namespace App\Message;

class Notification
{
    public function __construct(
        private string $content,
        private string $title = "New Notification",
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
