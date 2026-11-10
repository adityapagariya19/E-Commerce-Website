@foreach($products as $product)@include('store.partials.product-card', ['product' => $product])@endforeach
