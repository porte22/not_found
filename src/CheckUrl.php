<?php

namespace Porte22\NotFound;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

class CheckUrl
{
    private $client;
    private $concurrentRequests = 50;
    private $result = [];
    private $list = [];
    private $keys = ['forum', 'photos', 'photo', 'usato', 'logout', 'foto'];
    private $makerList;

    private $fixedUrl=[];

    public function __construct($fileMaker)
    {
        $this->client = new Client([
            'timeout' => 10,
            'concurrency' => $this->concurrentRequests
        ]);

        $makers = file($fileMaker);
        foreach ($makers as $currentMaker) {
            $currentMaker = trim($currentMaker);
            $this->makerList[$currentMaker] = $currentMaker;
        }
    }

    private function isUrlEnabled($url)
    {
        $isValid = true;
        foreach ($this->keys as $currentKey) {
            if (stripos($url, $currentKey) !== false) {
                return false;
            }
        }
        return $isValid;
    }

    public function loadUrlInErrorFromCsv($pattern, $urlKey)
    {
        foreach (glob($pattern) as $filename) {
            $csv = array_map('str_getcsv', file($filename));
            $header = array_flip(array_shift($csv));

            foreach ($csv as $item) {
                $url = $item[$header[$urlKey]];
                $this->analizeUrl($url);
            }
        }
    }

    private function addUrl($url)
    {
        $this->list[] = $url;
    }

    public function analizeUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            if ($this->isUrlEnabled($url)) {
                $this->addUrl($url);
            }
        }
    }


    public function getList()
    {
        return array_unique($this->list);
    }

    private function isUrlMakerModel($currentUrl){

            $url = explode('/', $currentUrl);
            //check case like this https://it.motor1.com/peugeot/peugeot-407-coupe/
            if (isset($url['4']) && $url['3'] == substr($url['4'],0,strlen($url['3']))){
                $url['4'] = substr($url['4'],strlen($url['3'])+1);
                $url = implode('/', $url);
                $response = $this->getUrlInError([$url]);
                if (isset($response['ok'])){
                    return $url;
                }
            }

        return false;
    }


    public function fixMaker($fileError, $fixedFile)
    {
        $file = file($fileError);
        $fp = fopen($fixedFile,'w+');
        fwrite($fp,"SOURCE,DESTINATION\n");
        foreach ($file as $currentUrl) {
            $response = $this->getUrlInError([$currentUrl]);
            if (isset($response['ko'])) {
                $currentUrl = trim($currentUrl);
                if (($url = $this->isUrlMakerModel($currentUrl)) || ($url = $this->isValidWithoutLastPart($currentUrl))) {
                    $this->fixedUrl[$currentUrl] = $url;
                    fwrite($fp,$currentUrl.','.$url."\n");
                }
            }
        }
        fclose($fp);
        return $this->fixedUrl;
    }

    private function isValidWithoutLastPart($currentUrl){

        $url = $this->removeLastPartOfUrl($currentUrl);
        $response =$this->getUrlInError([$url]);
        if (isset($response['ok'])){
            return $url;
        }
        return false;
    }

    private function isMaker($url)
    {
        $url = explode('/', $url);
        print_r($this->makerList);
        if (isset($url['3']) && isset($this->makerList[$url['3']])) {
            return true;
        }
        return false;
    }

    private function removeLastPartOfUrl($url)
    {
        $url = explode('/', $url);
        $last = array_pop($url);
        if (trim($last)==''){
            array_pop($url);
        }
        return implode('/', $url);
    }

    public function getUrlInError($list, $concurrentRequests = 1)
    {
        $result = [];
        while (count($list) > 0) {
            /** @var Request[] $requestsToDo */
            $requestsToDo = [];
            $subLinks = array_splice($list, 0, $concurrentRequests);

            foreach ($subLinks as $subLink) {
                $requestsToDo[$subLink] = new Request('HEAD', $subLink);
            }

            $pool = new Pool($this->client, $requestsToDo, [
                'concurrency' => $concurrentRequests,
                'timeout' => $this->client->getConfig('timeout'),
                'fulfilled' => function ($response, $index) use (&$result) {
                    $result['ok'][$index] = 'yeah!';
                },
                'rejected' => function ($reason, $index) use (&$result) {
                    $result['ko'][$index] = 'booou!!';
                }
            ]);

            $promise = $pool->promise();
            $promise->wait(false);
        }
        return $result;
    }


    public function writeUrlInError($fileErrorMaker, $fileErrorUnknown)
    {
        $urlInError = $this->getUrlInError(array_unique($this->list), $this->concurrentRequests);
        ksort($urlInError['ko']);
        $fpMaker = fopen($fileErrorMaker, 'w+');
        $fpUnknown = fopen($fileErrorUnknown, 'w+');
        foreach ($urlInError['ko'] as $currentUrl => $value) {
            if ($this->isMaker($currentUrl)) {
                fwrite($fpMaker, $currentUrl . "\n");
            } else {
                fwrite($fpUnknown, $currentUrl . "\n");
            }
        }
        fclose($fpMaker);
        fclose($fpUnknown);
    }

    public function showUrlInError($fileError)
    {
        $file = file($fileError);
        foreach ($file as $currentUrl) {
            ?>
            <div><a href="<?php echo $currentUrl; ?>"><?php echo $currentUrl; ?></a></div>
            <?php
        }
    }

}