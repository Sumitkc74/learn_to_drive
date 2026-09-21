<?php

return [
    'python' => env('PDF_OCR_PYTHON', 'python'),
    'modules' => env('PDF_OCR_MODULES', storage_path('app/private/ocr-python')),
    'tessdata' => env('PDF_OCR_TESSDATA', storage_path('app/private/ocr-tessdata')),
];
