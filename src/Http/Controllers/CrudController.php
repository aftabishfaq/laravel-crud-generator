<?php

namespace Aftab\LaravelCrud\Http\Controllers;

use Aftab\LaravelCrud\Helpers\CrudHelper;
use Aftab\LaravelCrud\Models\DynamicModel;
use Aftab\LaravelCrud\Services\OptionsResolver;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;

class CrudController extends BaseController
{
    public function index(Request $request, string $table)
    {
        $this->guardTable($table);
        $columns = CrudHelper::tableColumns($table);
        $types = CrudHelper::columnTypes($table);

        [$sort, $dir] = CrudHelper::sanitizeSort($request->query('sort'), $request->query('dir'), $columns);
        $query = (new DynamicModel())->setTable($table)::query();
        $query->orderBy($sort, $dir);

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($columns, $q) {
                foreach ($columns as $c) {
                    $sub->orWhere($c, 'like', '%' . $q . '%');
                }
            });
        }

        $perPage = (int) ($request->query('per_page', 15));
        $records = $query->paginate($perPage)->appends($request->query());

        return view('crud::list', compact('table', 'columns', 'records', 'sort', 'dir', 'types'));
    }

    public function create(string $table)
    {
        $this->guardTable($table);
        $columns = CrudHelper::tableColumns($table);
        $options = $this->resolveFieldOptions($table, $columns);
        $types = CrudHelper::columnTypes($table);
        return view('crud::form', [
            'table' => $table,
            'columns' => $columns,
            'types' => $types,
            'options' => $options,
            'record' => null,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request, string $table)
    {
        $this->guardTable($table);
        $rules = CrudHelper::inferredValidation($table, false);
        $data = $this->validate($request, $rules);
        $data = CrudHelper::processUploads($request, $table, $data);

        $model = new DynamicModel();
        $model->setTableName($table);
        $fillable = CrudHelper::fillableColumns($table);
        $safeData = array_intersect_key($data, array_flip($fillable));
        $model->fill($safeData);
        $model->save();

        return redirect()->route('crud.index', ['table' => $table])->with('status', 'Created');
    }

    public function edit(string $table, $id)
    {
        $this->guardTable($table);
        $columns = CrudHelper::tableColumns($table);
        $options = $this->resolveFieldOptions($table, $columns);
        $types = CrudHelper::columnTypes($table);
        $model = (new DynamicModel());
        $model->setTableName($table);
        $record = $model->newQuery()->findOrFail($id);
        return view('crud::form', [
            'table' => $table,
            'columns' => $columns,
            'types' => $types,
            'options' => $options,
            'record' => $record,
            'mode' => 'edit',
        ]);
    }

    public function show(string $table, $id)
    {
        $this->guardTable($table);
        $columns = CrudHelper::tableColumns($table);
        $model = (new DynamicModel());
        $model->setTableName($table);
        $record = $model->newQuery()->findOrFail($id);
        return view('crud::view', compact('table', 'columns', 'record'));
    }

    public function update(Request $request, string $table, $id)
    {
        $this->guardTable($table);
        $rules = CrudHelper::inferredValidation($table, true);
        $data = $this->validate($request, $rules);
        $data = CrudHelper::processUploads($request, $table, $data);

        $model = (new DynamicModel());
        $model->setTableName($table);
        $record = $model->newQuery()->findOrFail($id);
        $fillable = CrudHelper::fillableColumns($table);
        $safeData = array_intersect_key($data, array_flip($fillable));
        $record->fill($safeData);
        $record->save();

        return redirect()->route('crud.index', ['table' => $table])->with('status', 'Updated');
    }

    public function destroy(string $table, $id)
    {
        $this->guardTable($table);
        $model = (new DynamicModel());
        $model->setTableName($table);
        $record = $model->newQuery()->findOrFail($id);
        $record->delete();

        return redirect()->route('crud.index', ['table' => $table])->with('status', 'Deleted');
    }

    protected function guardTable(string $table): void
    {
        // Permissions (optional)
        $perm = (array) (config('crud.permissions') ?? []);
        if (!empty($perm['enabled'])) {
            $action = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'index';
            $ability = (string) (($perm['abilities'][$action] ?? 'crud.access-:table'));
            $ability = str_replace(':table', $table, $ability);
            Gate::authorize($ability);
        }
        if (!CrudHelper::isTableAllowed($table)) {
            abort(404);
        }
    }

    /**
     * @param array<int, string> $columns
     * @return array<string, array<string,string>>
     */
    protected function resolveFieldOptions(string $table, array $columns): array
    {
        $resolver = new OptionsResolver();
        $out = [];
        foreach ($columns as $c) {
            $opts = $resolver->resolve($table, $c);
            if (!empty($opts)) {
                $out[$c] = $opts;
            }
        }
        return $out;
    }
}


