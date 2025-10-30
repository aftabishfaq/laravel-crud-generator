<div style="margin-bottom: .75rem;">
    <label for="{{ $name }}">{{ $label ?? $name }}</label>
    <select id="{{ $name }}" name="{{ $name }}">
        <option value="">--</option>
        @foreach(($options ?? []) as $val => $text)
            <option value="{{ $val }}" @selected((string) old($name, $value ?? '') === (string) $val)>{{ $text }}</option>
        @endforeach
    </select>
</div>


