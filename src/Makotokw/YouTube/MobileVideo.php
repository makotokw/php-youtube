<?php

namespace Makotokw\YouTube;

use Httpful\Request;

class MobileVideo
{
    const DEFAULT_MOBILE_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3';

    const QUALITY_SMALL = 0;
    const QUALITY_MEDIUM = 1;
    const QUALITY_LARGE = 2;

    /**
     * @var string
     */
    protected $videoId;

    /**
     * @var array
     */
    protected $contentAttributes;

    /**
     * @param string $videoId
     */
    public function __construct($videoId)
    {
        $this->videoId = $videoId;
        $this->contentAttributes = array();
    }

    /**
     * @param string $videoId
     * @param int $quality
     * @param string $userAgent
     * @return string
     */
    public static function fetchVideoUrl($videoId, $quality = self::QUALITY_LARGE, $userAgent = null)
    {
        $v = new MobileVideo($videoId);
        $v->retriveteDataFromWatchPage($userAgent);
        return $v->findMediaUrlByQuality($quality);
    }

    /**
     * @param int $quality
     * @return bool|string
     */
    public function findMediaUrlByQuality($quality)
    {
        if (empty($this->contentAttributes)) {
            return false;
        }
        $playerData = @$this->contentAttributes['player_data'];
        if (!$playerData) {
            return false;
        }
        $videos = @$playerData['fmt_stream_map'];
        if (!is_array($videos) && empty($videos) == 0) {
            return false;
        }

        $videoIndex = 0;
        switch ($quality) {

            case self::QUALITY_SMALL:
                $videoIndex = count($videos)-1;
                break;

            case self::QUALITY_MEDIUM:
                $videoIndex = min(count($videos)-1, 1);
                break;

            default:
                break;
        }

        $video = $videos[$videoIndex];
        if (!$video) {
            return false;
        }
        return @$video['url'];
    }

    /**
     * @param string $userAgent
     */
    public function retriveteDataFromWatchPage($userAgent = null)
    {
        if (!$userAgent) {
            $userAgent = self::DEFAULT_MOBILE_USER_AGENT;
        }

        $watchUrl = 'http://m.youtube.com/watch?v=' . $this->videoId;

        $response = Request::get($watchUrl)
            ->addHeaders(
                array(
                    'User-Agent' => $userAgent,
                )
            )
            ->send();

        if (!empty($response)) {
            $this->parseWatchPage($response);
        }
    }

    protected function parseWatchPage($html)
    {
        $jsonString = $this->extractBootstrapData($html);
        if (!$jsonString) {
            $jsonString = $this->extractPiggybackData($html);
        }

        if (!$jsonString) {
            return;
        }

        $jsonData = json_decode($jsonString, true);
        if (!$jsonData) {
            return;
        }
        $this->contentAttributes = $jsonData['content'];
    }

    protected function extractBootstrapData($html)
    {
        $jsonString = false;
        $startString = "var bootstrap_data = \")]}'";
        $startPos = strpos($html, $startString);

        if ($startPos === false) {
            $startString = str_replace(' ', '', $startString);
            $startPos = strpos($html, $startString);
        }
        if ($startPos !== false) {
            $startPos += strlen($startString);
            $endPost = strpos($html, "\";", $startPos);
            if ($endPost !== false) {
                $jsonString = substr($html, $startPos, $endPost - $startPos);
                $jsonString = self::unescape($jsonString);
            }
        }
        return $jsonString;
    }

    protected function extractPiggybackData($html)
    {
        $jsonString = false;
        $startString = "ls.setItem('PIGGYBACK_DATA', \")]}'";
        $startPos = strpos($html, $startString);

        if ($startPos === false) {
            $startString = str_replace(' ', '', $startString);
            $startPos = strpos($html, $startString);
        }
        if ($startPos !== false) {
            $startPos += strlen($startString);
            $endPost = strpos($html, "\";", $startPos);
            if ($endPost !== false) {
                $jsonString = substr($html, $startPos, $endPost - $startPos);
                $jsonString = self::unescape($jsonString);
            }
        }
        return $jsonString;
    }

    protected static function unescape($source)
    {
        $source = str_replace("\\\\\"", "'", $source);
        $source = stripcslashes($source);
        return $source;
    }
}
