<section class="ltd-panel mb-4">
    <h2 class="ltd-panel__title">Start here</h2>
    <div class="ltd-admin-tools__grid">
        @foreach([
            [route('learningContent', ['status' => 'Pending']), 'fa-book-reader', 'Review learning content', 'Check PDFs, translate questions, and prepare signs or vision tests.'],
            [route('governmentNotices', ['status' => 'Pending']), 'fa-landmark', 'Review government notices', 'Check official notices and import selected items as drafts.'],
            [route('support.index', ['status' => 'New']), 'fa-comments', 'Respond to users', 'Read new complaints, content reports, and requests.'],
        ] as [$link, $icon, $title, $description])
        <a href="{{ $link }}" class="ltd-admin-tool"><i class="fas {{ $icon }}" aria-hidden="true"></i><span><strong>{{ $title }}</strong><small>{{ $description }}</small></span></a>
        @endforeach
    </div>
    <p class="small text-muted mt-3 mb-0">Use Content in the sidebar to add or edit items. Open Analytics for user growth and exam results.</p>
</section>
