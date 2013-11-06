<?php

namespace Makotokw\YouTube;


class Video
{
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
     * @param $videoId
     * @return int
     */
    public static function validateVideoId($videoId)
    {
        return preg_match('/^[\w-]{11}$/', $videoId);
    }
}
