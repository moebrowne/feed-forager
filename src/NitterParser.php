<?php

declare(strict_types=1);

final class NitterParser
{
    public function __construct(
        private readonly SimpleXMLElement $xml,
    ) {
    }

    public function parse(): Feed
    {
        $posts = [];

        foreach ($this->xml->channel->item as $item) {
            $title = trim((string)$item->title);

            if (str_starts_with($title, 'RT by ') || str_starts_with($title, 'R to ')) {
                continue;
            }

            $posts[] = new Post(
                title: $title,
                link: (string)$item->link,
                description: null,
                publishedAt: new DateTimeImmutable((string)$item->pubDate),
                tags: [],
            );
        }

        return new Feed(
            title: trim((string)$this->xml->channel->title),
            description: null,
            posts: $posts,
        );
    }
}
