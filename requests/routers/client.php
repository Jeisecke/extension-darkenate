<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;

Route::get('/folders', function () {
    $data = DB::table('settings')->where('key', 'darkenate::folders')->value('value');
    $folders = $data ? json_decode($data, true) : [];

    $result = [];
    foreach ($folders as $folder) {
        $servers = [];
        foreach (($folder['servers'] ?? []) as $sid) {
            $server = Server::select('id', 'uuid')->find($sid);
            if ($server) {
                $servers[] = ['id' => $server->id, 'uuid' => $server->uuid];
            }
        }
        $result[] = [
            'id' => $folder['id'],
            'name' => $folder['name'],
            'sort_order' => $folder['sort_order'] ?? 0,
            'servers' => $servers,
        ];
    }

    usort($result, fn($a, $b) => $a['sort_order'] - $b['sort_order']);

    return response()->json($result);
});
