<?php

namespace Upsoftware\Svarium\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Upsoftware\Svarium\JsonResource\Field;
use Upsoftware\Svarium\Traits\HasHash;
use Illuminate\Support\Carbon;

/**
 * @mixin Builder
 */
class ApiResponseBuilder
{
    protected Builder $query;
    /** @var Field[] */
    protected array $fields;
    protected int $perPage = 15;
    protected string $sortBy = '';
    protected string $defaultSortBy = '';
    protected bool $descending = false;
    protected array $exclude = ['hash'];
    protected array $columns = [];
    protected array $filters = [];
    protected array $visible = [];

    public function __construct(Builder $query, array $fields)
    {
        $this->visible = collect($fields)
            ->filter(fn($column) => $column->visible)
            ->values()
            ->all();
        $this->query = $query;
        $this->fields = $fields;
        $request = request();

        if ($request->has('filters') && is_array($request->filters)) {
            $this->filters = $request->filters;
        }

        if ($request->has('sort')) {
            $sort = $request->sort;
            if (str_starts_with($request->sortBy, '-')) {
                $sortBy = substr($request->sortBy, 1);
                $this->descending(true);
            } else {
                $sortBy = $request->sortBy;
                $this->descending(false);
            }
            $this->sortBy($sortBy);
        }
    }

    public function __call($name, $arguments)
    {
        $this->query->$name(...$arguments);
        return $this;
    }

    public function sortBy(string $column): self
    {
        $this->sortBy = $column;
        return $this;
    }

    public function filters(array $filters): self {
        $this->filters = $filters;
        return $this;
    }

    public function descending(bool $isDescending): self
    {
        $this->descending = $isDescending;
        return $this;
    }

    public function perPage(int $perPage): self
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function defaultSort(string $sort): self {
        $this->defaultSortBy = $sort;
        return $this;
    }

    public function json(): JsonResponse
    {
        $model = $this->query->getModel();
        $tableName = $model->getTable();
        $existingColumns = Schema::getColumnListing($tableName);

        $traits = class_uses_recursive($model);
        $hasHashTrait = in_array(HasHash::class, $traits);

        $dbColumns = [];
        $isIdRequested = false;

        foreach ($this->fields as $field) {
            if ($field->key === 'id') {
                $isIdRequested = true;
            }

            if (!$field->isAccessor && !$field->isRelation) {
                if (!str_contains($field->key, '.') && in_array($field->key, $existingColumns)) {
                    $dbColumns[] = $tableName . '.' . $field->key;
                }
            }

            if (property_exists($field, 'dependencies') && !empty($field->dependencies)) {
                foreach ($field->dependencies as $dependency) {
                    if (in_array($dependency, $existingColumns)) {
                        $colWithTable = $tableName . '.' . $dependency;
                        if (!in_array($colWithTable, $dbColumns)) {
                            $dbColumns[] = $colWithTable;
                        }
                    }
                }
            }

            if ($field->isRelation || str_contains($field->key, '.')) {
                $parts = explode('.', $field->key);
                $relationName = $parts[0];

                if (method_exists($model, $relationName)) {
                    try {
                        $relation = $model->{$relationName}();

                        if ($relation instanceof Relation) {
                            $keyToAdd = null;
                            if ($relation instanceof BelongsTo) {
                                $keyToAdd = $relation->getForeignKeyName();
                            }
                            elseif ($relation instanceof HasOneOrMany) {
                                $keyToAdd = $relation->getLocalKeyName();
                            }

                            if ($keyToAdd && in_array($keyToAdd, $existingColumns)) {
                                $fullKey = $tableName . '.' . $keyToAdd;
                                if (!in_array($fullKey, $dbColumns)) {
                                    $dbColumns[] = $fullKey;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        // Ignoruj błędy wywołania metody, która nie jest relacją
                    }
                }
            }
        }

        if (!in_array($tableName . '.id', $dbColumns) && in_array('id', $existingColumns)) {
            $dbColumns[] = $tableName . '.id';
        }

        if ($hasHashTrait && !in_array($tableName . '.hash', $dbColumns) && in_array('hash', $existingColumns)) {
            $dbColumns[] = $tableName . '.hash';
        }

        $this->query->select($dbColumns);

        if (!$this->sortBy) {
            $this->sortBy($this->defaultSortBy);
        }

        if ($this->sortBy) {
            $sortColumn = in_array($this->sortBy, $existingColumns)
                ? $tableName . '.' . $this->sortBy
                : $this->sortBy;
            $this->query->orderBy($sortColumn, $this->descending ? 'desc' : 'asc');
        }

        if ($this->filters) {
            foreach($this->filters as $column => $value) {
                if (in_array($column, $existingColumns)) {
                    $this->query->where($tableName . '.' . $column, 'LIKE', '%'.$value.'%');
                }
            }
        }

        $paginator = $this->query->paginate($this->perPage);

        $paginator->through(function ($item) use ($hasHashTrait, $isIdRequested) {
            $mappedRow = [];

            foreach ($this->fields as $field) {
                if ($field->visible) {
                    $value = null;
                    if ($field->isRelation || str_contains($field->key, '.')) {
                        $value = data_get($item, $field->key);
                    } else {
                        $value = $item->{$field->key};
                    }

                    if ($field->dateFormat && $value) {
                        try {
                            $value = Carbon::parse($value)->format($field->dateFormat);
                        } catch (\Exception $e) {

                        }
                    }

                    if (str_contains($field->key, '.')) {
                        Arr::set($mappedRow, $field->key, $value);
                    } else {
                        $mappedRow[$field->key] = $value;
                    }
                }
            }

            if ($hasHashTrait) {
                $mappedRow['hash'] = $item->hash;
            }

            if (!$isIdRequested && isset($mappedRow["id"])) {
                unset($mappedRow["id"]);
            }

            return $mappedRow;
        });

        $columnsMetadata = array_map(function (Field $field) {
            return $field->toArray();
        }, $this->visible);

        return response()->json([
            'data'         => $paginator->items(),
            'columns'      => $columnsMetadata,
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'order'        => $this->sortBy,
            'descending'   => $this->descending,
            'filters'      => $this->filters
        ]);
    }
}
