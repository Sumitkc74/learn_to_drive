@php($practiceLanguage=old('language',session('practice_language',app()->getLocale())))
@php($practiceLanguage=in_array($practiceLanguage,['en','ne'])?$practiceLanguage:app()->getLocale())
<label>{{ __('Question language') }}<select name="language">
    <option value="en" @selected($practiceLanguage==='en')>{{ __('English') }}</option>
    <option value="ne" @selected($practiceLanguage==='ne')>{{ __('Nepali') }}</option>
</select></label>
