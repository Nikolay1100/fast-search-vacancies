@if(!empty($matchedKeyword))
    🔔 <b>Найден пост с вакансией [<i>{{ $matchedKeyword }}</i>]</b>
@else
🔔 <b>Найден пост с вакансией</b>
@endif

@php
    $fields = [
        'role' => '👨‍💻 <b>Роль:</b>',
        'grade' => '🎓 <b>Грейд:</b>',
        'format' => '🏢 <b>Формат:</b>',
        'salary_min' => '💰 <b>Зарплата (от):</b>',
        'salary_max' => '💰 <b>Зарплата (до):</b>',
        'technologies' => '💻 <b>Технологии:</b>',
        'short_description' => '📝 <b>Описание:</b>',
    ];

    $output = [];
    if (!empty($extractedData) && is_array($extractedData)) {
        foreach ($extractedData as $key => $value) {
            if ($value === null || (is_array($value) && empty($value))) {
                continue;
            }

            $valStr = is_array($value) ? implode(', ', $value) : (string) $value;
            $valStr = trim(preg_replace('/\s+/', ' ', $valStr));

            if ($valStr !== '') {
                $label = $fields[$key] ?? ("🔹 <b>" . ucfirst(str_replace('_', ' ', $key)) . ":</b>");
                $output[] = $label . ' ' . $valStr;
            }
        }
    }
@endphp
@if(!empty($output))

{!! implode("\n", $output) !!}
@else

<i>Нет извлеченных данных</i>
@endif
