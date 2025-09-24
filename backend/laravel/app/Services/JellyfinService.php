<?php

namespace App\Services;

use App\DataTransferObjects\Jellyfin\JellyfinSessionData;
use App\DataTransferObjects\Jellyfin\JellyfinSubtitleData;
use App\Helpers\Language\LanguageConfig;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class JellyfinService
{
    private $jellyfinLanguageCodes = [];

    private $apiKey;

    private $apiHost;

    // TODO: refactor and remove unused $session data
    public function __construct()
    {
        $this->jellyfinLanguageCodes = LanguageConfig::all()->pluck('name', 'jellyfinCode')->toArray();

        $this->apiKey = Setting::query()
            ->where('name', 'jellyfinApiKey')
            ->firstOrFail()
            ->decode();

        $this->apiHost = Setting::query()
            ->where('name', 'jellyfinHost')
            ->firstOrFail()
            ->decode();
    }

    public function makeRequest($method, $url): mixed
    {
        $response = '';

        if ($method == 'GET') {
            $response = Http::withHeaders([
                'Authorization' => 'MediaBrowser Token="' . $this->apiKey . '", Client="LinguaCafe", Device="Test", DeviceId="deviceIdPlaceholder", Version="0.1"',
            ])->get($this->apiHost . $url);
        }

        if ($method == 'POST') {
            $response = Http::withHeaders([
                'Authorization' => 'MediaBrowser Token="' . $this->apiKey . '", Client="LinguaCafe", Device="Test", DeviceId="deviceIdPlaceholder", Version="0.1"',
            ])->post($this->apiHost . $url);
        }

        return $response->json();
    }

    public function getJellyfinCurrentlyPlayedSubtitles(): array
    {
        $calculatedSessions = [];
        $sessions = $this->makeRequest('GET', '/Sessions');
        for ($sessionCounter = 0; $sessionCounter < count($sessions); $sessionCounter++) {
            if (!array_key_exists('NowPlayingItem', $sessions[$sessionCounter])) {
                continue;
            }

            if ($sessions[$sessionCounter]['NowPlayingItem']['MediaType'] !== 'Video') {
                continue;
            }

            if ($sessions[$sessionCounter]['NowPlayingItem']['Type'] == 'Episode') {
                $seriesName = $sessions[$sessionCounter]['NowPlayingItem']['SeriesName'];
                $seriesEpisode = $sessions[$sessionCounter]['NowPlayingItem']['IndexNumber'];
                $seriesSeason = str_replace('Season ', '', $sessions[$sessionCounter]['NowPlayingItem']['SeasonName']);
                $movieName = null;
            } else {
                $seriesName = null;
                $seriesEpisode = null;
                $seriesSeason = null;
                $movieName = $sessions[$sessionCounter]['NowPlayingItem']['Name'];
            }

            $session = new JellyfinSessionData(
                client: $sessions[$sessionCounter]['Client'],
                userName: $sessions[$sessionCounter]['UserName'],
                userId: $sessions[$sessionCounter]['UserId'],
                title: $sessions[$sessionCounter]['NowPlayingItem']['Name'],
                type: $sessions[$sessionCounter]['NowPlayingItem']['Type'],
                seriesName: $seriesName,
                seriesEpisode: $seriesEpisode,
                seriesSeason: $seriesSeason,
                movieName: $movieName,
                runTimeTicks: $sessions[$sessionCounter]['NowPlayingItem']['RunTimeTicks'],
                nowPlayingItemId: $sessions[$sessionCounter]['NowPlayingItem']['Id'],
                sessionId: $sessions[$sessionCounter]['Id'],
                mediaSourceId: $sessions[$sessionCounter]['PlayState']['MediaSourceId'],
                subtitles: [],
            );

            $calculatedSessions[] = $session;
            $mediaSource = $this->makeRequest('GET', '/Items/' . $session->nowPlayingItemId . '/PlaybackInfo?userId=' . $session->userId);
            $mediaSource = $mediaSource['MediaSources'][0];

            for ($subtitleCounter = 0; $subtitleCounter < count($mediaSource['MediaStreams']); $subtitleCounter++) {
                if ($mediaSource['MediaStreams'][$subtitleCounter]['Type'] !== 'Subtitle' ||
                    !$mediaSource['MediaStreams'][$subtitleCounter]['IsExternal']) {
                    continue;
                }

                $subtitleText = $this->makeRequest('GET', '/Videos/' . $session->nowPlayingItemId . '/' . $session->mediaSourceId . '/Subtitles/ ' . $mediaSource['MediaStreams'][$subtitleCounter]['Index'] . '/0/Stream.js');

                // add language for subtitles that Jellyfin did not recognise
                if (!isset($mediaSource['MediaStreams'][$subtitleCounter]['Language'])) {
                    $mediaSource['MediaStreams'][$subtitleCounter]['Language'] = $mediaSource['MediaStreams'][$subtitleCounter]['Title'];
                }

                // retrieve language. if not possible, use the jellyfin language code instead,
                // so it can be viewed as an error message in the console and added to
                // jellyfinLanguageCodes.
                if (array_key_exists($mediaSource['MediaStreams'][$subtitleCounter]['Language'], $this->jellyfinLanguageCodes)) {
                    $language = $this->jellyfinLanguageCodes[$mediaSource['MediaStreams'][$subtitleCounter]['Language']];
                    $supportedLanguage = true;
                } else {
                    $language = $mediaSource['MediaStreams'][$subtitleCounter]['Language'];
                    $supportedLanguage = false;
                }

                $subtitle = new JellyfinSubtitleData(
                    language: $language,
                    supportedLanguage: $supportedLanguage,
                    text: $subtitleText['TrackEvents'],
                );

                $calculatedSessions[count($calculatedSessions) - 1]->subtitles[] = $subtitle;
            }
        }

        return $calculatedSessions;
    }
}
