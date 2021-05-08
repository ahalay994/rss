<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function convert_jsonfeed_to_rss($content = NULL, $max = NULL)
{
    //Test if the content is actual JSON
    json_decode($content);
    if( json_last_error() !== JSON_ERROR_NONE) return FALSE;

    //Now, is it valid JSONFeed?
    $jsonFeed = json_decode($content, TRUE);
//    if (!isset($jsonFeed['version'])) return FALSE;
//    if (!isset($jsonFeed['title'])) return FALSE;
//    if (!isset($jsonFeed['items'])) return FALSE;

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
//    if (isset($jf['home_page_url'])) $xmlFeed->channel->link = $jf['home_page_url'];

    //Items
    foreach ($jf['data'] as $id => $item) {
        $newItem = $xmlFeed->channel->addChild('item');

        //Standard stuff
        $newItem->addChild('guid', $id);
        $newItem->addChild('title', 'Прогноз погоды на ' . date('d.m.Y', strtotime($item['valid_date'])));
//        if (isset($item['content_text'])) $newItem->addChild('description', '');
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
//        if (isset($item['date_published'])) $newItem->addChild('pubDate', $item['date_published']);
//        if (isset($item['url'])) $newItem->addChild('link', $item['url']);

        //Enclosures?
//        if(isset($item['attachments'])) {
//            foreach($item['attachments'] as $attachment) {
//                $enclosure = $newItem->addChild('enclosure');
//                $enclosure['url'] = $attachment['url'];
//                $enclosure['type'] = $attachment['mime_type'];
//                $enclosure['length'] = $attachment['size_in_bytes'];
//            }
//        }
    }

    //Make the output pretty
    $dom = new DOMDocument("1.0");
    $dom = dom_import_simplexml($xmlFeed)->ownerDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    return $dom->saveXML();
}

$content = @file_get_contents("https://api.weatherbit.io/v2.0/forecast/daily?city=Minsk,Belarus&lang=ru&key=50e6e3b29ddc4521acbf49edc20fdcba");
echo convert_jsonfeed_to_rss($content)."\n";

//$feedurl = "http://test/";
//$feedme = file_get_contents($feedurl);
//var_dump(1);
//if($feedme):
//    $fh = fopen('./newfeed.xml', 'w+'); //create new file if not exists
//    fwrite($fh, $feedme) or die("Failed to write contents to new file"); //write contents to new XML file
//    fclose($fh) or die("failed to close stream resource"); //close resource stream
//else:
//    die("Failed to read contents of feed at $feedurl");
//endif;
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
