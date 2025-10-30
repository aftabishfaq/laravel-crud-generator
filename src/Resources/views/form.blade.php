@php
    $title = ($mode ?? 'create') === 'edit' ? __('Edit :table', ['table' => $table]) : __('Create :table', ['table' => $table]);
    $action = ($mode ?? 'create') === 'edit'
        ? route('crud.update', ['table' => $table, 'id' => $record->id])
        : route('crud.store', ['table' => $table]);
    $method = ($mode ?? 'create') === 'edit' ? 'PUT' : 'POST';
    $types = $types ?? [];
@endphp

<h1>{{ $title }}</h1>

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif

    @foreach($columns as $col)
        @continue(in_array($col, ['id','created_at','updated_at','deleted_at']))
        @php
            $label = __($col);
            $value = old($col, isset($record) ? $record->{$col} : null);
            $type = $types[$col] ?? 'string';
            $fieldOptions = ($options[$col] ?? [])
        @endphp

        @if(!empty($fieldOptions))
            @include('crud::fields.select', ['name' => $col, 'label' => $label, 'value' => $value, 'options' => $fieldOptions])
        @elseif($type === 'boolean')
            @include('crud::fields.checkbox', ['name' => $col, 'label' => $label, 'checked' => (bool) $value])
        @elseif($type === 'text')
            @include('crud::fields.textarea', ['name' => $col, 'label' => $label, 'value' => $value])
        @elseif(in_array($type, ['date','datetime']))
            @include('crud::fields.date', ['name' => $col, 'label' => $label, 'value' => $value, 'type' => $type === 'datetime' ? 'datetime-local' : 'date'])
        @else
            @include('crud::fields.text', ['name' => $col, 'label' => $label, 'value' => $value, 'type' => 'text'])
        @endif

        @error($col)
            <div class="text-red-600">{{ $message }}</div>
        @enderror
    @endforeach

    <div style="margin-top: 1rem;">
        <button type="submit">{{ $method === 'PUT' ? __('Update') : __('Create') }}</button>
        <a href="{{ route('crud.index', ['table' => $table]) }}">{{ __('Cancel') }}</a>
    </div>
</form>


