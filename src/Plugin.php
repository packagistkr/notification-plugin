<?php


namespace Packagistkr\Notification;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface
{
    const PACKAGIST_KR_NOTICES_API = "https://api.packagist.kr/notices";
    const CONNECTION_LIMIT_TIME = 2;
    const LATEST_NOTICE_JSON_NAME = 'latest_notice.json';

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {

        $latestNotice = $this->getLatestNotice();

        foreach ($this->getMirrorNotices() as $notice) {
            if ($this->isNotValidNotice($latestNotice, $notice)) {
                continue;
            }

            $io->write($notice->title . ($notice->link ? " [자세히보기:" . $notice->link . "]" : ""));
        }

        if (isset($notice)) {
            $this->saveLatestNotice($notice);
        }
    }

    protected function getLatestNotice()
    {
        if (file_exists($this->getCachePath() . self::LATEST_NOTICE_JSON_NAME))
            return json_decode(file_get_contents($this->getCachePath() . self::LATEST_NOTICE_JSON_NAME));
        return null;
    }

    private function getCachePath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR;
    }

    protected function getMirrorNotices()
    {


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::PACKAGIST_KR_NOTICES_API);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECTION_LIMIT_TIME);

        $response = curl_exec($ch);
        curl_close($ch);


        if (!$response)
            return [];


        return json_decode($response);
    }

    /**
     * @param $latestNotice
     * @param $notice
     * @return bool
     */
    private function isNotValidNotice($latestNotice, $notice)
    {
        return $latestNotice && $latestNotice->created_at >= $notice->created_at;
    }

    protected function saveLatestNotice($notice)
    {
        file_put_contents($this->getCachePath() . self::LATEST_NOTICE_JSON_NAME, json_encode($notice));
    }
}