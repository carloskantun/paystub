<?php
namespace App\Models;

class EarningItem
{
    public string $order_id;
    public int $stub_index;
    public string $label;
    public ?float $hours;
    public ?float $rate;
    public float $current_amount;
    public float $ytd_amount;
    public int $sort_order;
    public ?string $origin;

    // convenience: formatted getters could be added here if needed
}
