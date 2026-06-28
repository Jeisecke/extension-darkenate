<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\Darkenate;

use Illuminate\View\View;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;

use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;

class darkenateExtensionController extends Controller
{
    public function __construct(
        private BlueprintExtensionLibrary $blueprint,
    ) {}

    private function getFolders(): array
    {
        $data = DB::table('settings')->where('key', 'darkenate::folders')->value('value');
        return $data ? json_decode($data, true) : [];
    }

    private function saveFolders(array $folders): void
    {
        usort($folders, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));
        DB::table('settings')->updateOrInsert(
            ['key' => 'darkenate::folders'],
            ['value' => json_encode(array_values($folders))]
        );
    }

    public function index(): View
    {
        return view('admin.extensions.darkenate.index', [
            'blueprint' => $this->blueprint,
            'folders' => $this->getFolders(),
            'version' => 'v2.1.0',
            'servers' => Server::select('id', 'name', 'uuid')->orderBy('name')->get()->keyBy('id'),
        ]);
    }

    public function post(Request $request)
    {
        $folders = $this->getFolders();
        $folders[] = [
            'id' => (string) Uuid::uuid4(),
            'name' => $request->input('name', 'New Folder'),
            'sort_order' => count($folders),
            'servers' => [],
        ];
        $this->saveFolders($folders);
        return redirect('/admin/extensions/darkenate');
    }

    public function update(Request $request)
    {
        $folders = $this->getFolders();

        if ($request->input('_delete')) {
            $deleteId = $request->input('_delete');
            $folders = array_values(array_filter($folders, fn($f) => $f['id'] !== $deleteId));
        }

        $input = $request->input('folders');
        if ($input && is_array($input)) {
            foreach ($input as $item) {
                $id = $item['id'] ?? null;
                if (!$id) continue;
                foreach ($folders as &$folder) {
                    if ($folder['id'] === $id) {
                        if (isset($item['name'])) $folder['name'] = $item['name'];
                        if (isset($item['sort_order'])) $folder['sort_order'] = (int) $item['sort_order'];
                        if (isset($item['servers'])) $folder['servers'] = array_map('intval', $item['servers']);
                        break;
                    }
                }
            }
        }

        $this->saveFolders($folders);
        return redirect('/admin/extensions/darkenate');
    }
}
