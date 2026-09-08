<section class="ltd-panel ltd-admin-tools">
    <div class="ltd-section-heading"><h2 class="ltd-panel__title">Admin Tools</h2><p>Review imported content, recover deleted items, and maintain the application.</p></div>
    <div class="ltd-admin-tools__grid">
        @foreach([
            [route('governmentNotices', ['status' => 'Pending']), 'fa-landmark', 'Government Review', 'Review official notice suggestions'],
            [route('questionTrash'), 'fa-trash-restore', 'Question Trash', 'Restore deleted questions'],
            [route('noticeTrash'), 'fa-recycle', 'Notice Trash', 'Restore deleted notices'],
            [route('auditLogs'), 'fa-history', 'Audit Log', 'Review administrator activity'],
            [route('appSettings'), 'fa-sliders-h', 'Application Settings', 'Configure each application area'],
        ] as [$link, $icon, $title, $description])
            <a href="{{ $link }}" class="ltd-admin-tool"><i class="fas {{ $icon }}"></i><span><strong>{{ $title }}</strong><small>{{ $description }}</small></span></a>
        @endforeach
    </div>
</section>
