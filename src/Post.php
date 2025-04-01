<?php

declare(strict_types=1);

final class Post
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public readonly string $title,
        public readonly string $link,
        public readonly ?string $description,
        public readonly DateTimeImmutable $publishedAt,
        public readonly array $tags,
    ) {
    }

    public ?string $shortDescription {
        get {
            if ($this->description === null) {
                return null;
            }

            $text = strip_tags($this->description);
            $text = preg_replace('/ +/', ' ', $text);
            $text = preg_replace("/\n+/", "\n", $text);

            return mb_strimwidth(trim($text), 0, 255, '…');
        }
    }
}
