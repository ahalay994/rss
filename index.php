<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$GLOBALS['city'] = isset($_GET['city']) ? $_GET['city'] : 'Minsk';
$GLOBALS['limit'] = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;

$content = @file_get_contents("https://api.weatherbit.io/v2.0/forecast/daily?city=" . $city . ",Belarus&lang=ru&key=50e6e3b29ddc4521acbf49edc20fdcba");
echo convert_jsonfeed_to_rss($content)."\n";

function convert_jsonfeed_to_rss($content = NULL, $max = NULL)
{
    //Test if the content is actual JSON
    json_decode($content);
    if( json_last_error() !== JSON_ERROR_NONE) return FALSE;

    //Decode the feed to a PHP array
    $jf = json_decode($content, TRUE);

    //Get the latest item publish date to use as the channel pubDate
    $latestDate = time();
    $lastBuildDate = date(DATE_RSS, $latestDate);

    //Create the RSS feed
    $xmlFeed = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>');
    $xmlFeed->addChild("channel");

    //Required elements
    $xmlFeed->channel->addChild("title", 'Погода v1.0');
    $xmlFeed->channel->addChild("pubDate", $lastBuildDate);
    $xmlFeed->channel->addChild("lastBuildDate", $lastBuildDate);

    //Optional elements
    if (isset($jf['description'])) $xmlFeed->channel->description = $jf['description'];

    //Items
    foreach ($jf['data'] as $id => $item) {
        if ($id == $GLOBALS['limit']) {
            break;
        }
        $newItem = $xmlFeed->channel->addChild('item');

        //Standard stuff
        $newItem->addChild('guid', $id);
        $newItem->addChild('title', 'Прогноз погоды на ' . date('d.m.Y', strtotime($item['valid_date'])));
        $newItem->addChild('description', generateBody(
            $item['wind_gust_spd'],
            $item['wind_spd'],
            $item['temp'],
            $item['min_temp'],
            $item['max_temp'],
            $item['pop'],
            $item['rh']
        ));
        $newItem->addChild('image', 'https://www.weatherbit.io/static/img/icons/' . $item['weather']['icon'] . '.png');
        $newItem->addChild('pubDate', date('d.m.Y H:i', time()));
    }

    //Make the output pretty
    $dom = new DOMDocument("1.0");
    $dom = dom_import_simplexml($xmlFeed)->ownerDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    return $dom->saveXML();
}

function generateBody($wind_gust_spd, $wind_spd, $temp, $min_temp, $max_temp, $pop, $rh) {
    return '
        <div>
            <div class="wind_gust_spd">Скорость порыва ветра: ' . $wind_gust_spd . 'м/с</div>
            <div class="wind_spd">Скорость ветра: ' . $wind_spd . 'м/с</div>
            <div class="temp">Средняя температура: ' . $temp . '℃</div>
            <div class="min_temp">Минимальная температура: ' . $min_temp . '℃</div>
            <div class="max_temp">Максимальная температура: ' . $max_temp . '℃</div>
            <div class="pop">Вероятность осадков: ' . $pop . '%</div>
            <div class="rh">Средняя относительная влажность: ' . $rh . '%</div>
        </div>
    ';
}
