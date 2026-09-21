<div class="ltd-table-pagination">
    <p>
        @if($items->total())
            Showing {{ $items->firstItem() }}–{{ $items->lastItem() }} of {{ $items->total() }} records
        @else
            No matching records
        @endif
    </p>
    @if($items->hasPages())
        <nav aria-label="Table pagination" class="ltd-table-pagination__buttons">
            @if($items->onFirstPage())
                <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">Previous</span>
            @else
                <a class="btn btn-sm btn-outline-secondary" href="{{ $items->previousPageUrl() }}" rel="prev">Previous</a>
            @endif
            <span>Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
            @if($items->hasMorePages())
                <a class="btn btn-sm btn-outline-secondary" href="{{ $items->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">Next</span>
            @endif
        </nav>
    @endif
</div>
