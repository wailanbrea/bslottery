<form method="GET" action="{{ url()->current() }}" class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label" for="from">Desde</label>
                <input class="form-control" id="from" name="from" type="date" value="{{ $filters['from_date'] }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="to">Hasta</label>
                <input class="form-control" id="to" name="to" type="date" value="{{ $filters['to_date'] }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="branch_id">Sucursal</label>
                <select class="form-select" id="branch_id" name="branch_id" @disabled($filters['forced_branch_id'])>
                    <option value="">Todas</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((int) $filters['branch_id'] === $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @if ($filters['forced_branch_id'])
                    <input type="hidden" name="branch_id" value="{{ $filters['forced_branch_id'] }}">
                @endif
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="user_id">Usuario</label>
                <select class="form-select" id="user_id" name="user_id">
                    <option value="">Todos</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((int) $filters['user_id'] === $user->id)>
                            {{ $user->name }} ({{ $user->username }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="lottery_id">Lotería</label>
                <select class="form-select" id="lottery_id" name="lottery_id">
                    <option value="">Todas</option>
                    @foreach ($lotteries as $lottery)
                        <option value="{{ $lottery->id }}" @selected((int) $filters['lottery_id'] === $lottery->id)>
                            {{ $lottery->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="draw_id">Sorteo</label>
                <select class="form-select" id="draw_id" name="draw_id">
                    <option value="">Todos</option>
                    @foreach ($draws as $draw)
                        <option value="{{ $draw->id }}" @selected((int) $filters['draw_id'] === $draw->id)>
                            {{ $draw->draw_date?->format('d/m') }} {{ $draw->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="status">Estado</label>
                <input class="form-control" id="status" name="status" value="{{ $filters['status'] }}" placeholder="Ej: ACTIVE">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="per_page">Filas</label>
                <select class="form-select" id="per_page" name="per_page">
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected((int) $filters['per_page'] === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a class="btn btn-outline-secondary" href="{{ url()->current() }}">
                    <i class="bi bi-x-circle me-1"></i>Limpiar
                </a>
                @if(url()->current() !== route('admin.reports.index'))
                    <a class="btn btn-outline-success"
                       href="{{ url()->current() . '?' . http_build_query(array_merge(request()->query(), ['export' => 'excel'])) }}">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    <a class="btn btn-outline-secondary"
                       href="{{ url()->current() . '?' . http_build_query(array_merge(request()->query(), ['export' => 'csv'])) }}">
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </a>
                    <a class="btn btn-outline-danger"
                       href="{{ url()->current() . '?' . http_build_query(array_merge(request()->query(), ['export' => 'pdf'])) }}">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                @endif
            </div>
        </div>
    </div>
</form>
