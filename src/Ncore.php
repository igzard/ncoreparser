<?php

declare(strict_types=1);

namespace Igzard\Ncore;

use GuzzleHttp\Exception\GuzzleException;
use Igzard\Ncore\Common\Downloader;
use Igzard\Ncore\Entity\Collection\TorrentCollection;
use Igzard\Ncore\Entity\Search;
use Igzard\Ncore\Exception\EmptyPasskeyException;
use Igzard\Ncore\Exception\RequestException;
use Igzard\Ncore\Service\RssParser;

class Ncore
{
    private const RSS_URL = 'https://finderss.it.cx/';

    private string $passkey;
    private \GuzzleHttp\Client $client;
    private RssParser $rssParser;
    private Downloader $downloader;

    /**
     * @throws EmptyPasskeyException
     */
    public function __construct(string $passkey)
    {
        $this->validatePasskey($passkey);

        $this->passkey = $passkey;
        $this->client = new \GuzzleHttp\Client();
        $this->rssParser = new RssParser($this->passkey);
        $this->downloader = new Downloader();
    }

    /**
     * @throws GuzzleException
     * @throws RequestException
     */
    public function search(Search $search): TorrentCollection
    {
        $options = [
            's='.$search->getSearch(),
            'cat='.$search->getCat()->value(),
        ];

        $response = $this->client->request('GET', self::RSS_URL.'?&'.implode('&', $options).',');

        if ($response->getStatusCode() !== 200) {
            throw RequestException::create();
        }

        return $this->rssParser->parse($response);
    }

    /**
     * @throws GuzzleException
     * @throws RequestException
     */
    public function download(Search $search, string $path, string $filename): void
    {
        $torrents = $this->search($search);

        $this->downloader->download($path, $filename, $torrents->first()->getLink());
    }

    /**
     * @throws EmptyPasskeyException
     */
    private function validatePasskey(string $passkey): void
    {
        if (empty($passkey)) {
            EmptyPasskeyException::create();
        }
    }
}