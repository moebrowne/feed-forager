<?php

declare(strict_types=1);

foreach (glob(__DIR__ . '/../src/*.php') as $path) {
    require $path;
}

$feedUrls = file(__DIR__ . '/../feeds.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

/** @var string[] $feedUrlsToFetch */
$feedUrlsToFetch = pipe(
    $feedUrls,
    filterOutComments: fn (string $feedUrl): bool => str_starts_with($feedUrl, '#') === false,
    mapToRemoveLeadingStar: fn (string $feedUrl): string => ltrim($feedUrl, '*'),
);

/** @var string[] $featuredAuthors */
$featuredAuthors = pipe(
    $feedUrls,
    filterOutNonFeaturedUrls: fn (string $feedUrl): bool => str_starts_with($feedUrl, '*'),
    mapToRemoveLeadingStar: fn (string $feedUrl): string => ltrim($feedUrl, '*'),
    mapToAuthorName: function (string $feedUrl): string {
        if (preg_match('#nitter.poast.org/([^/]+)/#', $feedUrl, $matches) === 1) {
            return '@' . $matches[1];
        }

        return parse_url($feedUrl, PHP_URL_HOST);
    },
);

/** @var Post[] $posts */
$posts = pipe(
    FeedParser::run($feedUrlsToFetch),
    sortByPublishDate: fn(Post $a, Post $b): int => $b->publishedAt <=> $a->publishedAt,
);

header('Referrer-Policy: no-referrer');

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Feed Forager</title>
    <style>
        body {
            font-family: sans-serif;
            background: linear-gradient(<?= date('z') % 360; ?>deg, #fdf8ed, #edf9fd) fixed;
        }

        @media screen and (max-device-width: 560px) {
            time, feed-domain {
                display: none;
            }
            h1 {
                text-align: center;
            }
        }

        a {
            display: inline-block;
        }

        feed-items {
            display: block;
            max-width: 900px;
            margin: 0 auto;
            padding: 25px 10px;
        }

        hr {
            color: lightsteelblue;
        }

        h1 {
            font-size: 7rem;
            margin: 3rem 0 1rem;
            font-weight: 200;
        }

        feed-item {
            position: relative;
            display: block;
            padding: 4px;
            text-wrap: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 150%;
        }

        feed-item[featured]::before {
            position: absolute;
            content: "✦";
            display: inline-block;
            left: 62px;
            top: 5px;
            color: darkgoldenrod;
            font-size: 0.8rem;
        }

        time {
            display: inline-block;
            width: 70px;
            color: slategray;
            font-size: 0.8rem;
        }

        feed-author {
            color: slategray;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
<feed-items>
    <?php $year = null; ?>
    <?php foreach ($posts as $post) : ?>
        <?php if ($year === null || $year !== $post->publishedAt->format('Y')) : ?>
            <?php if ($year !== null) : ?><hr><?php endif; ?>

            <?php $year = $post->publishedAt->format('Y'); ?>

            <h1><?= $year; ?></h1>
        <?php endif ?>

        <feed-item <?= in_array($post->author, $featuredAuthors, true) ? 'featured' : ''; ?>>
            <time datetime="<?= $post->publishedAt->format('c'); ?>">
                <?= $post->publishedAt->format('j'); ?><span style="font-size: 0.7rem;"><?= $post->publishedAt->format('S'); ?></span> <?= $post->publishedAt->format('M'); ?>
            </time>

            <a href="<?= e($post->link); ?>" title="<?= e($post->shortDescription); ?>">
                <?= e(mb_strimwidth($post->title, 0, 70, '…')) ?>
            </a>

            <feed-author>
                <?= e($post->author) ?>
            </feed-author>
        </feed-item>
    <?php endforeach; ?>
</feed-items>

</body>
</html>