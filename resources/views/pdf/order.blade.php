<h1>Commande #{{ $order->order_number }}</h1>

<p>Client : {{ $order->customer_name }}</p>
<p>Email : {{ $order->customer_email }}</p>

<hr>

<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Produit</th>
            <th>Quantité</th>
            <th>Prix</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->price }} FCFA</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Total : {{ $order->total_amount }} FCFA</h3>
