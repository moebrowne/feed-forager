<?php

declare(strict_types=1);

final readonly class Feed
{
    /**
     * @param Post[] $posts
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public array $posts,
    ) {
    }
}
