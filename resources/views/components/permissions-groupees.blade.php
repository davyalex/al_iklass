@props(['permissions', 'libelles' => [], 'idPrefix', 'checkboxClass' => ''])

<div class="row g-2">
    @foreach ($permissions as $groupe => $items)
        <div class="col-12 col-sm-6">
            <div class="card h-100 border-0 bg-white shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold small text-muted mb-2">{{ $libelles[$groupe] ?? ucfirst($groupe) }}</div>
                    <div class="row row-cols-1 row-cols-md-2 g-1">
                        @foreach ($items as $permission)
                            <div class="col">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input {{ $checkboxClass }}" name="permissions[]" value="{{ $permission->name }}" id="perm-{{ $idPrefix }}-{{ $permission->id }}">
                                    <label class="form-check-label small" for="perm-{{ $idPrefix }}-{{ $permission->id }}">{{ $permission->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
