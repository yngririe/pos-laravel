<?php

namespace App\Http\Controllers;

use App\Models\Order;
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

        if (!in_array($data['order_type'] ?? null, ['dine-in', 'take-out'], true)) {
            return $this->fail('Invalid order type.');
        }
        if (!in_array($data['payment_method'] ?? null, ['cash', 'gcash'], true)) {
            return $this->fail('Invalid payment method.');
        }

        $gcashRef = trim((string) ($data['gcash_reference'] ?? ''));
        if ($gcashRef !== '' && !preg_match('/^\d{13}$/', $gcashRef)) {
            return $this->fail('Invalid GCash reference number.');
        }

        foreach (['amount_paid', 'subtotal', 'discount', 'total', 'change_amount'] as $field) {
            if (!isset($data[$field]) || !is_numeric($data[$field])) {
                return $this->fail("Missing or invalid $field.");
            }
        }

        foreach ($data['items'] as $item) {
            if (!isset($item['name'], $item['price'], $item['qty'])
                || !is_numeric($item['price']) || (int) $item['qty'] < 1) {
                return $this->fail('Invalid order item.');
            }
        }

        $beeperInUse = Order::where('beeper_number', $beeper)
            ->where('status', 'pending')
            ->where('branch_id', $branch_id)
            ->exists();
        if ($beeperInUse) {
            return $this->fail('beeper_in_use');
        }

        try {
            $order = DB::transaction(function () use ($data, $branch_id, $beeper, $gcashRef) {
                $order = Order::create([
                    'branch_id'       => $branch_id,
                    'beeper_number'   => $beeper,
                    'order_type'      => $data['order_type'],
                    'payment_method'  => $data['payment_method'],
                    'amount_paid'     => $data['amount_paid'],
                    'gcash_reference' => $gcashRef,
                    'subtotal'        => $data['subtotal'],
                    'discount'        => $data['discount'],
                    'total'           => $data['total'],
                    'change_amount'   => $data['change_amount'],
                ]);

                $order->items()->createMany($this->itemRows($data['items']));

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

        if (!isset($data['total']) || !is_numeric($data['total'])) {
            return $this->fail('Missing or invalid total.');
        }

        $extraRef = $data['gcash_ref'] ?? null;
        if (!empty($extraRef) && !preg_match('/^\d{13}$/', (string) $extraRef)) {
            return $this->fail('Invalid GCash reference number. Must be 13 digits.');
        }

        $order = Order::where('id', $data['order_id'])->where('branch_id', $branch_id)->first();
        if (!$order) {
            return $this->fail('Order not found or unauthorized');
        }

        $diff = (float) $data['total'] - (float) $order->total;

        try {
            DB::transaction(function () use ($order, $data, $beeper, $extraRef) {
                $order->update([
                    'beeper_number'      => $beeper,
                    'order_type'         => $data['order_type'],
                    'payment_method'     => $data['payment_method'],
                    'amount_paid'        => $data['amount_paid'] ?? $data['total'],
                    'subtotal'           => $data['subtotal'] ?? $data['total'],
                    'discount'           => $data['discount'] ?? 0,
                    'total'              => $data['total'],
                    // keep the previous extra reference unless a new one was supplied
                    'gcash_reference_extra' => !empty($extraRef) ? $extraRef : $order->gcash_reference_extra,
                    'gcash_extra_amount' => $data['gcash_extra_amount'] ?? 0,
                    'refund_amount'      => $data['refund_amount'] ?? 0,
                ]);

                if (!empty($data['items']) && is_array($data['items'])) {
                    $order->items()->delete();
                    $order->items()->createMany($this->itemRows($data['items']));
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return $this->fail('Could not update the order.');
        }

        return response()->json([
            'success'       => true,
            'diff'          => $diff,
            'refund_amount' => $data['refund_amount'] ?? 0,
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

    private function itemRows(array $items): array
    {
        return array_map(fn ($item) => [
            'menu_item_id' => $item['id'] ?? null,
            'name'         => $item['name'],
            'price'        => $item['price'],
            'quantity'     => (int) $item['qty'],
        ], $items);
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message]);
    }
}
