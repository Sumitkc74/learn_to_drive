"""Copy the shared learner Nepali dictionary into Flutter's bundled assets."""
import json
from pathlib import Path

root = Path(__file__).resolve().parents[2]
source = root / 'backend/lang/ne.json'
if not source.exists():
    source = root / 'backend/resources/lang/ne.json'
messages = json.loads(source.read_text(encoding='utf-8-sig'))
destination = root / 'mobile/assets/i18n/ne.json'
destination.parent.mkdir(parents=True, exist_ok=True)
destination.write_text(json.dumps(messages, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
print(f'Synced {len(messages)} translations.')
