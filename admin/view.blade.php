<style>
.folder-item {
  margin-bottom: 15px;
  background: var(--item-color, #1f212f);
  padding: 12px;
  border-radius: 8px;
  transition: opacity 0.15s;
}
.folder-item.dragging {
  opacity: 0.4;
}
.folder-item.drag-over {
  border: 2px dashed #4a9eff;
}
.folder-item select {
  height: 100px !important;
}
.drag-handle {
  cursor: grab;
  font-size: 18px;
  user-select: none;
}
.drag-handle:active {
  cursor: grabbing;
}
</style>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class='bx bx-folder' style='margin-right:5px;'></i>Server Folders</h3>
            </div>
            <div class="box-body">
                <p>You are currently running version <code>{{ $version }}</code>.</p>
                <p class="text-muted">Create folders to group servers in the dashboard. Drag the handle &#9776; to reorder.</p>
                <hr>

                <form method="POST" action="/admin/extensions/darkenate" id="folder-form">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_delete" id="delete-target" value="">

                    <div id="folder-list">
                        @forelse($folders as $i => $folder)
                            <div class="folder-item" data-id="{{ $folder['id'] }}" draggable="false">
                                <div class="row">
<div class="col-xs-1 text-center" style="padding-top:22px;">
    <span class="drag-handle" draggable="true">&#9776;</span>
    <input type="hidden" name="folders[{{ $i }}][id]" value="{{ $folder['id'] }}">
    <input type="hidden" name="folders[{{ $i }}][sort_order]" value="{{ $i }}" class="sort-order">
</div>
                                    <div class="col-xs-3">
                                        <label class="control-label" style="font-size:0.85rem;">Name</label>
                                        <input type="text" name="folders[{{ $i }}][name]"
                                               value="{{ e($folder['name']) }}" class="form-control" placeholder="Folder name">
                                    </div>
                                    <div class="col-xs-5">
                                        <label class="control-label" style="font-size:0.85rem;">
                                            Servers
                                            <small class="text-muted">(Ctrl+click to select multiple)</small>
                                        </label>
                                        <select name="folders[{{ $i }}][servers][]" multiple class="form-control">
                                            @foreach($servers as $server)
                                                <option value="{{ $server->id }}"
                                                    {{ in_array($server->id, $folder['servers'] ?? []) ? 'selected' : '' }}>
                                                    {{ $server->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xs-3 text-right" style="padding-top:22px;">
                                        <button type="button" class="btn btn-danger btn-sm"
                                                onclick="deleteFolder('{{ $folder['id'] }}')">
                                            <i class='bx bx-trash'></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No folders yet. Create one below.</p>
                        @endforelse
                    </div>

                    @if(!empty($folders))
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-save'></i> Save Changes
                    </button>
                    @endif
                </form>

                <hr>

                <form method="POST" action="/admin/extensions/darkenate">
                    @csrf
                    <div class="row">
                        <div class="col-xs-4">
                            <input type="text" name="name" class="form-control" placeholder="New folder name" required>
                        </div>
                        <div class="col-xs-2">
                            <button type="submit" class="btn btn-success">
                                <i class='bx bx-folder-plus'></i> Create Folder
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteFolder(id) {
    if (!confirm('Delete this folder and unassign its servers?')) return;
    document.getElementById('delete-target').value = id;
    document.getElementById('folder-form').submit();
}

(function() {
    var list = document.getElementById('folder-list');
    if (!list) return;

    var dragSrc = null;

    list.querySelectorAll('.drag-handle').forEach(function(handle) {
        handle.addEventListener('dragstart', function(e) {
            dragSrc = this.closest('.folder-item');
            dragSrc.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        handle.addEventListener('dragend', function() {
            if (dragSrc) dragSrc.classList.remove('dragging');
            list.querySelectorAll('.folder-item').forEach(function(el) {
                el.classList.remove('drag-over');
            });
        });
    });

    list.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var target = e.target.closest('.folder-item');
        if (target && target !== dragSrc) {
            list.querySelectorAll('.folder-item').forEach(function(el) {
                el.classList.remove('drag-over');
            });
            target.classList.add('drag-over');
        }
    });

    function renumber() {
        list.querySelectorAll('.folder-item').forEach(function(el, idx) {
            el.querySelectorAll('input, select').forEach(function(input) {
                var name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/folders\[\d+\]/, 'folders[' + idx + ']'));
                }
            });
            var so = el.querySelector('.sort-order');
            if (so) so.value = idx;
        });
    }

    list.addEventListener('drop', function(e) {
        e.preventDefault();
        var target = e.target.closest('.folder-item');
        if (!target || target === dragSrc || !dragSrc) return;

        if (target.nextElementSibling) {
            list.insertBefore(dragSrc, target.nextElementSibling);
        } else {
            list.appendChild(dragSrc);
        }

        list.querySelectorAll('.folder-item').forEach(function(el) {
            el.classList.remove('drag-over');
        });

        renumber();
    });
})();
</script>
