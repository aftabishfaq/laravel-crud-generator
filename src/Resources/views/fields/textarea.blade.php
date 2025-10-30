<div style="margin-bottom: .75rem;">
    <label for="{{ $name }}">{{ $label ?? $name }}</label>
    <textarea id="{{ $name }}" name="{{ $name }}">{{ old($name, $value ?? '') }}</textarea>
</div>


