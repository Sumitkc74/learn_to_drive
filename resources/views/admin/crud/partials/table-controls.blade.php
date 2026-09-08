@php
    $filterDefinitions = $filters ?? [];
    $advancedParameters = array_merge(array_keys($filterDefinitions), ['sort', 'direction', 'per_page']);
    $activeFilterCount = collect(array_keys($filterDefinitions))->filter(fn ($parameter) => request()->filled($parameter))->count();
    $advancedIsActive = $activeFilterCount > 0
        || (request()->filled('sort') && request('sort') !== 'created_at')
        || request('direction') === 'asc'
        || (int) request('per_page', 10) !== 10;
@endphp

<form class="ltd-table-controls" method="GET" action="{{ url()->current() }}">
    <div class="ltd-table-controls__primary">
        <div class="ltd-table-controls__search">
            <label for="table-search-{{ $items->getPageName() }}">Search</label>
            <div class="ltd-search-input"><i class="fas fa-search" aria-hidden="true"></i><input id="table-search-{{ $items->getPageName() }}" type="search" name="search" value="{{ request('search') }}" placeholder="Search by keyword…" class="form-control"></div>
        </div>
        <button type="submit" class="btn btn-primary ltd-table-controls__search-button"><i class="fas fa-search mr-1"></i>Search</button>
        @if(request()->hasAny(array_merge(['search'], $advancedParameters)))
            <a href="{{ url()->current() }}" class="btn btn-outline-secondary"><i class="fas fa-times mr-1"></i>Clear</a>
        @endif
    </div>

    <details class="ltd-filter-panel" {{ $advancedIsActive ? 'open' : '' }}>
        <summary><span><i class="fas fa-sliders-h" aria-hidden="true"></i> Filters &amp; sorting</span><span class="ltd-filter-panel__summary-meta">@if($activeFilterCount)<strong>{{ $activeFilterCount }} active</strong>@else Optional @endif<i class="fas fa-chevron-down" aria-hidden="true"></i></span></summary>
        <div class="ltd-filter-panel__body">
            <div class="ltd-filter-grid">
                @foreach($filterDefinitions as $name => $filter)
                    <div class="ltd-filter-field"><label for="filter-{{ $name }}">{{ $filter['label'] }}</label><select id="filter-{{ $name }}" name="{{ $name }}" class="form-control"><option value="">All</option>@foreach($filter['options'] as $value => $label)<option value="{{ $value }}" {{ request($name) === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                @endforeach
                <div class="ltd-filter-field"><label for="table-sort">Sort by</label><select id="table-sort" name="sort" class="form-control">@foreach($sortOptions as $value => $label)<option value="{{ $value }}" {{ request('sort', 'created_at') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="ltd-filter-field"><label for="table-direction">Direction</label><select id="table-direction" name="direction" class="form-control"><option value="desc" {{ request('direction', 'desc') === 'desc' ? 'selected' : '' }}>Newest / highest first</option><option value="asc" {{ request('direction') === 'asc' ? 'selected' : '' }}>Oldest / lowest first</option></select></div>
                <div class="ltd-filter-field"><label for="table-page-size">Rows per page</label><select id="table-page-size" name="per_page" class="form-control">@foreach([10, 25, 50] as $size)<option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }} rows</option>@endforeach</select></div>
            </div>
            <div class="ltd-filter-panel__actions"><button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i>Apply filters</button><a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset all</a></div>
        </div>
    </details>
</form>
