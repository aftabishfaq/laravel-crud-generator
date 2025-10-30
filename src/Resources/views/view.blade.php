<h1>{{ __('View :table', ['table' => $table]) }}</h1>

<div style="margin-bottom: 1rem;">
    <a href="{{ route('crud.index', ['table' => $table]) }}">{{ __('Back to list') }}</a>
    <a href="{{ route('crud.edit', ['table' => $table, 'id' => $record->id]) }}">{{ __('Edit') }}</a>
    <form action="{{ route('crud.destroy', ['table' => $table, 'id' => $record->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('{{ __('Delete this item?') }}');">
        @csrf
        @method('DELETE')
        <button type="submit">{{ __('Delete') }}</button>
    </form>
    </div>

<table border="1" cellpadding="6" cellspacing="0">
    <tbody>
        @foreach($columns as $c)
            @continue(in_array($c, ['id','created_at','updated_at','deleted_at']))
            <tr>
                <th>{{ __($c) }}</th>
                <td>{{ data_get($record, $c) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>


