<?php

namespace Upsoftware\Svarium\JsonResource;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class Resource
{
    public static string $model;
    public static string | null $resource = null;
    public static string | null $resources = null;
    public bool $paginate = true;
    public int $per_page = 50;
    public bool $tenant = false;
    public array $middleware = [];
    public static array | bool $defaultSort = ['created_at' => 'desc'];

    abstract public function fields(): array;

    /**
     * Zwraca instancję modelu powiązanego z resource.
     */
    public function model(): Model
    {
        return new static::$model;
    }

    /**
     * Zwraca domyślny QueryBuilder — z obsługą customowej metody w modelu.
     */
    public function baseQuery(Request $request): Builder
    {
        $model = $this->model();

        // Jeśli model ma własną metodę resourceQuery(Request $request)
        if (method_exists($model, 'resourceQuery')) {
            return $model->resourceQuery($request);
        }

        // Domyślnie zwykłe zapytanie
        return $model::query();
    }

    /**
     * Dodatkowa modyfikacja QueryBuildera w resource.
     * Można np. dodać joiny, with() lub where().
     */
    public function queryBuilder(Builder $query, Request $request): Builder
    {
        // Domyślnie zwracamy bez zmian
        return $query;
    }

    /**
     * Pola sortowalne zdefiniowane w fields().
     */
    public function sortableFields(): array
    {
        return collect($this->fields())
            ->filter(fn($field) => $field->meta['sortable'] ?? false)
            ->map(fn($field) => $field->key)
            ->toArray();
    }

    /**
     * Zwraca pola w postaci tablicy.
     */
    public function fieldsToArray(): array
    {
        return collect($this->fields())
            ->map(fn ($field) => $field->toArray())
            ->values()
            ->toArray();
    }
}
