<?php
/**
 * UCID: dns33
 * Date: 04/19/2026
 * Summary: Wrapper functions for YouTube Search & Download (RapidAPI).
 *          Fetch channel search results, then transform into DB-ready arrays
 *          for IT202_M2_YT_Channels and IT202_M2_YT_Videos tables.
 *
 * Requires: api_helper.php (which loads load_api_keys.php internally)
 */

// make sure get() exists
require_once(__DIR__ . "/api_helper.php");

/**
 * Picks the "best" thumbnail URL from a thumbnails array.
 * Typically we pick the last item (highest res) if present.
 */
function yt_pick_best_thumbnail($thumbnails)
{
    if (!is_array($thumbnails) || empty($thumbnails)) {
        return null;
    }
    $last = end($thumbnails);
    return $last["url"] ?? null;
}

/**
 * Extract a channel row for IT202_M2_YT_Channels from API decoded JSON.
 * NOTE: channel_id is NOT always returned by some endpoints. If your endpoint
 * doesn’t return it, we store the one you searched with.
 * dns33
*/
function yt_transform_channel($apiJson, $channelId)
{
    return [
        "channel_id"        => $channelId,
        "title"             => $apiJson["title"] ?? "",
        "vanity_url"        => $apiJson["vanityChannelUrl"] ?? null,
        "verified"          => !empty($apiJson["verified"]) ? 1 : 0,
        "subscribers_text"  => $apiJson["subscriberCountText"] ?? null,
        "avatar_url"        => yt_pick_best_thumbnail($apiJson["avatar"]["thumbnails"] ?? []),
        "is_api"            => 1,
    ];
}

/**
 * Extract video rows for IT202_M2_YT_Videos from API decoded JSON.
 * Only grabs items that actually contain ["video"].
 * dns33
 */
function yt_transform_videos($apiJson, $channelId)
{
    $rows = [];
    $contents = $apiJson["contents"] ?? [];
    if (!is_array($contents)) {
        return $rows;
    }

    foreach ($contents as $item) {
        if (!isset($item["video"]) || !is_array($item["video"])) {
            continue;
        }
        $v = $item["video"];

        // MUST have a videoId to be useful / unique
        $videoId = $v["videoId"] ?? null;
        if (!$videoId) {
            continue;
        }

        $rows[] = [
            "video_id"        => $videoId,
            "channel_id"      => $channelId,
            "title"           => $v["title"] ?? "",
            "channel_name"    => $v["channelName"] ?? "",
            "length_text"     => $v["lengthText"] ?? null,
            "published_text"  => $v["publishedTimeText"] ?? null,
            "views_text"      => $v["viewCountText"] ?? null,
            "thumbnail_url"   => yt_pick_best_thumbnail($v["thumbnails"] ?? []),
            "is_api"          => 1,
        ];
    }

    return $rows;
}

/**
 * API CALL (list endpoint):
 * Channel Search: returns channel info + list of videos in "contents"
 *
 * Params:
 * - $channelId: channel ID string
 * - $query: search query string
 * - $next: optional pagination token
 *
 * Returns:
 *  [
 *    "raw" => decoded API JSON,
 *    "channel" => DB-ready channel row,
 *    "videos"  => array of DB-ready video rows
 *  ]
 */
function yt_channel_search($channelId, $query, $next = "")
{
    $endpoint = "https://youtube-search-and-download.p.rapidapi.com/channel/search";
    $isRapidAPI = true;
    $rapidAPIHost = "youtube-search-and-download.p.rapidapi.com";

    $data = [
        "id" => $channelId,
        "query" => $query,
    ];
    if (!empty($next)) {
        $data["next"] = $next;
    }

    $result = get($endpoint, "RAPIDAPI_KEY", $data, $isRapidAPI, $rapidAPIHost);

    error_log("YT API Response: " . var_export($result, true));

    if (se($result, "status", 400, false) != 200 || !isset($result["response"])) {
        return ["raw" => [], "channel" => [], "videos" => []];
    }

    $json = json_decode($result["response"], true);
    if (!is_array($json)) {
        return ["raw" => [], "channel" => [], "videos" => []];
    }

    return [
        "raw" => $json,
        "channel" => yt_transform_channel($json, $channelId),
        "videos" => yt_transform_videos($json, $channelId),
    ];
}