#!/usr/bin/env php
<?php

declare(strict_types=1);

const ENV_ALFRED_HABR_FEED = 'ALFRED_HABR_FEED';

const FEED_BEST = 'best';
const FEED_EVERYTHING = 'all';

const FEED_URLS = [
    FEED_BEST       => 'https://habr.com/ru/rss/best/%s/?fl=ru&limit=100&with_hubs=true:&with_tags=true:',
    FEED_EVERYTHING => 'https://habr.com/ru/rss/all/%s/?fl=ru&limit=100&with_hubs=true:&with_tags=true:',
];

const FEED_BEST_PERIOD_DAILY = 'daily';
const FEED_BEST_PERIOD_WEEKLY = 'weekly';
const FEED_BEST_PERIOD_MONTHLY = 'monthly';
const FEED_BEST_PERIOD_YEARLY = 'yearly';
const FEED_BEST_PERIOD_ALL_TIME = 'alltime';

const FEED_EVERYTHING_PERIOD_ALL = 'all';
const FEED_EVERYTHING_PERIOD_TOP0 = 'top0';
const FEED_EVERYTHING_PERIOD_TOP10 = 'top10';
const FEED_EVERYTHING_PERIOD_TOP25 = 'top25';
const FEED_EVERYTHING_PERIOD_TOP50 = 'top50';
const FEED_EVERYTHING_PERIOD_TOP100 = 'top100';

const FEED_PERIODS = [
    FEED_BEST_PERIOD_DAILY,
    FEED_BEST_PERIOD_WEEKLY,
    FEED_BEST_PERIOD_MONTHLY,
    FEED_BEST_PERIOD_YEARLY,
    FEED_BEST_PERIOD_ALL_TIME,

    FEED_EVERYTHING_PERIOD_ALL,
    FEED_EVERYTHING_PERIOD_TOP0,
    FEED_EVERYTHING_PERIOD_TOP10,
    FEED_EVERYTHING_PERIOD_TOP25,
    FEED_EVERYTHING_PERIOD_TOP50,
    FEED_EVERYTHING_PERIOD_TOP100,
];

const FEED_DEFAULT_PERIODS = [
    FEED_BEST       => FEED_BEST_PERIOD_DAILY,
    FEED_EVERYTHING => FEED_EVERYTHING_PERIOD_ALL,
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
            "alt"     => [
                "arg" => $title,

            ],
            "control" => [
                "arg" => $guid,
            ],
        ];

        $results['items'][] = $result;
    }

    return $results;
}

function getURL(): string
{
    global $argv;

    $feedAndPeriod = !empty($argv[1]) ? $argv[1] : getenv(ENV_ALFRED_HABR_FEED);

    if (empty($feedAndPeriod)) {
        return sprintf(FEED_URLS[FEED_BEST], FEED_DEFAULT_PERIODS[FEED_BEST]);
    }

    [$feedName, $feedPeriod] = explode('-', $feedAndPeriod);

    $feedPeriod = empty($feedPeriod) ? FEED_DEFAULT_PERIODS[$feedName] : $feedPeriod;

    if (
        !empty($feedName)
        && array_key_exists($feedName, FEED_URLS)
        && !empty($feedPeriod)
        && in_array($feedPeriod, FEED_PERIODS, true)
    ) {
        return sprintf(FEED_URLS[$feedName], $feedPeriod);
    }
}

$url = getURL();
$alfredJson = parseFeed($url);
$alfredJson['variables'][ENV_ALFRED_HABR_FEED] = $url;

echo json_encode($alfredJson);
