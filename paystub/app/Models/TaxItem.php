<?php
namespace App\Models;

class TaxItem
{
    public string $order_id;
    public int $stub_index;
    public string $label;
    public float $current_amount;
    public float $ytd_amount;
    public int $sort_order;
    public ?string $origin;
}
