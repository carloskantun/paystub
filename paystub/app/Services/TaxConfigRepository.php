<?php
namespace App\Services;

use PDO;

/**
 * TaxConfigRepository
 * Lee configuraciones fiscales desde tablas (multi-año) con fallbacks seguros.
 */
class TaxConfigRepository
{
    private PDO $db;
    public function __construct()
    {
        $this->db = db();
    }

    public function getStandardDeduction(int $year, string $status='single'): float
    {
        try {
            $col = $status === 'married' ? 'standard_deduction_married' : 'standard_deduction_single';
            $stmt = $this->db->prepare("SELECT $col AS val FROM tax_years WHERE year = :y LIMIT 1");
            $stmt->execute([':y'=>$year]);
            $val = $stmt->fetchColumn();
            if ($val === false || $val === null) {
                return $status==='married' ? 29200.0 : 14600.0; // fallback
            }
            return (float)$val;
        } catch (\Throwable $e) {
            return $status==='married' ? 29200.0 : 14600.0; // fallback si tabla no existe
        }
    }

    public function getFederalBrackets(int $year, string $status='single'): array
    {
        try {
            $stmt = $this->db->prepare("SELECT threshold, rate FROM federal_brackets WHERE year=:y AND filing_status=:s ORDER BY bracket_order ASC");
            $stmt->execute([':y'=>$year, ':s'=>$status]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $rows = [];
        }
        if (!$rows) {
            // Fallback a 2024 Single / Married (simplificada)
            if ($status==='married') {
                return [
                    ['threshold'=>0,'rate'=>0.10],
                    ['threshold'=>23200,'rate'=>0.12],
                    ['threshold'=>94300,'rate'=>0.22],
                    ['threshold'=>201050,'rate'=>0.24],
                    ['threshold'=>383900,'rate'=>0.32],
                    ['threshold'=>487450,'rate'=>0.35],
                    ['threshold'=>731200,'rate'=>0.37],
                ];
            }
            return [
                ['threshold'=>0,'rate'=>0.10],
                ['threshold'=>11600,'rate'=>0.12],
                ['threshold'=>47150,'rate'=>0.22],
                ['threshold'=>100525,'rate'=>0.24],
                ['threshold'=>191950,'rate'=>0.32],
                ['threshold'=>243725,'rate'=>0.35],
                ['threshold'=>609350,'rate'=>0.37],
            ];
        }
        return array_map(fn($r)=>['threshold'=>(float)$r['threshold'],'rate'=>(float)$r['rate']], $rows);
    }

    public function getFica(int $year): array
    {
        try {
            $stmt = $this->db->prepare("SELECT ss_wage_base, ss_rate_employee, medicare_rate, addl_medicare_threshold_single, addl_medicare_rate FROM fica_limits WHERE year=:y LIMIT 1");
            $stmt->execute([':y'=>$year]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $row = null;
        }
        if (!$row) {
            return [
                'ss_wage_base'=>168600.0,
                'ss_rate_employee'=>0.062,
                'medicare_rate'=>0.0145,
                'addl_medicare_threshold_single'=>200000.0,
                'addl_medicare_rate'=>0.009,
            ];
        }
        return [
            'ss_wage_base'=>(float)($row['ss_wage_base'] ?? 168600.0),
            'ss_rate_employee'=>(float)($row['ss_rate_employee'] ?? 0.062),
            'medicare_rate'=>(float)($row['medicare_rate'] ?? 0.0145),
            'addl_medicare_threshold_single'=>(float)($row['addl_medicare_threshold_single'] ?? 200000.0),
            'addl_medicare_rate'=>(float)($row['addl_medicare_rate'] ?? 0.009),
        ];
    }

    public function getStateRate(int $year, ?string $state): float
    {
        if (!$state) return 0.0;
        try {
            $stmt = $this->db->prepare("SELECT rate FROM state_tax_rates WHERE year=:y AND state_code=:s LIMIT 1");
            $stmt->execute([':y'=>$year, ':s'=>strtoupper($state)]);
            $val = $stmt->fetchColumn();
            return $val !== false && $val !== null ? (float)$val : 0.0;
        } catch (\Throwable $e) {
            return 0.0; // fallback si tabla ausente
        }
    }
}
