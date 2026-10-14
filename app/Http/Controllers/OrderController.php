<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\MenuItem;
use App\Support\Pos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /** Pending orders page. */
    public function index()
    {
        $branch_id = Pos::branchId();

        $orders = Pos::all("
            SELECT o.*,
                   GROUP_CONCAT(oi.name         ORDER BY oi.id SEPARATOR '||') AS item_names,
                   GROUP_CONCAT(oi.price        ORDER BY oi.id SEPARATOR '||') AS item_prices,
                   GROUP_CONCAT(oi.quantity     ORDER BY oi.id SEPARATOR '||') AS item_qtys,
                   GROUP_CONCAT(oi.menu_item_id ORDER BY oi.id SEPARATOR '||') AS item_ids
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            WHERE o.status = 'pending' AND o.branch_id = ?
            GROUP BY o.id
            ORDER BY o.created_at ASC
        ", [$branch_id]);

        $orders = Pos::localizeRows($orders);

        [$menu_items, $discount_map] = Pos::menu();

        return view('pages.orders', Pos::shared('orders') + compact('orders', 'menu_items', 'discount_map'));
    }

    /** Place a new order (JSON in / JSON out, same shape the old place_order.php used). */
    public function store(Request $request): JsonResponse
    {
        $data      = $request->all();
        $branch_id = Pos::branchId();

        if (empty($data['items']) || !is_array($data['items'])) {
            return $this->fail('No items');
        }

        $beeper = (int) ($data['beeper_number'] ?? 0);
        if ($beeper < 1 || $beeper > 16) {
            return $this->fail('Beeper number must be between 1 and 16.');
        }

        if (!in_array($data['order_type'] ?? null, ['dine-in', 'take-out', 'delivery', 'pick-up'], true)) {
            return $this->fail('Invalid order type.');
        }
        if (!in_array($data['payment_method'] ?? null, ['cash', 'gcash'], true)) {
            return $this->fail('Invalid payment method.');
        }


        if (!isset($data['amount_paid']) || !is_numeric($data['amount_paid'])) {
            return $this->fail('Missing or invalid amount paid.');
        }

        $discountType = (string) ($data['discount_type'] ?? 'none');
        if (!in_array($discountType, ['none', '20', '10', 'minus10'], true)) {
            return $this->fail('Invalid discount type.');
        }

        $priced = $this->priceItems($data['items']);
        if ($priced === null) {
            return $this->fail('One or more order items are invalid.');
        }
        [$itemRows, $subtotal, $discount, $total] = $this->calculateTotals($priced, $discountType);
        $amountPaid = (float) $data['amount_paid'];
        if (($data['payment_method'] ?? null) === 'cash' && $amountPaid < $total) {
            return $this->fail('Amount paid must be equal to or greater than the total.');
        }
        if (($data['payment_method'] ?? null) === 'gcash') {
            $amountPaid = $total;
        }
        $changeAmount = ($data['payment_method'] ?? null) === 'cash' ? max(0, $amountPaid - $total) : 0;

        $beeperInUse = Order::where('beeper_number', $beeper)
            ->where('status', 'pending')
            ->where('branch_id', $branch_id)
            ->exists();
        if ($beeperInUse) {
            return $this->fail('beeper_in_use');
        }

        try {
            $order = DB::transaction(function () use ($data, $branch_id, $beeper, $itemRows, $subtotal, $discount, $total, $amountPaid, $changeAmount) {
                $order = Order::create([
                    'branch_id'       => $branch_id,
                    'beeper_number'   => $beeper,
                    'order_type'      => $data['order_type'],
                    'payment_method'  => $data['payment_method'],
                    'amount_paid'     => $amountPaid,
                    'gcash_reference' => null,
                    'subtotal'        => $subtotal,
                    'discount'        => $discount,
                    'total'           => $total,
                    'change_amount'   => $changeAmount,
                ]);

                $order->items()->createMany($itemRows);

                return $order;
            });
        } catch (\Throwable $e) {
            report($e);
            return $this->fail('Could not save the order.');
        }

        return response()->json(['success' => true, 'order_id' => $order->id]);
    }

    /** Edit a pending order (items, totals, GCash top-up / refund). */
    public function update(Request $request): JsonResponse
    {
        $data      = $request->all();
        $branch_id = Pos::branchId();

        if (empty($data['order_id'])) {
            return $this->fail('Missing order ID');
        }

        $beeper = (int) ($data['beeper_number'] ?? 0);
        if ($beeper < 1 || $beeper > 16) {
            return $this->fail('Beeper number must be between 1 and 16.');
        }

        if (!in_array($data['order_type'] ?? null, ['dine-in', 'take-out', 'delivery', 'pick-up'], true)) {
            return $this->fail('Invalid order type.');
        }
        if (!in_array($data['payment_method'] ?? null, ['cash', 'gcash'], true)) {
            return $this->fail('Invalid payment method.');
        }
        $discountType = (string) ($data['discount_type'] ?? 'none');
        if (!in_array($discountType, ['none', '20', '10', 'minus10'], true)) {
            return $this->fail('Invalid discount type.');
        }

        $order = Order::where('id', $data['order_id'])->where('branch_id', $branch_id)->first();
        if (!$order) {
            return $this->fail('Order not found or unauthorized');
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            return $this->fail('No items');
        }
        $priced = $this->priceItems($data['items']);
        if ($priced === null) {
            return $this->fail('One or more order items are invalid.');
        }
        [$itemRows, $subtotal, $discount, $total] = $this->calculateTotals($priced, $discountType);
        $amountPaid = ($data['payment_method'] ?? null) === 'gcash'
            ? $total
            : max((float) ($data['amount_paid'] ?? $total), $total);

        $diff = $total - (float) $order->total;

        try {
            DB::transaction(function () use ($order, $data, $beeper, $itemRows, $subtotal, $discount, $total, $amountPaid, $diff) {
                $order->update([
                    'beeper_number'      => $beeper,
                    'order_type'         => $data['order_type'],
                    'payment_method'     => $data['payment_method'],
                    'amount_paid'        => $amountPaid,
                    'subtotal'           => $subtotal,
                    'discount'           => $discount,
                    'total'              => $total,
                    'gcash_reference'    => null,
                    'gcash_reference_extra' => null,
                    'gcash_extra_amount' => max(0, $diff ?? 0),
                    'refund_amount'      => max(0, -($diff ?? 0)),
                ]);

                if (!empty($data['items']) && is_array($data['items'])) {
                    $order->items()->delete();
                    $order->items()->createMany($itemRows);
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return $this->fail('Could not update the order.');
        }

        return response()->json([
            'success'       => true,
            'diff'          => $diff,
            'refund_amount' => max(0, -$diff),
        ]);
    }

    public function serve(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');
        if (!$orderId) {
            return $this->fail('No order ID');
        }

        $affected = Order::where('id', $orderId)
            ->where('branch_id', Pos::branchId())
            ->update(['status' => 'served', 'served_at' => Pos::nowUtc()]);

        return $affected === 0
            ? $this->fail('Order not found or unauthorized')
            : response()->json(['success' => true]);
    }

    public function void(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');
        if (!$orderId) {
            return $this->fail('No order ID');
        }

        $affected = Order::where('id', $orderId)
            ->where('branch_id', Pos::branchId())
            ->update(['status' => 'voided']);

        return $affected === 0
            ? $this->fail('Order not found or unauthorized')
            : response()->json(['success' => true]);
    }

    private function priceItems(array $items): ?array
    {
        $ids = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $menu = MenuItem::whereIn('id', $ids)->get()->keyBy('id');
        $priced = [];
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $menuItem = $menu->get($id);
            if (!$menuItem || $qty < 1) return null;
            $priced[] = [
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => (float) $menuItem->price,
                'quantity' => $qty,
            ];
        }
        return $priced;
    }

    private function calculateTotals(array $items, string $discountType): array
    {
        $subtotal = (int) round(array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $items)));
        $lowest = empty($items) ? 0 : min(array_column($items, 'price'));
        $discount = match ($discountType) {
            '20' => (int) round($lowest * 0.20),
            '10' => (int) round($lowest * 0.10),
            'minus10' => (int) min(10, $lowest),
            default => 0,
        };
        return [$items, $subtotal, $discount, max(0, $subtotal - $discount)];
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message]);
    }
}
