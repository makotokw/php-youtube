<?php

namespace Makotokw\YouTube;

class GData
{
    const API_URL = 'https://gdata.youtube.com/feeds/api/videos';

    const ORDERBY_RELEVANCE = 'relevance';
    const ORDERBY_PUBLISHED = 'published';
    const ORDERBY_VIEWCOUNT = 'viewCount';
    const ORDERBY_RATING = 'rating';

    /**
     * @param string $apiUrl
     * @param array $params
     * @param int $maxResults
     * @param callable $function
     */
    protected static function fetchFeed($apiUrl, array $params, $maxResults, $function)
    {
        $requestViaMaxResults = ($maxResults > 50) ? 50 : $maxResults;
        $params = array_merge(
            array(
                'orderby'     => self::ORDERBY_VIEWCOUNT,
                'start-index' => '1',
                'max-results' => $requestViaMaxResults,
                'v'           => '2',
                'strict'      => false,
                'alt'         => 'json'
            ),
            $params
        );

        $fetch = function ($startIndex) use ($apiUrl, $params, $function) {
            $params['start-index'] = $startIndex;
            $u = $apiUrl . '?' . http_build_query($params, '', '&');

            $content = file_get_contents($u);

            $data = json_decode($content, true);
            $feed = @$data['feed'];

            $totalResults = 0;

            if ($feed) {
                $totalResults = self::parseInteger($feed, 'openSearch$totalResults');
                if ($totalResults > 0) {
                    $index = 0;
                    foreach ($feed['entry'] as $entry) {
                        $videoData = self::videoDataFromFeedEntry($entry);
                        $function($startIndex + $index, $videoData);
                        $index++;
                    }
                }
            }
            return $totalResults;
        };

        $totalResults = $fetch(1);

        if ($totalResults > $requestViaMaxResults) {
            $itemsOfPage = 50;
            $requestCount = intval(ceil(min($totalResults, $maxResults) / $itemsOfPage)) - 1;
            $startIndex = $itemsOfPage + 1;
            while ($requestCount > 0) {
                $fetch($startIndex);
                $startIndex += $itemsOfPage;
                $requestCount--;
            }
        }
    }

    /**
     * fetch
     * @param string $url
     * @param array $params
     * @param int $maxResults
     * @param callable $itemFunction
     * @return bool
     */
    public static function fetchFeedByUrl($url, array $params = array(), $maxResults = 200, $itemFunction = null)
    {
        $u = parse_url($url);
        if (!empty($u['query'])) {
            $url = sprintf('%s://%s%s', $u['scheme'], $u['host'], $u['path']);
            $queryParams = array();
            parse_str($u['query'], $queryParams);
            $params = array_merge($queryParams, $params);
        }

        self::fetchFeed(
            $url,
            $params,
            $maxResults,
            function ($index, $videoData) use ($itemFunction) {
                /**
                 * @var callable $itemFunction
                 */
                if ($itemFunction) {
                    $itemFunction($index, $videoData);
                }
            }
        );
        return true;
    }

    /**
     * @param string $videoId
     * @param callable $itemFunction
     * @return bool
     */
    public static function fetchFeedByVideoId($videoId, $itemFunction = null)
    {
        // http://gdata.youtube.com/feeds/api/videos/videoid
        $params = array(
            'v'           => '2',
            'strict'      => false,
            'alt'         => 'json'
        );
        $url = self::API_URL . '/' . $videoId. '?' . http_build_query($params, '', '&');

        $content = file_get_contents($url);
        $data = json_decode($content, true);
        if ($entry = @$data['entry']) {
            if ($videoData = self::videoDataFromFeedEntry($entry)) {
                if ($itemFunction) {
                    $itemFunction(0, $videoData);
                }
                return $videoData;
            }
        }
        return false;
    }

    protected static function videoDataFromFeedEntry($entry)
    {
        $mediaGroup = $entry['media$group'];
        $videoData = array();
        $videoData['video_id'] = self::parseCdata($mediaGroup, 'yt$videoid');
        $videoData['thumbnail_url'] = self::parseThumbnailUrl($mediaGroup, 'media$thumbnail');
        $videoData['title'] = self::parseCdata($mediaGroup, 'media$title');
        $videoData['description'] = self::parseCdata($mediaGroup, 'media$description');
        $videoData['duration'] = self::parseDuration($mediaGroup, 'yt$duration');
        $videoData['published_at'] = self::parseTime($entry, 'published');

        if (array_key_exists('yt$statistics', $entry)) {
            $videoData = array_merge(
                $videoData,
                $entry['yt$statistics']
            );
        }
        if (array_key_exists('yt$rating', $entry)) {
            $videoData = array_merge(
                $videoData,
                $entry['yt$rating']
            );
        }

        // fill
        foreach (array('favoriteCount', 'viewCount', 'numDislikes', 'numLikes') as $key) {
            if (!array_key_exists($key, $videoData)) {
                $videoData[$key] = 0;
            }
        }
        return $videoData;
    }

    protected static function parseCdata($entry, $key)
    {
        return (string)$entry[$key]['$t'];
    }

    protected static function parseInteger($entry, $key)
    {
        return intval($entry[$key]['$t']);
    }

    protected static function parseDuration($entry, $key)
    {
        return intval($entry[$key]['seconds']);
    }

    protected static function parseTime($entry, $key)
    {
        return strtotime($entry[$key]['$t']);
    }

    protected static function parseThumbnailUrl($entry, $key)
    {
        return (string)$entry[$key][0]['url'];
    }
}
