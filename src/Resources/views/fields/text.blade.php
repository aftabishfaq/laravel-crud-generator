<div style="margin-bottom: .75rem;">
    <label for="{{ $name }}">{{ $label ?? $name }}</label>
    <input type="{{ $type ?? 'text' }}" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $value ?? '') }}" />
</div>


