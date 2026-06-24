<?php
/**
 * 日志查询公共参数
 */

class LogQuery
{
    public const PER_PAGE = 20;

    public static function params(array $input): array
    {
        $page = max(1, (int) ($input['page'] ?? 1));
        $perPage = self::PER_PAGE;
        $dateFrom = trim($input['date_from'] ?? '');
        $dateTo = trim($input['date_to'] ?? '');
        if ($dateFrom === '' && $dateTo === '') {
            $dateTo = date('Y-m-d');
            $dateFrom = date('Y-m-d', strtotime('-6 days'));
        }
        $scope = trim($input['search_scope'] ?? 'all');
        $keyword = trim($input['search_keyword'] ?? '');

        return [
            'page'          => $page,
            'per_page'      => $perPage,
            'offset'        => ($page - 1) * $perPage,
            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,
            'search_scope'  => $scope === '' ? 'all' : $scope,
            'search_keyword'=> $keyword,
        ];
    }

    public static function dateWhere(string $field, string $dateFrom, string $dateTo, array &$bind): string
    {
        $sql = '';
        if ($dateFrom !== '') {
            $sql .= " AND {$field} >= ?";
            $bind[] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $sql .= " AND {$field} <= ?";
            $bind[] = $dateTo . ' 23:59:59';
        }
        return $sql;
    }

    public static function like(string $keyword): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword) . '%';
    }

    /**
     * 统一 COLLATE，避免不同列字符集混用导致 LIKE 报错
     */
    public static function likeExpr(string $column): string
    {
        return 'CAST(`' . str_replace('`', '``', $column) . '` AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?';
    }

    public static function buildSearch(array $scopeMap, string $scope, string $keyword, array &$bind): string
    {
        if ($keyword === '') {
            return '';
        }

        $like = self::like($keyword);
        if ($scope === 'all') {
            $parts = [];
            foreach ($scopeMap as $column) {
                $parts[] = self::likeExpr($column);
                $bind[] = $like;
            }
            return ' AND (' . implode(' OR ', $parts) . ')';
        }

        if (!isset($scopeMap[$scope])) {
            return '';
        }

        $bind[] = $like;
        return ' AND ' . self::likeExpr($scopeMap[$scope]);
    }

    public static function paginate(string $table, string $where, array $bind, int $page, int $perPage): array
    {
        $countSql = "SELECT COUNT(*) AS cnt FROM `{$table}` WHERE 1=1 {$where}";
        $row = DB::fetchOne($countSql, $bind);
        $total = (int) ($row['cnt'] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }
}
