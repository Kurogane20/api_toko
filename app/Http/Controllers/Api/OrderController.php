<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *     title="Toko API",
 *     version="1.0.0",
 *     description="API documentation for Toko API",
 * )
 */
class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/order",
     *     summary="Create a new order",
     *     tags={"Order"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="table_number", type="string", example="MEJA NO 1"),
     *             @OA\Property(
     *                 property="order_items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="quantity", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="total_price", type="number", format="float", example=89000),
     *             @OA\Property(
     *                 property="printers",
     *                 type="array",
     *                 @OA\Items(type="string", example="Bar")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Table not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Order processing failed"
     *     )
     * )
     */
    public function postOrder(Request $request)
    {
        $tableNumber = $request->input('table_number');
        $orderItems = $request->input('order_items');

        $table = Table::where('table_number', $tableNumber)->first();

        if (!$table) {
            return response()->json(['error' => 'Table not found'], 404);
        }

        DB::beginTransaction();
        try {
            $order = new Order();
            $order->table_id = $table->id;
            $order->total_price = 0;
            $order->save();

            $totalPrice = 0;
            $printerTypes = [];

            foreach ($orderItems as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $orderItem = new OrderItem();
                    $orderItem->order_id = $order->id;
                    $orderItem->product_id = $product->id;
                    $orderItem->quantity = $item['quantity'];
                    $orderItem->save();

                    $totalPrice += $product->price * $item['quantity'];

                    if ($product->category === 'Minuman' || $product->category === 'Promo') {
                        $printerTypes['Bar'] = true;
                    } else if ($product->category === 'Makanan') {
                        $printerTypes['Dapur'] = true;
                    }
                }
            }

            $order->total_price = $totalPrice;
            $order->save();

            DB::commit();

            return response()->json([
                'order_id' => $order->id,
                'total_price' => $totalPrice,
                'printers' => array_keys($printerTypes)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Order processing failed'], 500);
        }
    }
}
