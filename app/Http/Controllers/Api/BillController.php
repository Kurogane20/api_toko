<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;

/**
 * @OA\Tag(
 *     name="Bill",
 *     description="Operations related to bills"
 * )
 */
class BillController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/bill/{table_number}",
     *     summary="Get the bill for a specific table",
     *     tags={"Bill"},
     *     @OA\Parameter(
     *         name="table_number",
     *         in="path",
     *         description="The table number",
     *         required=true,
     *         @OA\Schema(type="string", example="MEJA NO 1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bill retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="table_number", type="string", example="MEJA NO 1"),
     *             @OA\Property(property="total_price", type="number", format="float", example=87000),
     *             @OA\Property(
     *                 property="bill_details",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_name", type="string", example="Jeruk"),
     *                     @OA\Property(property="variant", type="string", example="DINGIN"),
     *                     @OA\Property(property="price", type="number", format="float", example=12000),
     *                     @OA\Property(property="quantity", type="integer", example=1),
     *                     @OA\Property(property="subtotal", type="number", format="float", example=12000)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Table not found or no orders found for this table"
     *     )
     * )
     */
    public function getBill(Request $request, $tableNumber){
        $table = Table::where('table_number', $tableNumber)->first();
        if(!$table){
            return response()->json(['error' => 'Table not found'], 404);
        }
        $order =  Order::where('table_id', $table->id)->orderBy('created_at', 'desc')->first();
        if(!$order){
            return response()->json(['error' => 'Order not found'], 404);
        }

        $orderItems = $order->orderItems()->with('product')->get();

        $billDetails = $orderItems->map(function($item){
            return [
                'product_name' => $item->product->name,
                'variant' => $item->product->variant,
                'price' => $item->product->price,
                'quantity' => $item->quantity,
                'subtotal' => $item->product->price * $item->quantity

            ];
        });
         return response()->json([
            'order_id' => $order->id,
            'table_number' => $tableNumber,
            'total_price' => $order->total_price,
            'bill_details' => $billDetails
        ]);

    }
}
