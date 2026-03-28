@extends('layouts.app')
@section('title', $product->meta_title ?? $product->name . ' - Digital Support')
@section('meta_description', $product->meta_description ?? $product->short_description)

@section('content')
@php
    $tc = \App\Filament\Pages\TemplateSettings::defaults();
    foreach ($tc as $k => $d) { $tc[$k] = \App\Services\SettingService::get($k, $d); }
@endphp
@include('components.breadcrumb', ['items' => [['label' => 'Products', 'url' => route('products.index')], ['label' => $product->name]]])

<div class="container-custom px-4 sm:px-6 lg:px-8 pb-16">
    <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 relative">
        <!-- Product Images: Vertical Thumbs + Main Image -->
        <div>
            @php
                $allMedia = $product->getMedia('product_images');
                $mainImage = $product->getFirstMediaUrl('product_images', 'large') ?: $product->getFirstMediaUrl('product_thumbnail', 'large');
                $fullImage = $product->getFirstMediaUrl('product_images') ?: $product->getFirstMediaUrl('product_thumbnail');
            @endphp
            <div style="display: flex; gap: 12px;">
                {{-- Vertical Thumbnails --}}
                @if($allMedia->count() > 1)
                <div id="thumb-strip" style="display: flex; flex-direction: column; gap: 8px; width: 70px; min-width: 70px; max-width: 70px; overflow-y: auto; max-height: 500px;">
                    @foreach($allMedia as $i => $media)
                    <div class="thumb-item cursor-pointer border-2 {{ $i === 0 ? 'border-primary-500' : 'border-surface-200' }} hover:border-primary-500 transition-colors duration-200"
                         style="width: 70px; height: 70px; min-height: 70px; border-radius: 8px; overflow: hidden; flex-shrink: 0;"
                         data-large="{{ $media->getUrl('large') }}" data-zoom="{{ $media->getUrl() }}">
                        <img src="{{ $media->getUrl('thumb') }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Main Image with Zoom --}}
                <div style="flex: 1; min-width: 0; position: relative;" id="image-wrapper">
                    <div id="zoom-container" class="bg-surface-50 border border-surface-200 rounded-xl overflow-hidden relative {{ $mainImage ? 'cursor-crosshair' : '' }}" style="aspect-ratio: 1/1;">
                        @if($mainImage)
                            <img id="main-image" src="{{ $mainImage }}" data-zoom="{{ $fullImage ?: $mainImage }}" alt="{{ $product->name }}" class="w-full h-full object-contain p-6">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-32 h-32 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        @endif
                    </div>
                    {{-- Zoom Result: positioned over the right column --}}
                    @if($mainImage)
                    <div id="zoom-result" class="hidden absolute top-0 bg-white rounded-xl shadow-2xl border border-surface-200 overflow-hidden z-50 bg-no-repeat pointer-events-none" style="left: calc(100% + 1.5rem);"></div>
                    @endif
                    <p class="text-xs text-surface-400 mt-2 italic">N.B: Image may differ with actual product's layout, color, size & dimension.</p>
                </div>
            </div>
        </div>

        <!-- Product Info -->
        <div class="relative z-10">
            @if($product->brand)
                <p class="text-sm text-surface-500 uppercase tracking-wide mb-2">{{ $product->brand->name }}</p>
            @endif
            <h1 class="text-lg md:text-xl font-display mb-4 leading-tight">{{ $product->name }}</h1>

            @if($product->sku)
                <p class="text-sm text-surface-500 mb-4">SKU: {{ $product->sku }}</p>
            @endif

            <div class="flex items-center gap-4 mb-6">
                <span class="text-xl font-bold text-primary-600">{{ number_format($product->price, 0) }}৳</span>
                @if($product->compare_price)
                    <span class="text-base text-surface-400 line-through">{{ number_format($product->compare_price, 0) }}৳</span>
                    <span class="bg-accent-100 text-accent-700 text-sm font-semibold px-3 py-1 rounded-full">
                        Save {{ round(($product->compare_price - $product->price) / $product->compare_price * 100) }}%
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2 mb-6">
                <span class="inline-flex items-center gap-1 text-sm font-medium {{ $product->in_stock ? 'text-primary-600' : 'text-accent-500' }}">
                    <span class="w-2 h-2 rounded-full {{ $product->in_stock ? 'bg-primary-500' : 'bg-accent-500' }}"></span>
                    {{ $product->in_stock ? 'In Stock ('.$product->stock_quantity.' available)' : 'Out of Stock' }}
                </span>
            </div>

            @if($product->short_description)
                <div class="text-surface-600 mb-6 prose prose-sm">{!! $product->short_description !!}</div>
            @endif

            {{-- Quantity + Action Buttons --}}
            @php
                $waNumber = preg_replace('/[^0-9]/', '', \App\Services\SettingService::get('contact_whatsapp', ''));
                $phoneNumber = \App\Services\SettingService::get('contact_phone', '');
                $waMessage   = urlencode('Hi, I want to order: ' . $product->name . ' (Price: ' . number_format($product->price, 0) . '৳)');
            @endphp
            <div x-data="{ qty: 1 }" style="margin-bottom:24px;">
                {{-- Quantity --}}
                <div style="display:flex; align-items:center; gap:16px; margin-bottom:16px;">
                    <span style="font-size:0.9rem; font-weight:600; color:#374151;">Quantity:</span>
                    <div style="display:inline-flex; align-items:center; border:1.5px solid #d1d5db; border-radius:8px; overflow:hidden;">
                        <button type="button" @click="qty = Math.max(1, qty - 1)"
                            style="width:38px; height:38px; background:#f9fafb; border:none; font-size:1.2rem; font-weight:600; color:#374151; cursor:pointer; display:flex; align-items:center; justify-content:center;"
                            onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#f9fafb'">−</button>
                        <span x-text="qty" style="min-width:40px; text-align:center; font-size:1rem; font-weight:700; color:#111827; border-left:1.5px solid #d1d5db; border-right:1.5px solid #d1d5db; height:38px; line-height:38px;"></span>
                        <button type="button" @click="qty++"
                            style="width:38px; height:38px; background:#f9fafb; border:none; font-size:1.2rem; font-weight:600; color:#374151; cursor:pointer; display:flex; align-items:center; justify-content:center;"
                            onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#f9fafb'">+</button>
                    </div>
                </div>

                {{-- Add to Cart + Buy Now --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                    <button type="button"
                        @click="addToCart({{ $product->id }}, qty)"
                        @if(!$product->in_stock) disabled @endif
                        class="btn-cart"
                        style="display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:13px 10px; background:{{ $product->in_stock ? $tc['color_btn_cart_bg'] : '#9ca3af' }}; color:{{ $tc['color_btn_cart_text'] }}; font-size:0.88rem; font-weight:700; border:none; border-radius:8px; cursor:{{ $product->in_stock ? 'pointer' : 'not-allowed' }}; transition:background 0.2s, color 0.2s;"
                        onmouseover="if(!this.disabled){this.style.background='{{ $tc['color_btn_cart_hover_bg'] }}';this.style.color='{{ $tc['color_btn_cart_hover_text'] }}'}"
                        onmouseout="if(!this.disabled){this.style.background='{{ $tc['color_btn_cart_bg'] }}';this.style.color='{{ $tc['color_btn_cart_text'] }}'}">
                        <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        ADD TO CART
                    </button>
                    <button type="button"
                        @click="buyNow({{ $product->id }}, qty)"
                        @if(!$product->in_stock) disabled @endif
                        class="btn-buy"
                        style="display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:13px 10px; background:{{ $product->in_stock ? $tc['color_btn_buy_bg'] : '#9ca3af' }}; color:{{ $tc['color_btn_buy_text'] }}; font-size:0.88rem; font-weight:700; border:none; border-radius:8px; cursor:{{ $product->in_stock ? 'pointer' : 'not-allowed' }}; transition:background 0.2s, color 0.2s;"
                        onmouseover="if(!this.disabled){this.style.background='{{ $tc['color_btn_buy_hover_bg'] }}';this.style.color='{{ $tc['color_btn_buy_hover_text'] }}'}"
                        onmouseout="if(!this.disabled){this.style.background='{{ $tc['color_btn_buy_bg'] }}';this.style.color='{{ $tc['color_btn_buy_text'] }}'}">
                        <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        BUY NOW
                    </button>
                </div>

                {{-- WhatsApp + Call --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <a href="{{ $waNumber ? 'https://wa.me/'.$waNumber.'?text='.$waMessage : '#' }}"
                        @if(!$waNumber) onclick="return false;" @else target="_blank" rel="noopener" @endif
                        class="btn-wa"
                        style="display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:13px 10px; background:{{ $tc['color_btn_wa_bg'] }}; color:{{ $tc['color_btn_wa_text'] }}; font-size:0.88rem; font-weight:700; text-decoration:none; border-radius:8px; transition:background 0.2s, color 0.2s; {{ !$waNumber ? 'opacity:0.5; cursor:not-allowed;' : '' }}"
                        onmouseover="this.style.background='{{ $tc['color_btn_wa_hover_bg'] }}';this.style.color='{{ $tc['color_btn_wa_hover_text'] }}'"
                        onmouseout="this.style.background='{{ $tc['color_btn_wa_bg'] }}';this.style.color='{{ $tc['color_btn_wa_text'] }}'">
                        <svg style="width:17px;height:17px;flex-shrink:0;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Order On WhatsApp
                    </a>
                    <a href="{{ $phoneNumber ? 'tel:'.preg_replace('/\s+/', '', $phoneNumber) : '#' }}"
                        @if(!$phoneNumber) onclick="return false;" @endif
                        class="btn-call"
                        style="display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:13px 10px; background:{{ $tc['color_btn_call_bg'] }}; color:{{ $tc['color_btn_call_text'] }}; font-size:0.88rem; font-weight:700; text-decoration:none; border-radius:8px; transition:background 0.2s, color 0.2s; {{ !$phoneNumber ? 'opacity:0.5; cursor:not-allowed;' : '' }}"
                        onmouseover="this.style.background='{{ $tc['color_btn_call_hover_bg'] }}';this.style.color='{{ $tc['color_btn_call_hover_text'] }}'"
                        onmouseout="this.style.background='{{ $tc['color_btn_call_bg'] }}';this.style.color='{{ $tc['color_btn_call_text'] }}'">
                        <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 7V5z"/></svg>
                        Call For Order
                    </a>
                </div>
            </div>

            @if($product->warranty_info)
            <div style="border-top:1px solid #e5e7eb; padding-top:16px; margin-top:16px;">
                <p style="font-size:0.875rem; color:#374151;"><strong>Warranty:</strong> {{ $product->warranty_info }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Tabbed Section: Specifications | Details | Q&A | Review -->
    <div id="product-tabs" x-data="{ activeTab: 'specifications' }" style="margin-top:48px;">
        <!-- Tab Headers -->
        <div style="display:flex; border-bottom:1px solid #d1d5db;">
            <button id="tab-specifications" @click="activeTab = 'specifications'"
                :style="'padding:12px 28px; font-size:0.925rem; font-weight:600; border:1px solid; border-bottom:none; cursor:pointer; margin-bottom:-1px; margin-right:-1px;' + (activeTab === 'specifications' ? 'background:#111827; color:#fff; border-color:#111827;' : 'background:transparent; color:#374151; border-color:#d1d5db;')">
                Specifications
            </button>
            <button @click="activeTab = 'details'"
                :style="'padding:12px 28px; font-size:0.925rem; font-weight:600; border:1px solid; border-bottom:none; cursor:pointer; margin-bottom:-1px; margin-right:-1px;' + (activeTab === 'details' ? 'background:#111827; color:#fff; border-color:#111827;' : 'background:transparent; color:#374151; border-color:#d1d5db;')">
                Details
            </button>
            <button @click="activeTab = 'qa'"
                :style="'padding:12px 28px; font-size:0.925rem; font-weight:600; border:1px solid; border-bottom:none; cursor:pointer; margin-bottom:-1px; margin-right:-1px;' + (activeTab === 'qa' ? 'background:#111827; color:#fff; border-color:#111827;' : 'background:transparent; color:#374151; border-color:#d1d5db;')">
                Q&amp;A
            </button>
            <button @click="activeTab = 'review'"
                :style="'padding:12px 28px; font-size:0.925rem; font-weight:600; border:1px solid; border-bottom:none; cursor:pointer; margin-bottom:-1px;' + (activeTab === 'review' ? 'background:#111827; color:#fff; border-color:#111827;' : 'background:transparent; color:#374151; border-color:#d1d5db;')">
                Review
            </button>
        </div>

        <!-- Tab Content -->
        <div style="border:1px solid #d1d5db; border-top:none; background:#fff;">

            <!-- Specifications Tab -->
            <div x-show="activeTab === 'specifications'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @if($product->specifications && count($product->specifications))
                <table style="width:100%; border-collapse:collapse;">
                    <tbody>
                        @php $specIndex = 0; @endphp
                        @foreach($product->specifications as $key => $value)
                        <tr style="border-bottom:1px solid #f3f4f6; {{ $specIndex % 2 === 0 ? 'background:#fff;' : 'background:#f9fafb;' }}">
                            <td style="padding:14px 24px; width:35%; font-weight:600; color:#111827; font-size:0.9rem; vertical-align:top;">{{ $key }}</td>
                            <td style="padding:14px 24px; color:#374151; font-size:0.9rem; vertical-align:top;">{{ $value }}</td>
                        </tr>
                        @php $specIndex++; @endphp
                        @endforeach
                    </tbody>
                </table>
                @else
                <div style="padding:40px; text-align:center; color:#9ca3af;">No specifications available for this product.</div>
                @endif
            </div>

            <!-- Details Tab -->
            <div x-show="activeTab === 'details'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display:none;">
                @if($product->description)
                <div style="padding:24px;" class="prose prose-lg max-w-none">{!! $product->description !!}</div>
                @else
                <div style="padding:40px; text-align:center; color:#9ca3af;">No detailed description available for this product.</div>
                @endif
            </div>

            <!-- Q&A Tab -->
            <div x-show="activeTab === 'qa'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display:none;">
                <div style="padding:40px; text-align:center;">
                    <div style="width:64px; height:64px; background:#f0fdf4; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                        <svg style="width:32px; height:32px; color:#16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 style="font-size:1.1rem; font-weight:600; color:#111827; margin-bottom:6px;">Questions & Answers</h3>
                    <p style="color:#6b7280; font-size:0.9rem;">No questions have been asked about this product yet.</p>
                </div>
            </div>

            <!-- Review Tab -->
            <div x-show="activeTab === 'review'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display:none;">
                <div style="padding:40px; text-align:center;">
                    <div style="width:64px; height:64px; background:#fef3c7; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                        <svg style="width:32px; height:32px; color:#f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <h3 style="font-size:1.1rem; font-weight:600; color:#111827; margin-bottom:6px;">Customer Reviews</h3>
                    <p style="color:#6b7280; font-size:0.9rem;">No reviews yet. Be the first to review this product.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    @if($relatedProducts->count())
    <div style="margin-top:48px; border-top:1px solid #e5e7eb; padding-top:32px;">
        <h2 class="text-2xl font-display mb-6">Related Products</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-start">
            @foreach($relatedProducts as $related)
                @include('components.product-card', ['product' => $related])
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Add to Cart from product detail page
function addToCart(productId, qty) {
    fetch('/cart/add/' + productId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        body: JSON.stringify({ qty: qty }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.count !== undefined && window.updateCartCount) window.updateCartCount(data.count);
        // Show toast if available, else alert briefly
        if (window.showToast) {
            window.showToast(data.message || 'Added to cart!', 'success');
        } else {
            const t = document.createElement('div');
            t.textContent = data.message || 'Added to cart!';
            Object.assign(t.style, { position:'fixed', bottom:'24px', left:'50%', transform:'translateX(-50%)', background:'#111827', color:'#fff', padding:'12px 24px', borderRadius:'8px', fontSize:'0.88rem', fontWeight:'600', zIndex:'9999', boxShadow:'0 4px 20px rgba(0,0,0,0.3)' });
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 2800);
        }
    })
    .catch(() => {});
}

// Buy Now: add to cart (with qty) then redirect to checkout
function buyNow(productId, qty) {
    var btn = document.querySelector('.btn-buy');
    if (btn) { btn.disabled = true; btn.style.opacity = '0.7'; }
    fetch('{{ route("cart.add", "") }}/' + productId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        body: JSON.stringify({ qty: qty }),
    })
    .then(r => r.json())
    .then(() => { window.location.href = '{{ route('checkout.index') }}'; })
    .catch(() => {
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('zoom-container');
    const mainImg = document.getElementById('main-image');
    const result = document.getElementById('zoom-result');

    if (!container || !mainImg || !result) return;

    const ZOOM_FACTOR = 3;
    let zoomImg = new Image();
    let zoomReady = false;
    let isZooming = false;

    function preloadZoom() {
        const src = mainImg.dataset.zoom || mainImg.src;
        zoomReady = false;
        zoomImg = new Image();
        zoomImg.onload = function () { zoomReady = true; };
        zoomImg.src = src;
    }
    preloadZoom();

    // Size the zoom result to match the container height
    function sizeResult() {
        const h = container.offsetHeight;
        result.style.width = h + 'px';
        result.style.height = h + 'px';
    }

    container.addEventListener('mouseenter', function () {
        if (!zoomReady || window.innerWidth < 1024) return;
        isZooming = true;
        sizeResult();
        result.classList.remove('hidden');

        const src = mainImg.dataset.zoom || mainImg.src;
        result.style.backgroundImage = 'url(' + src + ')';
        result.style.backgroundSize = (container.offsetWidth * ZOOM_FACTOR) + 'px ' + (container.offsetHeight * ZOOM_FACTOR) + 'px';
    });

    container.addEventListener('mouseleave', function () {
        isZooming = false;
        result.classList.add('hidden');
    });

    container.addEventListener('mousemove', function (e) {
        if (!isZooming || !zoomReady) return;

        const rect = container.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        // Percentage position of cursor within container
        const percX = x / rect.width;
        const percY = y / rect.height;

        const bgW = rect.width * ZOOM_FACTOR;
        const bgH = rect.height * ZOOM_FACTOR;
        const resW = result.offsetWidth;
        const resH = result.offsetHeight;

        // Center the zoom on cursor position
        const bgX = Math.max(0, Math.min(percX * bgW - resW / 2, bgW - resW));
        const bgY = Math.max(0, Math.min(percY * bgH - resH / 2, bgH - resH));

        result.style.backgroundPosition = -bgX + 'px ' + -bgY + 'px';
    });

    // Thumbnail switching
    document.querySelectorAll('.thumb-item').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            document.querySelectorAll('.thumb-item').forEach(function (t) {
                t.classList.remove('border-primary-500');
                t.classList.add('border-surface-200');
            });
            this.classList.remove('border-surface-200');
            this.classList.add('border-primary-500');

            mainImg.src = this.dataset.large;
            mainImg.dataset.zoom = this.dataset.zoom;
            preloadZoom();
        });
    });
});
</script>
@endpush
