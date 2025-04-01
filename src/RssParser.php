<?php

declare(strict_types=1);

final class RssParser
{
    private array $namespaces;
    private ?SimpleXMLElement $contentNamespace;

    public function __construct(
        private readonly SimpleXMLElement $xml,
    ) {
        $this->namespaces = $this->xml->getNamespaces(true);
        $this->contentNamespace = isset($this->namespaces['content'])
            ? $this->xml->channel->children($this->namespaces['content'])
            : null;
    }

    public function parse(): Feed
    {
        $posts = [];

        foreach ($this->xml->channel->item as $item) {
            $posts[] = new Post(
                title: trim((string)$item->title),
                link: (string)$item->link,
                description: $this->getItemDescription($item),
                publishedAt: new DateTimeImmutable((string)$item->pubDate),
                tags: $this->getItemTags($item),
            );
        }

        return new Feed(
            title: trim((string)$this->xml->channel->title),
            description: isset($this->xml->channel->description) ? trim((string)$this->xml->channel->description) : null,
            posts: $posts,
        );
    }

    private function getItemDescription(SimpleXMLElement $item): ?string
    {
        if (isset($item->description)) {
            return trim((string)$item->description);
        }

        if ($this->contentNamespace) {
            $content = (string)$item->children($this->namespaces['content'])?->encoded ?? null;
            return $content !== null ? trim($content) : null;
        }

        return null;
    }

    private function getItemTags(SimpleXMLElement $item): array
    {
        $tags = [];

        // Handle standard RSS categories
        foreach ($item->category as $category) {
            $tags[] = trim((string)$category);
        }

        // Handle Dublin Core terms
        if (isset($this->namespaces['dc'])) {
            $dc = $item->children($this->namespaces['dc']);
            foreach ($dc->subject as $subject) {
                $tags[] = trim((string)$subject);
            }
        }

        return array_unique($tags);
    }
}
