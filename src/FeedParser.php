<?php

declare(strict_types=1);

final class FeedParser
{
    /** @return Post[] */
    public static function run(array $feedUrls): array
    {
        $multiHandle = curl_multi_init();

        $curlHandles = [];

        foreach ($feedUrls as $feedUrl) {
            $curlHandle = curl_init();

            curl_setopt_array($curlHandle, [
                CURLOPT_URL => $feedUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT => 'RSS Feed Reader/1.0',
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_ENCODING => 'gzip',
            ]);

            curl_multi_add_handle($multiHandle, $curlHandle);

            $curlHandles[] = $curlHandle;
        }

        do {
            $status = curl_multi_exec($multiHandle, $pendingRequests);

            if (curl_multi_select($multiHandle, 0.1) === -1) {
                usleep(5_000);
            }
        } while ($pendingRequests > 0 && $status === CURLM_OK);

        $posts = [];

        foreach ($curlHandles as $handle) {
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $content = curl_multi_getcontent($handle);

            if ($httpCode === 200) {
                $posts = [...$posts, ...self::getFeed($content)->posts];
            }

            curl_multi_remove_handle($multiHandle, $handle);
            curl_close($handle);
        }

        curl_multi_close($multiHandle);

        return $posts;
    }

    private static function getFeed(string $xmlString): Feed
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            throw new Exception('Failed to parse XML: ' . $errors[0]->message);
        }

        return match($xml->getName()) {
            'feed' => new AtomParser($xml)->parse(),
            'rss' => new RssParser($xml)->parse(),
        };
    }
}
