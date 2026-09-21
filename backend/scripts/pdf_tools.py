"""Local PDF page rendering / OCR. JSON goes to stdout; PDFs never leave this host."""
import argparse
import json
from pathlib import Path
import sys


def table_rows(image):
    """Locate ruled question tables without trusting PDF text/font mappings."""
    gray = image.convert("L")
    width, height = gray.size
    pixels = gray.load()
    def groups(values):
        runs = []
        for value in values:
            if not runs or value > runs[-1][-1] + 1:
                runs.append([value])
            else:
                runs[-1].append(value)
        return [run[len(run) // 2] for run in runs if len(run) <= 12]
    horizontal = groups([y for y in range(height) if sum(pixels[x, y] < 100 for x in range(0, width, 3)) > width / 3 * .55])
    if len(horizontal) < 3:
        gray.close()
        return []
    top, bottom = horizontal[0], horizontal[-1]
    vertical = groups([x for x in range(width) if sum(pixels[x, y] < 100 for y in range(top, bottom, 3)) > (bottom - top) / 3 * .7])
    gray.close()
    if len(vertical) < 3:
        return []
    left, right = max(zip(vertical, vertical[1:]), key=lambda pair: pair[1] - pair[0])
    if right - left < width * .4:
        return []
    number_left = max((x for x in vertical if x < left), default=max(0, left - width // 12))
    return [(number_left, left, right, y1, y2) for y1, y2 in zip(horizontal, horizontal[1:]) if y2 - y1 > 35]


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("mode", choices=["render", "ocr", "check", "translate-text"])
    parser.add_argument("--modules", required=True)
    parser.add_argument("--tessdata", required=True)
    parser.add_argument("--pdf")
    parser.add_argument("--page", type=int, default=1)
    parser.add_argument("--output")
    args = parser.parse_args()
    sys.path.insert(0, args.modules)
    import pypdfium2 as pdfium

    if args.mode in ("ocr", "check", "translate-text"):
        import tesserocr
        if not all((Path(args.tessdata) / (language + ".traineddata")).is_file() for language in ("nep", "eng")):
            raise ValueError("Install the Nepali and English OCR language models.")
    if args.mode == "check":
        with tesserocr.PyTessBaseAPI(path=args.tessdata, lang="nep+eng"):
            print(json.dumps({"ready": True, "engine": tesserocr.tesseract_version()}))
        return

    with pdfium.PdfDocument(args.pdf) as document:
        if not 1 <= args.page <= len(document):
            raise ValueError("The requested page is outside this PDF.")
        page = document[args.page - 1]
        if args.mode == "translate-text":
            if page.get_width() * page.get_height() * 4 > 20_000_000:
                raise ValueError('Page exceeds rendering limit.')
            textpage = page.get_textpage()
            text = textpage.get_text_range()
            textpage.close()
            # Legacy Nepali font encodings need OCR even if a text layer exists.
            nepali = sum('\u0900' <= c <= '\u097f' for c in text)
            if len(text.strip()) < 30:
                text = ''
            bitmap = page.render(scale=2)
            image = bitmap.to_pil().convert('RGB')
            try:
                with tesserocr.PyTessBaseAPI(path=args.tessdata, lang='nep+eng', psm=tesserocr.PSM.AUTO) as api:
                    api.SetImage(image)
                    ocr_text = api.GetUTF8Text()
                # Use full-page OCR to retain labels and answer columns in legacy-font PDFs.
                if sum('\u0900' <= c <= '\u097f' for c in ocr_text) > nepali + 20 or not text.strip():
                    text = ocr_text
                print(json.dumps({'page': args.page, 'pages': len(document), 'text': text}, ensure_ascii=True))
            finally:
                image.close(); bitmap.close(); page.close()
            return
        scale = 3 if args.mode == "ocr" else 1.5
        if page.get_width() * page.get_height() * scale * scale > 20_000_000:
            raise ValueError("This PDF page exceeds the rendering size limit.")
        bitmap = page.render(scale=scale)
        image = bitmap.to_pil().convert("RGB")
        try:
            if args.mode == "render":
                image.save(args.output, format="PNG")
                print(json.dumps({"pages": len(document), "page": args.page}))
            else:
                rows = []
                with tesserocr.PyTessBaseAPI(path=args.tessdata, lang="nep+eng", psm=tesserocr.PSM.SINGLE_BLOCK) as api:
                    for row_index, (number_left, left, right, top, bottom) in enumerate(table_rows(image)):
                        crop = image.crop((left + 5, top + 5, right - 5, bottom - 5))
                        api.SetImage(crop)
                        text = api.GetUTF8Text()
                        confidence = api.MeanTextConf()
                        crop.close()
                        number_crop = image.crop((number_left + 4, top + 4, left - 4, bottom - 4))
                        api.SetImage(number_crop)
                        number = api.GetUTF8Text().strip()
                        number_crop.close()
                        rows.append({"row": row_index + 1, "number": number, "text": text, "confidence": confidence})
                print(json.dumps({"page": args.page, "pages": len(document), "rows": rows}, ensure_ascii=True))
        finally:
            image.close()
            bitmap.close()
            page.close()


if __name__ == "__main__":
    try:
        main()
    except Exception as exception:
        print(json.dumps({"error": str(exception)}, ensure_ascii=True))
        sys.exit(1)
