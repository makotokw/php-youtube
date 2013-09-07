<?php

namespace Makotokw\YouTube;

class MobileVideoTest extends \PHPUnit_Framework_TestCase
{
    public function testWatchPage()
    {
        $url = MobileVideo::fetchVideoUrl('NjXQcGmffu0');
        $this->assertNotEmpty($url, "MobileVideo::fetchVideoUrl('NjXQcGmffu0')");
    }
}
