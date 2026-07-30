</main>

<footer class="bg-black text-white border-t border-neutral-800 mt-16 text-xs">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center text-neutral-400 text-[11px] font-mono">
        &copy; 2026 HAPUTTY ORGANICS. All Rights Reserved.
    </div>
</footer>

<!-- Cart Drawer -->
<div id="cartDrawerOverlay" class="fixed inset-0 bg-black/50 z-40 hidden" onclick="toggleCartDrawer()"></div>
<div id="cartDrawer" class="fixed top-0 right-0 w-full max-w-md bg-white h-full shadow-2xl z-50 transform translate-x-full transition-transform duration-300 border-l border-black flex flex-col">
    <div class="p-4 border-b border-neutral-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <h2 class="font-bold text-xs text-black uppercase tracking-wider">YOUR CART</h2>
        </div>
        <button onclick="toggleCartDrawer()" class="p-1.5 text-neutral-400 hover:text-black cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div id="cartItems" class="flex-1 overflow-y-auto p-4 divide-y divide-neutral-100">
        <!-- Cart items loaded via AJAX -->
        <div class="h-full flex flex-col items-center justify-center text-center text-neutral-400 space-y-3">
            <svg class="w-12 h-12 stroke-1 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <p class="text-xs font-semibold uppercase">Your cart is empty</p>
        </div>
    </div>
    <div id="cartFooter" class="p-4 border-t border-neutral-200 space-y-3 bg-neutral-50 hidden">
        <div class="flex justify-between text-xs font-bold text-black">
            <span>Subtotal</span>
            <span id="cartSubtotal" class="font-mono text-sm font-extrabold">Ksh 0.00</span>
        </div>
        <a href="/?page=checkout" class="block w-full bg-black text-white text-xs font-bold uppercase tracking-wider py-3.5 hover:bg-neutral-800 transition-colors text-center no-underline">PROCEED TO CHECKOUT</a>
    </div>
</div>

<script>
function toggleCartDrawer() {
    const drawer = document.getElementById('cartDrawer');
    const overlay = document.getElementById('cartDrawerOverlay');
    const isOpen = drawer.classList.contains('translate-x-0');
    if (isOpen) {
        drawer.classList.remove('translate-x-0');
        drawer.classList.add('translate-x-full');
        overlay.classList.add('hidden');
    } else {
        drawer.classList.remove('translate-x-full');
        drawer.classList.add('translate-x-0');
        overlay.classList.remove('hidden');
        loadCartItems();
    }
}

function loadCartItems() {
    fetch('/ajax/cart.php?action=get')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('cartItems');
            const footer = document.getElementById('cartFooter');
            const subtotalEl = document.getElementById('cartSubtotal');

            if (data.items.length === 0) {
                container.innerHTML = `
                    <div class="h-full flex flex-col items-center justify-center text-center text-neutral-400 space-y-3">
                        <svg class="w-12 h-12 stroke-1 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <p class="text-xs font-semibold uppercase">Your cart is empty</p>
                    </div>
                `;
                footer.classList.add('hidden');
                return;
            }

            let html = '';
            data.items.forEach((item, idx) => {
                html += `
                    <div class="py-4 flex gap-4 text-xs">
                        <img src="${item.image}" alt="" class="w-16 h-16 object-contain bg-[#f5f5f5] border p-1" />
                        <div class="flex-1 space-y-1">
                            <h4 class="font-semibold text-black leading-snug">${item.title}</h4>
                            <p class="text-[11px] text-neutral-500">Color: ${item.color}</p>
                            <div class="font-mono font-bold text-black">Ksh ${item.price.toLocaleString()}.00</div>
                            <div class="flex items-center justify-between pt-1">
                                <div class="flex items-center border border-black">
                                    <button onclick="updateCartQty('${item.cartId}', -1)" class="px-2 py-0.5 hover:bg-neutral-100 cursor-pointer">-</button>
                                    <span class="px-2 font-mono font-bold">${item.quantity}</span>
                                    <button onclick="updateCartQty('${item.cartId}', 1)" class="px-2 py-0.5 hover:bg-neutral-100 cursor-pointer">+</button>
                                </div>
                                <button onclick="removeCartItem('${item.cartId}')" class="text-red-600 hover:text-black text-[11px] font-semibold cursor-pointer">Remove</button>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
            subtotalEl.textContent = 'Ksh ' + data.subtotal.toLocaleString() + '.00';
            footer.classList.remove('hidden');
        });
}

function updateCartQty(cartId, delta) {
    fetch('/ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update&cart_id=' + cartId + '&delta=' + delta
    }).then(r => r.json()).then(data => {
        if (data.success) loadCartItems();
    });
}

function removeCartItem(cartId) {
    fetch('/ajax/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=remove&cart_id=' + cartId
    }).then(r => r.json()).then(data => {
        if (data.success) loadCartItems();
    });
}
</script>

</body>
</html>
