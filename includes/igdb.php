<?php

require_once __DIR__ . '/db.php';

function igdb_get_token(): string {
    $token     = get_setting('igdb_token');
    $expires   = get_setting('igdb_token_expires');

    if ($token && $expires && time() < (int)$expires) {
        return $token;
    }

    $response = file_get_contents(
        'https://id.twitch.tv/oauth2/token?' . http_build_query([
            'client_id'     => IGDB_CLIENT_ID,
            'client_secret' => IGDB_CLIENT_SECRET,
            'grant_type'    => 'client_credentials',
        ]),
        false,
        stream_context_create(['http' => ['method' => 'POST']])
    );

    if ($response === false) {
        throw new RuntimeException('Failed to fetch IGDB token.');
    }

    $data = json_decode($response, true);
    if (empty($data['access_token'])) {
        throw new RuntimeException('Invalid IGDB token response.');
    }

    $token = $data['access_token'];
    // Expire 1 hour before actual expiry to be safe
    $expires = time() + $data['expires_in'] - 3600;

    set_setting('igdb_token', $token);
    set_setting('igdb_token_expires', (string)$expires);

    return $token;
}

function igdb_request(string $endpoint, string $query): array {
    $token = igdb_get_token();

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", [
                'Client-ID: ' . IGDB_CLIENT_ID,
                'Authorization: Bearer ' . $token,
                'Content-Type: text/plain',
                'Content-Length: ' . strlen($query),
            ]),
            'content' => $query,
        ]
    ]);

    $response = file_get_contents('https://api.igdb.com/v4/' . $endpoint, false, $ctx);

    if ($response === false) {
        throw new RuntimeException('IGDB API request failed.');
    }

    return json_decode($response, true) ?? [];
}

function igdb_search(string $query, int $limit = 12): array {
    $escaped = addslashes($query);
    $results = igdb_request('games', "
        fields name, cover.image_id, genres.name, platforms.name,
               first_release_date, involved_companies.company.name,
               involved_companies.developer, summary;
        search \"{$escaped}\";
        limit {$limit};
    ");

    return array_map('igdb_normalise', $results);
}

function igdb_get_game(int $igdb_id): ?array {
    $results = igdb_request('games', "
        fields name, cover.image_id, genres.name, platforms.name,
               first_release_date, involved_companies.company.name,
               involved_companies.developer, summary;
        where id = {$igdb_id};
        limit 1;
    ");

    if (empty($results)) return null;
    return igdb_normalise($results[0]);
}

function igdb_normalise(array $game): array {
    $cover_url = null;
    if (!empty($game['cover']['image_id'])) {
        $cover_url = 'https://images.igdb.com/igdb/image/upload/t_cover_big/' . $game['cover']['image_id'] . '.jpg';
    }

    $genres = [];
    if (!empty($game['genres'])) {
        $genres = array_column($game['genres'], 'name');
    }

    $platforms = [];
    if (!empty($game['platforms'])) {
        $platforms = array_column($game['platforms'], 'name');
    }

    $developer = null;
    if (!empty($game['involved_companies'])) {
        foreach ($game['involved_companies'] as $ic) {
            if (!empty($ic['developer']) && !empty($ic['company']['name'])) {
                $developer = $ic['company']['name'];
                break;
            }
        }
    }

    $release_year = null;
    if (!empty($game['first_release_date'])) {
        $release_year = (int)date('Y', $game['first_release_date']);
    }

    return [
        'igdb_id'      => $game['id'],
        'title'        => $game['name'],
        'cover_url'    => $cover_url,
        'genres'       => implode(', ', $genres),
        'platforms'    => implode(', ', $platforms),
        'release_year' => $release_year,
        'developer'    => $developer,
        'summary'      => $game['summary'] ?? null,
    ];
}
