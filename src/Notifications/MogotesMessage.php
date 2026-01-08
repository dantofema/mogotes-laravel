<?php

namespace Dantofema\MogotesLaravel\Notifications;

class MogotesMessage
{
    public function __construct(
        public string $template,
        public string $channel,
        public array $data = [],
    ) {}

    public static function create(string $template, string $channel): self
    {
        return new self($template, $channel);
    }

    public function with(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }
}
