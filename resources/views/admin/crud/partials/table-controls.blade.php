<form class="ltd-table-controls" method="GET" action="{{ url()->current() }}">
    <div class="ltd-table-controls__search">
        <label class="sr-only" for="table-search-{{ $items->getPageName() }}">Search</label>
        <i class="fas fa-search" aria-hidden="true"></i>
        <input id="table-search-{{ $items->getPageName() }}" type="search" name="search"
            value="{{ request('search') }}" placeholder="Search records" class="form-control">
    </div>

    @foreach(($filters ?? []) as $name => $filter)
        <label class="sr-only" for="filter-{{ $name }}">{{ $filter['label'] }}</label>
        <select id="filter-{{ $name }}" name="{{ $name }}" class="form-control">
            <option value="">All {{ strtolower($filter['label']) }}</option>
            @foreach($filter['options'] as $value => $label)
                <option value="{{ $value }}" {{ request($name) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    @endforeach

    <label class="sr-only" for="table-sort">Sort by</label>
    <select id="table-sort" name="sort" class="form-control">
        @foreach($sortOptions as $value => $label)
            <option value="{{ $value }}" {{ request('sort', 'created_at') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <label class="sr-only" for="table-direction">Sort direction</label>
    <select id="table-direction" name="direction" class="form-control">
        <option value="desc" {{ request('direction', 'desc') === 'desc' ? 'selected' : '' }}>Descending</option>
        <option value="asc" {{ request('direction') === 'asc' ? 'selected' : '' }}>Ascending</option>
    </select>

    <label class="sr-only" for="table-page-size">Rows per page</label>
    <select id="table-page-size" name="per_page" class="form-control">
        @foreach([10, 25, 50] as $size)
            <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }} rows</option>
        @endforeach
    </select>

    <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Apply</button>
    @if(request()->hasAny(['search', 'sort', 'direction', 'per_page', ...array_keys($filters ?? [])]))
        <a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a>
    @endif
</form>
