<?php

namespace App\Http\Middleware;

use Closure;
use DB;

class Sql
{
    /**
     * 运行请求过滤器
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->has('sqldebug')) {
            DB::enableQueryLog();
        }
        $response = $next($request);
        if ($request->has('sqldebug')) {
            $pdo = DB::getPdo();
            $queries = DB::getQueryLog();
            $sqldebug = [];
            foreach ($queries as $key => $query) {
                $statement = $pdo->prepare('EXPLAIN ' . $query['query']);
                $statement->execute($query['bindings']);
                $sqldebug[$key]['explain'] = $statement->fetchAll(\PDO::FETCH_CLASS);
                $sqldebug[$key]['query'] = $this->bindValues($query['query'], $query['bindings']);
                $sqldebug[$key]['time'] = $query['time'];
            }
            $response->setContent(json_decode($response->content(), true) + ['sqldebug' => $sqldebug]);
        }
        return $response;
    }

    /**
     * Bind values to their parameters in the given query.
     *
     * @param string $query
     * @param array $bindings
     * @return string
     */
    private function bindValues($query, $bindings)
    {
        $keys = [];
        $values = $bindings;
        // build a regular expression for each parameter
        foreach ($bindings as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }
            if (is_string($value))
                $values[$key] = "'" . $value . "'";
            if (is_array($value))
                $values[$key] = "'" . implode("','", $value) . "'";
            if (is_null($value))
                $values[$key] = 'NULL';
        }
        return preg_replace($keys, $values, $query, 1, $count);
    }
}