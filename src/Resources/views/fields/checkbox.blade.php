<div style="margin-bottom: .75rem;">
    <label>
        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked ?? false)) />
        {{ $label ?? $name }}
    </label>
</div>


