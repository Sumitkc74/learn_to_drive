<section class="ltd-panel mb-4"><h2 class="ltd-panel__title">Content Review Workload</h2>
<div class="row">@foreach(['pending'=>'Pending reviews','answers'=>'Missing answers','diagrams'=>'Diagram checks','ocr'=>'OCR reviews'] as $key=>$label)<div class="col-6 col-md-3 mb-3"><strong style="font-size:1.7rem">{{ number_format($reviewWorkload->$key) }}</strong><br>{{ $label }}</div>@endforeach</div>
@forelse($reviewBanks as $bank)<p><a href="{{ route('pdfQuestions',['resource'=>$bank->learning_content_import_id,'status'=>'Pending']) }}">{{ $bank->resource?->title ?? 'Question bank' }}</a> · {{ $bank->pending }} pending</p>@empty<p class="text-muted">No question candidates waiting for review.</p>@endforelse
@foreach($extractionIssues as $run)<p><a href="{{ route('pdfRuns.show',$run) }}">Extraction #{{ $run->id }} · {{ $run->resource?->title }} · {{ $run->status }}</a></p>@endforeach
</section>
