<?php
namespace App\Services;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;

class PeriodsService
{
    /**
     * Genera períodos de pago hacia atrás desde hoy (incluyendo el actual si aplica).
     * @param string $schedule weekly|biweekly|semi-monthly|monthly
     * @param int $count número de stubs (períodos) a generar
     * @return array<int,array{start_date:string,end_date:string,pay_date:string,index:int}>
     */
    public function generate(string $schedule, int $count): array
    {
        $schedule = strtolower($schedule);
        $today = new DateTimeImmutable('today');
        $periods = [];

        switch ($schedule) {
            case 'weekly':
            case 'biweekly':
                $days = $schedule === 'weekly' ? 7 : 14;
                $cursorEnd = $this->endOfCurrentRange($today, $days);
                for ($i = 0; $i < $count; $i++) {
                    $start = $cursorEnd->modify('-' . ($days - 1) . ' days');
                    $periods[] = [
                        'start_date' => $start->format('Y-m-d'),
                        'end_date'   => $cursorEnd->format('Y-m-d'),
                        'pay_date'   => $cursorEnd->modify('+3 days')->format('Y-m-d'), // Asumido: pago 3 días después cierre
                        'index'      => $i,
                    ];
                    $cursorEnd = $start->modify('-1 day');
                }
                break;

            case 'semi-monthly':
                // Periodos: 1-15 y 16-fin de mes.
                $cursor = $today;
                for ($i = 0; $i < $count; $i++) {
                    $day = (int)$cursor->format('d');
                    if ($day > 15) { // Estamos en segunda mitad → periodo 16-fin
                        $start = $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), 16);
                        $end = $cursor->modify('last day of this month');
                    } else { // Primera mitad → 1-15
                        $start = $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), 1);
                        $end = $cursor->setDate((int)$cursor->format('Y'), (int)$cursor->format('m'), 15);
                    }
                    $periods[] = [
                        'start_date' => $start->format('Y-m-d'),
                        'end_date'   => $end->format('Y-m-d'),
                        'pay_date'   => $end->modify('+2 days')->format('Y-m-d'),
                        'index'      => $i,
                    ];
                    // Retroceder al día anterior al inicio para siguiente iteración.
                    $cursor = $start->modify('-1 day');
                }
                break;

            case 'monthly':
                $cursor = $today->modify('last day of this month');
                for ($i = 0; $i < $count; $i++) {
                    $start = $cursor->modify('first day of this month');
                    $end = $cursor; // fin de mes
                    $periods[] = [
                        'start_date' => $start->format('Y-m-d'),
                        'end_date'   => $end->format('Y-m-d'),
                        'pay_date'   => $end->modify('+5 days')->format('Y-m-d'),
                        'index'      => $i,
                    ];
                    $cursor = $start->modify('-1 day')->modify('last day of this month');
                }
                break;

            default:
                return [];
        }

        return $periods; // Orden: más reciente primero.
    }

    private function endOfCurrentRange(DateTimeImmutable $today, int $spanDays): DateTimeImmutable
    {
        // Encuentra el final del bloque actual de longitud spanDays alineado a lunes (para weekly/biweekly) como referencia simple.
        $weekDay = (int)$today->format('N'); // 1 (Mon) - 7 (Sun)
        // Consideramos rango que termina el próximo domingo.
        $daysToSunday = 7 - $weekDay; // si hoy es domingo -> 0
        $end = $today->modify("+{$daysToSunday} days");
        // Para biweekly, ajustamos al bloque de 14 días que contenga hoy.
        if ($spanDays === 14) {
            // Determina inicio de semana (lunes)
            $startOfWeek = $today->modify('-' . ($weekDay - 1) . ' days');
            // Alterna bloques de 2 semanas tomando como época un lunes arbitrario (1970-01-05 es lunes)
            $epoch = new DateTimeImmutable('1970-01-05');
            $diffDays = $startOfWeek->diff($epoch)->days;
            $isOddBlock = ($diffDays / 7) % 2 === 1; // semana impar → segundo bloque
            if ($isOddBlock) {
                // Estamos en segunda semana del bloque → fin ya correcto (domingo).
            } else {
                // Primera semana del bloque → mover al domingo de la semana siguiente.
                $end = $end->modify('+7 days');
            }
        }
        return $end;
    }
}
