<?php

namespace Makotokw\YouTube;

class MobileVideoTest extends \PHPUnit_Framework_TestCase
{
    public function testWatchPage()
    {
        $feedUrl = sprintf('http://gdata.youtube.com/feeds/api/standardfeeds/JP/%s', 'most_viewed');

        $videos = array();

        GData::fetchFeedByUrl(
            $feedUrl,
            array(),
            50,
            function ($index, $video) use (&$videos) {
                $videos[] = $video;
            }
        );

        $this->assertGreaterThan(10, count($videos), 'fetch most_viewed');

        $key = array_rand($videos, 1);

        $video =  $videos[$key];
        $videoId = $video['video_id'];

        echo '# Test ' . $videoId . ':' . $video['title'] . PHP_EOL;

        $url = MobileVideo::fetchVideoUrl($videoId);
        $this->assertNotEmpty($url, "MobileVideo::fetchVideoUrl('${videoId}')");
    }
}
