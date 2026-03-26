<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\BangladeshGeoService;
use App\Services\CartService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    const DELIVERY_INSIDE_DHAKA  = 80;
    const DELIVERY_OUTSIDE_DHAKA = 130;

    public function index()
    {
        $items = CartService::get();

        if (empty($items)) {
            return redirect()->route('cart.index')->with('info', 'Your cart is empty.');
        }

        $subtotal        = CartService::total();
        $districts       = BangladeshGeoService::districts();
        $deliveryInside  = self::DELIVERY_INSIDE_DHAKA;
        $deliveryOutside = self::DELIVERY_OUTSIDE_DHAKA;

        return view('checkout.index', compact('items', 'subtotal', 'districts', 'deliveryInside', 'deliveryOutside'));
    }

    public function thanas(Request $request)
    {
        $district = $request->input('district', '');
        $thanas   = BangladeshGeoService::thanasForDistrict($district);

        return response()->json(['thanas' => $thanas]);
    }

    public function store(Request $request)
    {
        $items = CartService::get();

        if (empty($items)) {
            return redirect()->route('cart.index');
        }

        $validated = $request->validate([
            'shipping_name'     => 'required|string|max:255',
            'shipping_phone'    => 'required|string|max:20',
            'shipping_district' => 'required|string|max:100',
            'shipping_thana'    => 'required|string|max:100',
            'shipping_address'  => 'required|string|max:500',
            'billing_name'      => 'nullable|string|max:255',
            'billing_phone'     => 'nullable|string|max:20',
            'billing_district'  => 'nullable|string|max:100',
            'billing_thana'     => 'nullable|string|max:100',
            'billing_address'   => 'nullable|string|max:500',
            'payment_method'    => 'required|in:cod,bkash,online',
            'coupon_code'       => 'nullable|string|max:50',
            'notes'             => 'nullable|string|max:1000',
            'terms'             => 'accepted',
        ]);

        $subtotal     = CartService::total();
        $deliveryCost = ($validated['shipping_district'] === 'Dhaka')
            ? self::DELIVERY_INSIDE_DHAKA
            : self::DELIVERY_OUTSIDE_DHAKA;

        $couponDiscount = 0;
        $total          = $subtotal + $deliveryCost - $couponDiscount;

        $order = Order::create([
            'order_number'      => 'ORD-' . strtoupper(uniqid()),
            'status'            => 'pending',
            'shipping_name'     => $validated['shipping_name'],
            'shipping_phone'    => $validated['shipping_phone'],
            'shipping_district' => $validated['shipping_district'],
            'shipping_thana'    => $validated['shipping_thana'],
            'shipping_address'  => $validated['shipping_address'],
            'billing_name'      => $validated['billing_name']     ?? $validated['shipping_name'],
            'billing_phone'     => $validated['billing_phone']    ?? $validated['shipping_phone'],
            'billing_country'   => 'Bangladesh',
            'billing_district'  => $validated['billing_district'] ?? $validated['shipping_district'],
            'billing_thana'     => $validated['billing_thana']    ?? $validated['shipping_thana'],
            'billing_address'   => $validated['billing_address']  ?? $validated['shipping_address'],
            'payment_method'    => $validated['payment_method'],
            'payment_status'    => 'pending',
            'coupon_code'       => $validated['coupon_code'] ?? null,
            'coupon_discount'   => $couponDiscount,
            'notes'             => $validated['notes'] ?? null,
            'subtotal'          => $subtotal,
            'delivery_cost'     => $deliveryCost,
            'total'             => $total,
        ]);

        foreach ($items as $item) {
            OrderItem::create([
                'order_id'      => $order->id,
                'product_id'    => $item['id'],
                'product_name'  => $item['name'],
                'product_image' => $item['image'] ?? null,
                'price'         => $item['price'],
                'quantity'      => $item['qty'],
                'subtotal'      => $item['price'] * $item['qty'],
            ]);
        }

        CartService::clear();

        return redirect()->route('checkout.success', $order->order_number);
    }

    public function success(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with('items')
            ->firstOrFail();

        return view('checkout.success', compact('order'));
    }

    public function invoice(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with('items')
            ->firstOrFail();

        $pdf = Pdf::loadView('checkout.invoice', compact('order'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }
}
