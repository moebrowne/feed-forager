<?php

declare(strict_types=1);

foreach (glob(__DIR__ . '/../src/*.php') as $path) {
    require $path;
}

$feedUrls = array_filter(
    file(__DIR__ . '/../feeds.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
    fn (string $line): bool => str_starts_with($line, '#') === false
);

$posts = FeedParser::run($feedUrls);

uasort($posts, static fn(Post $a, Post $b): int => $b->publishedAt <=> $a->publishedAt);

if (array_key_exists('domain', $_GET)) {
    $posts = array_filter(
        $posts,
        fn (Post $post): bool => parse_url($post->link, PHP_URL_HOST) === $_GET['domain']
    );
}

function e(?string $value): string {
    return htmlentities($value ?? '', double_encode: false);
}

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
            display: block;
            padding: 4px;
            text-wrap: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 150%;
        }

        time {
            display: inline-block;
            width: 70px;
            color: slategray;
            font-size: 0.8rem;
        }


        feed-domain {
            color: slategray;
            font-size: 0.8rem;
        }

        feed-domain a {
            color: inherit;
            text-decoration: none;
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

        <feed-item>
            <time datetime="<?= $post->publishedAt->format('c'); ?>">
                <?= $post->publishedAt->format('j'); ?><span style="font-size: 0.7rem;"><?= $post->publishedAt->format('S'); ?></span> <?= $post->publishedAt->format('M'); ?>
            </time>

            <a href="<?= e($post->link); ?>" title="<?= e($post->shortDescription); ?>">
                <?= e(mb_strimwidth($post->title, 0, 70, '…')) ?>
            </a>

            <feed-domain>
                <a href="?domain=<?= e(parse_url($post->link, PHP_URL_HOST)); ?>">
                    (<?= e(parse_url($post->link, PHP_URL_HOST)) ?>)
                </a>
            </feed-domain>
        </feed-item>
    <?php endforeach; ?>
</feed-items>

</body>
</html>