#!/usr/bin/env php
<?php

declare(strict_types=1);

const ENV_ALFRED_HABR_FEED = 'ALFRED_HABR_FEED';

const FEED_BEST = 'best';
const FEED_ALL = 'all';

const FEED_URLS = [
    FEED_BEST => 'https://habr.com/ru/rss/best/daily/?fl=ru%2Cen',
    FEED_ALL  => 'https://habr.com/ru/rss/all/all/?fl=ru',
];

/**
 * @throws Exception
 * @return string[]
 */
function parseFeed(string $url): array
{
    $feeds = simplexml_load_string(file_get_contents($url));

    if ($feeds === false) {
        return [];
    }

    $results['items'] = [];
    foreach ($feeds->channel->item as $item) {
        $guid = (string) $item->guid;
        $result['uid'] = $guid;
        $title = (string) $item->title;
        $result['title'] = $title;

        $result['subtitle'] = sprintf(
            "@%s at %s",
            (string) $item->children('dc', true)->creator,
            strftime('%c', (new DateTimeImmutable((string) $item->pubDate))->getTimestamp())
        );
        $link = (string) $item->link;
        $result['arg'] = $link;
        $result['autocomplete'] = $title;
        $result['quicklookurl'] = $link;
        $result['mods'] = [
            "alt" => [
                "arg" => $title

            ],
            "control" => [
                "arg" => $guid
            ],
        ];

        $results['items'][] = $result;
    }

    return $results;
}

function getURL(): string
{
    global $argv;

    $feedName = !empty($argv[1]) ? $argv[1] : getenv(ENV_ALFRED_HABR_FEED);

    if (!empty($feedName) && array_key_exists($feedName, FEED_URLS)) {
        return FEED_URLS[$feedName];
    }

    return FEED_URLS[FEED_BEST];
}

$url = getURL();
$alfredJson = parseFeed($url);
$alfredJson['variables'][ENV_ALFRED_HABR_FEED] = array_flip(FEED_URLS)[$url];

echo json_encode($alfredJson);
