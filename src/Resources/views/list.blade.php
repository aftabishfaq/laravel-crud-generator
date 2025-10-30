<h1>{{ __('List :table', ['table' => $table]) }}</h1>

<div style="margin-bottom: 1rem;">
    <form method="GET" action="{{ route('crud.index', ['table' => $table]) }}" style="display:flex; gap:.5rem; align-items:center;">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Search') }}" />
        <select name="sort">
            @foreach($columns as $c)
                <option value="{{ $c }}" @selected($sort === $c)>{{ $c }}</option>
            @endforeach
        </select>
        <select name="dir">
            <option value="asc" @selected($dir==='asc')>asc</option>
            <option value="desc" @selected($dir==='desc')>desc</option>
        </select>
        <button type="submit">{{ __('Apply') }}</button>
        <a href="{{ route('crud.create', ['table' => $table]) }}">{{ __('Create') }}</a>
    </form>
}</div>

<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            @foreach($columns as $c)
                <th>{{ __($c) }}</th>
            @endforeach
            <th>{{ __('Actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $row)
            <tr>
                @foreach($columns as $c)
                    <td>{{ data_get($row, $c) }}</td>
                @endforeach
                <td>
                    <a href="{{ route('crud.show', ['table' => $table, 'id' => $row->id]) }}">{{ __('View') }}</a>
                    <a href="{{ route('crud.edit', ['table' => $table, 'id' => $row->id]) }}">{{ __('Edit') }}</a>
                    <form action="{{ route('crud.destroy', ['table' => $table, 'id' => $row->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('{{ __('Delete this item?') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit">{{ __('Delete') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top: 1rem;">
    {{ $records->links() }}
</div>


