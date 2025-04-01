<?php

declare(strict_types=1);

final class AtomParser
{
    public function __construct(
        private readonly SimpleXMLElement $xml,
    ) {
    }

    public function parse(): Feed
    {
        $posts = [];

        foreach ($this->xml->entry as $entry) {
            $posts[] = new Post(
                title: trim((string)$entry->title),
                link: $this->getEntryLink($entry),
                description: $this->getEntryDescription($entry),
                publishedAt: $this->getEntryPublishedDate($entry),
                tags: $this->getEntryTags($entry),
            );
        }

        return new Feed(
            title: trim((string)$this->xml->title),
            description: isset($this->xml->subtitle) ? trim((string)$this->xml->subtitle) : null,
            posts: $posts,
        );
    }

    private function getEntryLink(SimpleXMLElement $entry): string
    {
        foreach ($entry->link as $link) {
            $rel = (string)$link['rel'];
            if ($rel === 'alternate' || $rel === '') {
                return (string)$link['href'];
            }
        }

        throw new Exception('Unable to find the post link');
    }

    private function getEntryPublishedDate(SimpleXMLElement $entry): DateTimeImmutable
    {
        $dateString = $entry->published ?? $entry->updated ?? throw new Exception('Atom entry missing required published or updated element');

        return new DateTimeImmutable((string)$dateString);
    }

    private function getEntryDescription(SimpleXMLElement $entry): ?string
    {
        if (isset($entry->content)) {
            return trim((string)$entry->content);
        }

        if (isset($entry->summary)) {
            return trim((string)$entry->summary);
        }

        return null;
    }

    private function getEntryTags(SimpleXMLElement $entry): array
    {
        $tags = [];

        // Process Atom categories
        foreach ($entry->category as $category) {
            if (isset($category['term'])) {
                $tags[] = trim((string)$category['term']);
            } elseif (isset($category['label'])) {
                $tags[] = trim((string)$category['label']);
            }
        }

        return array_unique($tags);
    }
}
