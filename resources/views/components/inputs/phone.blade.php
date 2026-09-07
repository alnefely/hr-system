@props([
    'name',
    'id'             => null,
    'label'          => null,
    'tooltip'        => null,
    'value'          => null,
    'required'       => false,
    'placeholder'    => null,
    'col'            => null,
    'class'          => '',
    'inputClass'     => '',
    'size'           => null,
    'help'           => null,
    'initialCountry' => 'auto',
    'onlyCountries'  => ['eg','sa','ae','bh','kw','qa','om','jo','lb','ma','tn','dz','sy','iq','ye','sd'],
    'countryField'   => true,
    'blockInvalid'   => true,
    'showValid'      => true,
    'errorAfter'     => 3,   // يبدأ يوضّح الخطأ بعد كام رقم مكتوب (0 = من أول حرف)
])

@php
    $inputId    = $id ?? str_replace(['[', ']', '.'], '_', $name) . '_' . uniqid();
    $inputValue = old($name, $value);
    $sizeClass  = $size ? 'form-control-' . $size : '';
@endphp

<div @class(['col-md-' . $col => $col, $class => $class, 'mb-4'])>
    @if($label)
        <label for="{{ $inputId }}" class="form-label">
            {{ $label }}
            @if($required)<span class="text-danger">*</span>@endif
            @if($tooltip)
                <i class="fa-solid fa-circle-question text-muted ms-1 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tooltip }}"></i>
            @endif
        </label>
    @endif

    <input
        type="tel"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ $inputValue ?? '' }}"
        class="form-control {{ $sizeClass }} {{ $inputClass }} @error($name) is-invalid @enderror"
        placeholder="{{ $placeholder }}"
        autocomplete="tel"
        data-iti-phone
        data-iti-initial-country="{{ $initialCountry }}"
        data-iti-only-countries="{{ $onlyCountries ? implode(',', $onlyCountries) : '' }}"
        data-iti-country-field="{{ $countryField ? '1' : '0' }}"
        data-iti-block-invalid="{{ $blockInvalid ? '1' : '0' }}"
        data-iti-required="{{ $required ? '1' : '0' }}"
        data-iti-show-valid="{{ $showValid ? '1' : '0' }}"
        data-iti-error-after="{{ (int) $errorAfter }}"
        data-iti-custom-help="{{ $help ? '1' : '0' }}"
    />

    @if($countryField)
        <input type="hidden" name="{{ $name }}_country" id="{{ $inputId }}_country" value="">
    @endif

    <div class="invalid-feedback @error($name) d-block @enderror"
         data-iti-feedback-for="{{ $inputId }}">@error($name){{ $message }}@enderror</div>

    {{-- <div class="valid-feedback" data-iti-valid-for="{{ $inputId }}">الرقم صحيح</div> --}}

    {{-- <div class="form-text" data-iti-hint-for="{{ $inputId }}">{{ $help }}</div> --}}
</div>

@once
    @push('css')
        <link rel="stylesheet" href="{{ url('intl-tel-input/css/intlTelInput.min.css') }}">
        <style>
            /* =========================================================
               intl-tel-input × Vuexy — شكل Select2
               ملاحظة: عنصر واحد فقط هو اللي بياخد position:fixed
               وهو اللي بيتحدد بالـ JS عن طريق كلاس .js-iti-pop
               ========================================================= */

            html .iti,
            html .js-iti-pop {
                --iti-path-flags-1x: url('/intl-tel-input/img/flags.png');
                --iti-path-flags-2x: url('/intl-tel-input/img/flags@2x.png');
                --iti-path-globe-1x: url('/intl-tel-input/img/globe.png');
                --iti-path-globe-2x: url('/intl-tel-input/img/globe@2x.png');
            }
            html .iti__flag { background-image: url('/intl-tel-input/img/flags.png'); }
            @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
                html .iti__flag { background-image: url('/intl-tel-input/img/flags@2x.png'); }
            }

            /* ---------- (أ) الكونترول: LTR إجباري ---------- */
            html .iti,
            html .iti *,
            html[dir="rtl"] .iti,
            html[dir="rtl"] .iti * {
                direction: ltr !important;
                text-align: left !important;
            }

            html .iti {
                display: block !important;
                width: 100% !important;
                position: relative !important;
            }

            html .iti > input[type="tel"],
            html .iti > .form-control {
                width: 100% !important;
                direction: ltr !important;
                text-align: left !important;
                unicode-bidi: isolate;
            }

            html .iti__country-container,
            html .iti__flag-container {
                left: 0 !important;
                right: auto !important;
            }

            /* ---------- (ب) الحاوية المنبثقة الوحيدة ---------- */
            html .js-iti-pop {
                position: fixed !important;
                top: 0;
                left: 0;
                z-index: 1092 !important;
                direction: ltr !important;
                display: block;   /* بدون important عشان .iti__hide تقدر تخفيها */

                background-color: var(--bs-paper-bg, var(--bs-body-bg)) !important;
                color: var(--bs-body-color) !important;
                border: 1px solid var(--bs-border-color) !important;
                border-radius: var(--bs-border-radius, .375rem) !important;
                box-shadow: 0 .25rem .875rem rgba(var(--bs-body-color-rgb, 34, 48, 62), .18) !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: hidden !important;
                max-width: none !important;
                max-height: none !important;
            }

            /* الإخفاء: المكتبة بتضيف .iti__hide عند القفل — لازم تكسب */
            html .js-iti-pop.iti__hide,
            html .iti__dropdown-content.iti__hide,
            html .iti__country-list.iti__hide {
                display: none !important;
            }

            /* القائمة جوه الحاوية تفضل عادية — دي كانت سبب الانفصال */
            html .js-iti-pop .iti__country-list {
                position: static !important;
                width: auto !important;
                max-width: none !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
                list-style: none !important;
                max-height: 220px !important;
                overflow-y: auto !important;
                padding: .25rem !important;
                margin: 0 !important;
            }

            /* ---------- (ج) صندوق البحث ---------- */
            html .js-iti-pop .iti__search-input {
                display: block !important;
                width: calc(100% - 1rem) !important;
                margin: .5rem !important;
                padding: .375rem .625rem !important;
                border: 1px solid var(--bs-border-color) !important;
                border-radius: var(--bs-border-radius-sm, .25rem) !important;
                background-color: transparent !important;
                color: var(--bs-body-color) !important;
                font-size: .9375rem !important;
                line-height: 1.5 !important;
            }
            html .js-iti-pop .iti__search-input:focus {
                outline: 0 !important;
                border-color: var(--bs-primary) !important;
                box-shadow: 0 0 0 .15rem rgba(var(--bs-primary-rgb), .16) !important;
            }

            /* ---------- (د) صفوف الدول ---------- */
            html .iti__country {
                display: flex !important;
                align-items: center !important;
                gap: .5rem !important;
                padding: .4375rem .625rem !important;
                border-radius: var(--bs-border-radius-sm, .25rem) !important;
                font-size: .9375rem !important;
                line-height: 1.5 !important;
                cursor: pointer !important;
            }
            /* ترتيب ثابت: علم ← كود ← الاسم */
            html .iti__flag-box,
            html .iti__flag        { order: 1 !important; }
            html .iti__dial-code   { order: 2 !important; }
            html .iti__country-name{
                order: 3 !important;
                flex: 1 1 auto !important;
                unicode-bidi: plaintext !important;   /* الاسم العربي يترتب صح */
            }

            html .iti__country:hover,
            html .iti__country.iti__highlight {
                background-color: rgba(var(--bs-primary-rgb), .12) !important;
                color: var(--bs-primary) !important;
            }
            html .iti__country.iti__active,
            html .iti__country[aria-selected="true"] {
                background-color: var(--bs-primary) !important;
                color: #fff !important;
            }
            html .iti__country.iti__active .iti__dial-code,
            html .iti__country[aria-selected="true"] .iti__dial-code {
                color: rgba(255, 255, 255, .75) !important;
            }
            html .iti__dial-code {
                color: var(--bs-secondary-color, rgba(var(--bs-body-color-rgb), .55)) !important;
                font-size: .875rem !important;
                min-width: 3.25rem !important;
            }
            html .iti__divider {
                margin: .25rem 0 !important;
                border-bottom: 1px solid var(--bs-border-color) !important;
            }

            html .js-iti-pop .iti__country-list::-webkit-scrollbar { width: 6px; }
            html .js-iti-pop .iti__country-list::-webkit-scrollbar-thumb {
                background-color: rgba(var(--bs-body-color-rgb), .25);
                border-radius: 3px;
            }

            /* ---------- (هـ) زرار الدولة ---------- */
            html .iti__selected-country,
            html .iti__selected-flag {
                position: relative !important;
                border-radius: var(--bs-border-radius, .375rem) 0 0 var(--bs-border-radius, .375rem) !important;
                transition: background-color .15s ease-in-out;
            }
            html .iti__selected-country:hover,
            html .iti__selected-flag:hover {
                background-color: rgba(var(--bs-primary-rgb), .08) !important;
            }
            html .iti--separate-dial-code .iti__selected-dial-code {
                color: var(--bs-body-color) !important;
                font-size: var(--bs-body-font-size) !important;
                padding-left: .25rem !important;
                min-width: auto !important;
            }
            html .iti--separate-dial-code .iti__selected-country::after {
                content: '';
                position: absolute;
                top: 22%;
                bottom: 22%;
                right: 0;
                width: 1px;
                background-color: var(--bs-border-color);
            }

            /* ---------- (و) أيقونات التحقق يمين دائمًا ---------- */
            html .iti > .form-control.is-valid,
            html .iti > .form-control.is-invalid,
            html[dir="rtl"] .iti > .form-control.is-valid,
            html[dir="rtl"] .iti > .form-control.is-invalid {
                background-position: right calc(.375em + .1875rem) center !important;
            }

            /* ---------- (ز) الرسائل تحت الحقل ---------- */
            [data-iti-feedback-for],
            [data-iti-valid-for],
            [data-iti-hint-for] {
                direction: inherit;
                text-align: inherit;
            }
            [data-iti-hint-for]:empty { display: none; }
            [data-iti-hint-for] .iti-sample {
                display: inline-block;
                direction: ltr;
                unicode-bidi: isolate;
                font-family: var(--bs-font-monospace, monospace);
            }
        </style>
    @endpush

    @push('js')
        <script src="{{ url('intl-tel-input/js/intlTelInput.min.js') }}"></script>
        <script src="{{ url('intl-tel-input/js/i18n/ar/countries.js') }}"></script>
        <script>
            window.VuexyPhoneInput = (function () {
                const GEO_CACHE_KEY = 'iti_geo_country';
                const GAP = 4;

                const TYPE_DELAY = 120;   // تأخير بسيط عشان الرسالة ما ترفرفش مع كل حرف

                // أكواد libphonenumber: 0 ممكن، 1 كود دولة، 2 قصير، 3 طويل، 4 محلي فقط، 5 طول غير صحيح
                const ERRORS = [
                    'رقم الهاتف غير صحيح',
                    'كود الدولة غير صحيح',
                    'الرقم قصير جدًا',
                    'الرقم طويل جدًا',
                    'الرقم محلي فقط، اكتبه كامل',
                    'طول الرقم غير صحيح',
                ];

                const countDigits = (str) => (String(str || '').match(/\d/g) || []).length;

                function arabicDigits(n) {
                    if (n === 1) return 'رقم واحد';
                    if (n === 2) return 'رقمين';
                    if (n <= 10) return n + ' أرقام';
                    return n + ' رقم';
                }

                /* رسالة موجّهة أثناء الكتابة بدل "غير صحيح" المبهمة */
                function liveMessage(iti, input) {
                    const code = iti.getValidationError();

                    if (code === 2 || code === 5) {
                        const typed    = countDigits(input.value);
                        const expected = countDigits(input.getAttribute('placeholder'));

                        if (expected && typed < expected) {
                            return 'ناقص ' + arabicDigits(expected - typed);
                        }
                    }
                    return ERRORS[code] || ERRORS[0];
                }

                const I18N = Object.assign(
                    {},
                    (typeof countryTranslations !== 'undefined') ? countryTranslations : {},
                    {
                        searchPlaceholder: 'ابحث عن دولة',
                        selectedCountryAriaLabel: 'الدولة المختارة',
                        countryListAriaLabel: 'قائمة الدول',
                        noCountrySelected: 'لم يتم اختيار دولة',
                        zeroSearchResults: 'لا توجد نتائج',
                    }
                );

                function geoLookup(callback) {
                    let cached = null;
                    try { cached = sessionStorage.getItem(GEO_CACHE_KEY); } catch (e) {}
                    if (cached) return callback(cached);

                    fetch('https://ipapi.co/json')
                        .then(r => r.json())
                        .then(d => {
                            const code = (d.country_code || 'eg').toLowerCase();
                            try { sessionStorage.setItem(GEO_CACHE_KEY, code); } catch (e) {}
                            callback(code);
                        })
                        .catch(() => callback('eg'));
                }

                function numberIsValid(iti) {
                    return typeof iti.isValidNumberPrecise === 'function'
                        ? iti.isValidNumberPrecise()
                        : iti.isValidNumber();
                }

                const el = (attr, id) => document.querySelector('[' + attr + '="' + id + '"]');

                /* =========================================================
                   (1) الحشوة اليسرى للحقل
                   المكتبة بتحطها على الجهة الغلط لما الصفحة RTL،
                   فبنحسبها بأنفسنا من العرض الحقيقي لزرار الدولة
                   ========================================================= */
                function fixPadding(input, wrapper) {
                    const country = wrapper.querySelector('.iti__country-container')
                                 || wrapper.querySelector('.iti__flag-container');
                    if (!country) return;

                    const width = country.offsetWidth;
                    if (!width) return;

                    input.style.setProperty('padding-left', (width + 8) + 'px', 'important');
                    input.style.removeProperty('padding-right');
                }

                /* =========================================================
                   (2) تحديد الحاوية المنبثقة الصحيحة
                   v20+ : .iti__dropdown-content (وجوّاها القائمة)
                   v19- : .iti__country-list هي الحاوية نفسها
                   ========================================================= */
                function popupOf(wrapper) {
                    return wrapper.querySelector('.iti__dropdown-content')
                        || wrapper.querySelector('.iti__country-list');
                }

                function placePopup(wrapper) {
                    const box = popupOf(wrapper);
                    if (!box) return;

                    box.classList.add('js-iti-pop');

                    const rect = wrapper.getBoundingClientRect();

                    box.style.setProperty('width', rect.width + 'px', 'important');
                    box.style.setProperty('left', Math.round(rect.left) + 'px', 'important');
                    box.style.setProperty('right', 'auto', 'important');

                    const height     = box.offsetHeight;
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const openUp     = spaceBelow < height + GAP && rect.top > spaceBelow;

                    box.style.setProperty(
                        'top',
                        Math.round(openUp ? rect.top - height - GAP : rect.bottom + GAP) + 'px',
                        'important'
                    );
                }

                function bindPopup(input, wrapper) {
                    let onMove = null;

                    input.addEventListener('open:countrydropdown', function () {
                        placePopup(wrapper);
                        requestAnimationFrame(() => placePopup(wrapper));  // بعد ما الارتفاع يستقر

                        onMove = () => placePopup(wrapper);
                        window.addEventListener('scroll', onMove, true);
                        window.addEventListener('resize', onMove);
                    });

                    input.addEventListener('close:countrydropdown', function () {
                        if (!onMove) return;
                        window.removeEventListener('scroll', onMove, true);
                        window.removeEventListener('resize', onMove);
                        onMove = null;
                    });
                }

                function paint(input, state, message) {
                    const bad  = el('data-iti-feedback-for', input.id);
                    const good = el('data-iti-valid-for', input.id);

                    input.classList.remove('is-valid', 'is-invalid');
                    if (bad)  bad.classList.remove('d-block');
                    if (good) good.classList.remove('d-block');

                    if (state === 'invalid') {
                        input.classList.add('is-invalid');
                        if (bad) { bad.textContent = message; bad.classList.add('d-block'); }
                    } else if (state === 'valid' && input.dataset.itiShowValid === '1') {
                        input.classList.add('is-valid');
                        if (good) good.classList.add('d-block');
                    }
                }

                function initOne(input) {
                    if (input.dataset.itiReady === '1') return;
                    if (typeof window.intlTelInput !== 'function') return;
                    input.dataset.itiReady = '1';

                    const only = (input.dataset.itiOnlyCountries || '')
                        .split(',').map(c => c.trim()).filter(Boolean);

                    const options = {
                        loadUtils: () => import('/intl-tel-input/js/utils.js'),
                        strictMode: true,
                        separateDialCode: true,
                        countrySearch: true,
                        useFullscreenPopup: false,
                        autoPlaceholder: 'aggressive',
                        placeholderNumberType: 'MOBILE',
                        i18n: I18N,
                    };

                    if (only.length) options.onlyCountries = only;

                    // القيمة الجاية من قاعدة البيانات، مثال: +201021464469
                    const initialValue = input.value.trim();
                    const hasIntlValue = /^\+\d/.test(initialValue);

                    if (hasIntlValue) {
                        // الرقم نفسه بيحدد الدولة — منستخدمش auto ولا الافتراضي
                        options.initialCountry = '';
                    } else if (input.dataset.itiInitialCountry === 'auto') {
                        options.initialCountry = 'auto';
                        options.geoIpLookup = geoLookup;
                    } else {
                        options.initialCountry = input.dataset.itiInitialCountry || 'eg';
                    }

                    const iti = window.intlTelInput(input, options);
                    input._iti = iti;

                    const wrapper = input.closest('.iti');
                    if (wrapper) bindPopup(input, wrapper);

                    const countryField = input.dataset.itiCountryField === '1'
                        ? document.getElementById(input.id + '_country')
                        : null;

                    const hint = el('data-iti-hint-for', input.id);

                    function refreshMeta() {
                        if (wrapper) fixPadding(input, wrapper);

                        const data = iti.getSelectedCountryData();

                        if (countryField) {
                            countryField.value = data && data.iso2 ? data.iso2.toUpperCase() : '';
                        }
                        if (hint && input.dataset.itiCustomHelp !== '1' && data && data.dialCode) {
                            const sample = input.getAttribute('placeholder');
                            hint.innerHTML = sample
                                ? 'الصيغة المتوقعة: <span class="iti-sample">+' + data.dialCode + ' ' + sample + '</span>'
                                : '';
                        }
                    }

                    const errorAfter = parseInt(input.dataset.itiErrorAfter || '3', 10);

                    /**
                     * mode = 'live'  → أثناء الكتابة، بيسكت لحد ما يتكتب عدد كافي
                     * mode = 'final' → عند الخروج أو الإرسال، بيتكلم دايمًا
                     */
                    function validate(mode) {
                        const val = input.value.trim();

                        if (!val) {
                            paint(input, 'none');
                            return input.dataset.itiRequired !== '1';
                        }

                        if (numberIsValid(iti)) {
                            paint(input, 'valid');     // الأخضر بيظهر فورًا
                            return true;
                        }

                        // لسه بيكتب وعدد الأرقام أقل من العتبة → ما نزعقش
                        if (mode === 'live' && countDigits(val) < errorAfter) {
                            paint(input, 'none');
                            return false;
                        }

                        paint(input, 'invalid', liveMessage(iti, input));
                        return false;
                    }

                    let typeTimer = null;

                    input.addEventListener('countrychange', function () {
                        refreshMeta();
                        validate('live');
                    });

                    input.addEventListener('input', function () {
                        clearTimeout(typeTimer);
                        typeTimer = setTimeout(() => validate('live'), TYPE_DELAY);
                    });

                    input.addEventListener('blur', function () {
                        clearTimeout(typeTimer);
                        validate('final');
                    });
                    window.addEventListener('resize', function () {
                        if (wrapper) fixPadding(input, wrapper);
                    });

                    refreshMeta();

                    /* =========================================================
                       utils.js بتتحمّل بشكل غير متزامن.
                       أي تحقق قبل ما تخلّص = نتيجة غلط.
                       iti.promise بتتنفّذ بعد ما كل حاجة تجهز.
                       ========================================================= */
                    (iti.promise || Promise.resolve())
                        .then(function () {
                            // setNumber بتفصل كود الدولة عن الرقم وتظبط العلم
                            if (hasIntlValue) iti.setNumber(initialValue);

                            refreshMeta();
                            if (input.value.trim() !== '') validate('final');
                        })
                        .catch(function () {
                            refreshMeta();
                        });

                    const form = input.closest('form');
                    if (form && !form.dataset.itiBound) {
                        form.dataset.itiBound = '1';
                        form.addEventListener('submit', function (e) {
                            let ok = true;

                            form.querySelectorAll('[data-iti-phone][data-iti-ready="1"]').forEach(function (field) {
                                const instance = field._iti;
                                if (!instance) return;

                                if (field.value.trim() === '') {
                                    if (field.dataset.itiRequired === '1') ok = false;
                                    return;
                                }
                                if (numberIsValid(instance)) {
                                    field.value = instance.getNumber();
                                } else if (field.dataset.itiBlockInvalid === '1') {
                                    paint(field, 'invalid', liveMessage(instance, field));
                                    ok = false;
                                }
                            });

                            if (!ok) {
                                e.preventDefault();
                                e.stopPropagation();
                                const firstBad = form.querySelector('[data-iti-phone].is-invalid');
                                if (firstBad) firstBad.focus();
                            }
                        });
                    }

                    return iti;
                }

                function initAll(root) {
                    (root || document)
                        .querySelectorAll('[data-iti-phone]:not([data-iti-ready="1"])')
                        .forEach(initOne);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => initAll());
                } else {
                    initAll();
                }

                // مراقبة مخففة: بتتجاهل تغييرات المكتبة نفسها وبتشتغل بتأجيل
                let scanTimer = null;
                new MutationObserver(function (records) {
                    const relevant = records.some(r =>
                        !(r.target.closest && r.target.closest('.iti, .js-iti-pop'))
                    );
                    if (!relevant) return;

                    clearTimeout(scanTimer);
                    scanTimer = setTimeout(() => initAll(), 150);
                }).observe(document.documentElement, { childList: true, subtree: true });

                document.addEventListener('livewire:navigated', () => initAll());

                return { initAll, initOne, isValid: numberIsValid };
            })();
        </script>
    @endpush
@endonce
