<?php

declare(strict_types=1);

namespace Laravel\Mcp\Support;

use Illuminate\Support\Arr;
use Stringable;

/**
 * Минимальная замена Illuminate\Support\Uri, которого нет в Laravel 10
 * (добавлен в 11.35). Реализует ровно то подмножество, которое использует
 * пакет — of() / query() / withQuery() / приведение к строке, — с той же
 * семантикой: withQuery() по умолчанию мёржится с уже имеющейся query-строкой,
 * ключи раскрываются через data_set(), сборка идёт через Arr::query()
 * (RFC 3986), фрагмент сохраняется.
 */
class Uri implements Stringable
{
    public function __construct(protected string $uri = '')
    {
        //
    }

    public static function of(Stringable|string $uri = ''): static
    {
        return new static((string) $uri);
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        parse_str($this->parts()['query'], $query);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function withQuery(array $query, bool $merge = true): static
    {
        $newQuery = $merge ? $this->query() : [];

        foreach ($query as $key => $value) {
            data_set($newQuery, $key, $value);
        }

        $parts = $this->parts();
        $string = Arr::query($newQuery);

        return new static($parts['base'].($string !== '' ? '?'.$string : '').$parts['fragment']);
    }

    public function value(): string
    {
        return $this->uri;
    }

    public function __toString(): string
    {
        return $this->value();
    }

    /**
     * @return array{base: string, query: string, fragment: string}
     */
    protected function parts(): array
    {
        $uri = $this->uri;
        $fragment = '';

        if (($position = strpos($uri, '#')) !== false) {
            $fragment = substr($uri, $position);
            $uri = substr($uri, 0, $position);
        }

        $query = '';

        if (($position = strpos($uri, '?')) !== false) {
            $query = substr($uri, $position + 1);
            $uri = substr($uri, 0, $position);
        }

        return ['base' => $uri, 'query' => $query, 'fragment' => $fragment];
    }
}
