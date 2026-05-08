<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    products: Array
});

const search = ref('');
const cart = ref([]);
const isCartOpen = ref(false);
const showPaymentModal = ref(false);

const paymentForm = useForm({
    items: [],
    total_harga: 0,
    metode_bayar: 'cash',
    jumlah_bayar: 0
});

const totalPrice = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.harga * item.quantity), 0);
});

const kembalian = computed(() => {
    const val = paymentForm.jumlah_bayar - totalPrice.value;
    return val > 0 ? val : 0;
});

const openPaymentModal = () => {
    if (cart.value.length === 0) return;
    paymentForm.items = cart.value;
    paymentForm.total_harga = totalPrice.value;
    paymentForm.jumlah_bayar = 0;
    showPaymentModal.value = true;
};

const processPayment = () => {
    paymentForm.post(route('pos.checkout'), {
        onSuccess: () => {
            showPaymentModal.value = false;
            cart.value = [];
        }
    });
};

const filteredProducts = computed(() => {
    return props.products.filter(p => 
        p.nama_produk.toLowerCase().includes(search.value.toLowerCase()) ||
        p.kode_produk.toLowerCase().includes(search.value.toLowerCase())
    );
});

const addToCart = (product) => {
    const existing = cart.value.find(item => item.id === product.id);
    if (existing) {
        if (existing.quantity < product.stok) {
            existing.quantity++;
        }
    } else {
        cart.value.push({
            ...product,
            quantity: 1
        });
    }
};

const removeFromCart = (id) => {
    const index = cart.value.findIndex(item => item.id === id);
    if (index !== -1) {
        if (cart.value[index].quantity > 1) {
            cart.value[index].quantity--;
        } else {
            cart.value.splice(index, 1);
        }
    }
};


const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
};
</script>

<template>
    <Head title="POS (Kasir)" />

    <AppLayout>
        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-100px)] lg:h-[calc(100vh-120px)] overflow-hidden relative">
            
            <!-- LEFT: PRODUCT SELECTION -->
            <div class="flex-1 flex flex-col min-h-0 space-y-4 lg:space-y-6 pb-20 lg:pb-0">
                <!-- Search -->
                <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md p-3 rounded-sm shadow-sm border border-slate-200 dark:border-white/5">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Cari menu atau scan barcode..." 
                            class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-sm py-2.5 pl-10 pr-4 text-slate-800 dark:text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 transition-all text-sm"
                        />
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 lg:gap-4 pb-4">
                        <div 
                            v-for="product in filteredProducts" 
                            :key="product.id"
                            @click="addToCart(product)"
                            class="group relative bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-sm p-3 shadow-sm hover:shadow-xl hover:scale-[1.02] transition-all cursor-pointer overflow-hidden border border-slate-200 dark:border-white/5 hover:border-blue-500/30"
                        >
                            <div class="aspect-square rounded-sm bg-slate-100 dark:bg-slate-800 mb-2 lg:mb-3 overflow-hidden relative shadow-inner">
                                <img v-if="product.gambar" :src="product.gambar" class="w-full h-full object-cover transition-transform group-hover:scale-110" />
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                    <svg class="w-8 h-8 lg:w-12 lg:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="absolute top-2 right-2 px-2 py-1 bg-white/90 dark:bg-slate-900/90 backdrop-blur text-[9px] lg:text-[10px] font-black rounded-sm shadow-sm">
                                    {{ product.stok }}
                                </div>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white truncate text-sm lg:text-base capitalize">{{ product.nama_produk }}</h4>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-blue-600 dark:text-blue-400 font-black text-xs lg:text-sm">{{ formatPrice(product.harga) }}</span>
                                    <span class="text-[9px] lg:text-[10px] text-slate-400 font-mono uppercase">{{ product.ukuran }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: CART SUMMARY (Desktop) & MODAL (Mobile) -->
            <div 
                :class="[
                    'fixed inset-x-0 bottom-0 z-50 lg:relative lg:inset-auto lg:z-0 lg:w-[400px] flex flex-col bg-white dark:bg-slate-900 backdrop-blur-xl lg:backdrop-blur-md rounded-t-sm lg:rounded-sm shadow-2xl lg:shadow-xl border-t lg:border border-slate-100 dark:border-white/5 transition-transform duration-500 ease-in-out lg:translate-y-0',
                    isCartOpen ? 'translate-y-0 h-[85vh]' : 'translate-y-full lg:h-full'
                ]"
            >
                <!-- Pull Bar (Mobile Only) -->
                <div @click="isCartOpen = false" class="lg:hidden w-full flex justify-center py-4 cursor-pointer">
                    <div class="w-12 h-1.5 bg-slate-200 dark:bg-white/10 rounded-full"></div>
                </div>

                <div class="px-6 py-4 lg:p-6 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg lg:text-xl font-black text-slate-900 dark:text-white">Pesanan</h3>
                        <p class="text-[10px] lg:text-xs text-slate-500 font-medium uppercase tracking-widest">Guest Account</p>
                    </div>
                    <button @click="cart = []" class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar">
                    <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center text-center space-y-4 opacity-50">
                        <div class="w-16 h-16 lg:w-20 lg:h-20 bg-slate-50 dark:bg-white/5 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 lg:w-10 lg:h-10 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <p class="text-slate-400 text-sm font-medium">Belum ada menu dipilih</p>
                    </div>

                    <TransitionGroup 
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="transform translate-x-10 opacity-0"
                        enter-to-class="transform translate-x-0 opacity-100"
                        leave-active-class="transition duration-200 ease-in absolute"
                        leave-from-class="transform translate-x-0 opacity-100"
                        leave-to-class="transform translate-x-10 opacity-0"
                    >
                        <div v-for="item in cart" :key="item.id" class="flex items-center gap-4 bg-slate-50 dark:bg-white/5 p-3 rounded-sm border border-transparent hover:border-blue-500/10 transition-all">
                            <div class="w-12 h-12 rounded-sm bg-white dark:bg-slate-800 overflow-hidden flex-shrink-0 shadow-sm">
                                <img v-if="item.gambar" :src="item.gambar" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h5 class="font-bold text-slate-800 dark:text-white text-xs lg:text-sm truncate capitalize">{{ item.nama_produk }}</h5>
                                <p class="text-[10px] lg:text-xs text-blue-600 dark:text-blue-400 font-bold">{{ formatPrice(item.harga) }}</p>
                            </div>
                            <div class="flex items-center gap-2 bg-white dark:bg-slate-950 rounded-sm p-1 shadow-sm border border-slate-100 dark:border-white/5">
                                <button @click="removeFromCart(item.id)" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-sm transition-all font-bold">-</button>
                                <span class="w-5 text-center text-xs font-black text-slate-900 dark:text-white">{{ item.quantity }}</span>
                                <button @click="addToCart(item)" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-sm transition-all font-bold">+</button>
                            </div>
                        </div>
                    </TransitionGroup>
                </div>

                <!-- Footer Summary -->
                <div class="p-6 bg-slate-50 dark:bg-white/5 border-t border-slate-100 dark:border-white/5 space-y-4">
                    <div class="space-y-2">
                        <div class="flex justify-between text-slate-500 dark:text-slate-400 text-xs">
                            <span>Subtotal</span>
                            <span class="font-bold">{{ formatPrice(totalPrice) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-900 dark:text-white font-black text-lg lg:text-xl pt-2">
                            <span>Total</span>
                            <span class="text-blue-600 dark:text-blue-400">{{ formatPrice(totalPrice) }}</span>
                        </div>
                    </div>
                    <button 
                        @click="openPaymentModal"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-lg shadow-xl shadow-blue-500/20 transition-all active:scale-95 uppercase tracking-widest text-[10px]"
                    >
                        Proses Pembayaran
                    </button>
                </div>
            </div>

            <!-- MOBILE FLOATING SUMMARY BAR -->
            <div 
                v-if="cart.length > 0 && !isCartOpen"
                @click="isCartOpen = true"
                class="lg:hidden fixed bottom-24 inset-x-4 z-[40] bg-blue-600 text-white p-4 rounded-2xl shadow-2xl flex items-center justify-between animate-bounce-subtle cursor-pointer"
            >
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 w-10 h-10 rounded-xl flex items-center justify-center relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 11-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span class="absolute -top-1 -right-1 bg-rose-500 text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center border-2 border-blue-600">{{ cart.length }}</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest">Total Pesanan</p>
                        <p class="font-black text-sm tracking-tight">{{ formatPrice(totalPrice) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 font-black text-[10px] uppercase tracking-widest bg-white/20 px-4 py-2 rounded-xl">
                    Lihat
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                </div>
            </div>

            <!-- VERTICAL PAYMENT MODAL -->
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <div v-if="showPaymentModal" class="fixed inset-0 z-[100] overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 py-8">
                        <!-- Dark overlay without blur -->
                        <div class="fixed inset-0 bg-slate-950/80" @click="showPaymentModal = false"></div>
                        
                        <div class="relative bg-white dark:bg-slate-900 rounded-md shadow-[0_35px_60px_-15px_rgba(0,0,0,0.5)] w-full max-w-lg overflow-hidden transform transition-all border border-slate-200 dark:border-white/10">
                            <div class="p-8">
                                <div class="flex justify-between items-center mb-8">
                                    <div>
                                        <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Pembayaran</h3>
                                        <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">Selesaikan transaksi pesanan.</p>
                                    </div>
                                    <button @click="showPaymentModal = false" class="p-2 bg-slate-100 dark:bg-white/5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-md transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="space-y-6">
                                    <!-- Total Display Card (Deep Look) -->
                                    <div class="bg-slate-900 dark:bg-black rounded-md p-8 shadow-inner shadow-black/50 text-center relative overflow-hidden">
                                        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-blue-600/20 to-transparent pointer-events-none"></div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-2 relative z-10">Total Tagihan</p>
                                        <p class="text-4xl lg:text-5xl font-black text-white tracking-tighter relative z-10">{{ formatPrice(totalPrice) }}</p>
                                    </div>

                                    <!-- Method Select -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <button 
                                            @click="paymentForm.metode_bayar = 'cash'"
                                            :class="[paymentForm.metode_bayar === 'cash' ? 'ring-4 ring-blue-600/20 bg-blue-600 text-white shadow-xl shadow-blue-600/40' : 'bg-slate-50 dark:bg-white/5 text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10', 'flex items-center justify-center gap-3 py-4 rounded-md transition-all font-black uppercase tracking-widest text-[10px]']"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            Tunai
                                        </button>
                                        <button 
                                            @click="paymentForm.metode_bayar = 'qris'"
                                            :class="[paymentForm.metode_bayar === 'qris' ? 'ring-4 ring-blue-600/20 bg-blue-600 text-white shadow-xl shadow-blue-600/40' : 'bg-slate-50 dark:bg-white/5 text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10', 'flex items-center justify-center gap-3 py-4 rounded-md transition-all font-black uppercase tracking-widest text-[10px]']"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.008v.008H6.75V6.75zM6.75 16.5h.008v.008H6.75V16.5zM16.5 6.75h.008v.008H16.5V6.75zM13.5 13.5h.008v.008H13.5V13.5zM16.5 13.5h.008v.008H16.5V13.5zM19.5 13.5h.008v.008H19.5V13.5zM13.5 16.5h.008v.008H13.5V16.5zM16.5 16.5h.008v.008H16.5V16.5zM19.5 16.5h.008v.008H19.5V16.5zM13.5 19.5h.008v.008H13.5V19.5zM16.5 19.5h.008v.008H16.5V19.5zM19.5 19.5h.008v.008H19.5V19.5z" />
                                            </svg>
                                            QRIS
                                        </button>
                                    </div>

                                    <!-- Amount Input -->
                                    <div class="space-y-3">
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Input Pembayaran</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-8 flex items-center pointer-events-none text-slate-400 font-black text-xl transition-colors">Rp</span>
                                            <input 
                                                v-model="paymentForm.jumlah_bayar"
                                                type="number" 
                                                class="w-full bg-slate-50 dark:bg-white/5 border-2 border-transparent focus:border-blue-600/20 rounded-md py-8 pl-16 pr-10 text-3xl font-black text-slate-900 dark:text-white focus:ring-0 transition-all text-right"
                                                placeholder="0"
                                                @focus="$event.target.select()"
                                            />
                                        </div>
                                    </div>

                                    <!-- Quick Amounts -->
                                    <div class="grid grid-cols-3 gap-2">
                                        <button 
                                            @click="paymentForm.jumlah_bayar = totalPrice"
                                            class="px-2 py-4 bg-emerald-500 text-white hover:bg-emerald-600 rounded-md text-[10px] font-black transition-all shadow-md"
                                        >
                                            UANG PAS
                                        </button>
                                        <button 
                                            v-for="cash in [50000, 100000]" 
                                            :key="cash"
                                            @click="paymentForm.jumlah_bayar = cash"
                                            class="px-2 py-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 hover:border-blue-600 hover:text-blue-600 rounded-md text-[10px] font-black transition-all shadow-sm"
                                        >
                                            {{ formatPrice(cash) }}
                                        </button>
                                    </div>

                                    <!-- Final Change & Submit -->
                                    <div class="pt-6 border-t border-slate-100 dark:border-white/5 flex flex-col items-center gap-6">
                                        <div class="text-center">
                                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Kembalian</p>
                                            <p :class="[kembalian > 0 ? 'text-emerald-500' : 'text-slate-400', 'text-3xl font-black tracking-tighter transition-colors']">{{ formatPrice(kembalian) }}</p>
                                        </div>
                                        <button 
                                            @click="processPayment"
                                            :disabled="paymentForm.processing || paymentForm.jumlah_bayar < totalPrice"
                                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-md shadow-[0_20px_50px_rgba(37,99,235,0.3)] hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 uppercase tracking-[0.2em] text-[12px]"
                                        >
                                            {{ paymentForm.processing ? 'Sedang Memproses...' : 'Selesaikan Transaksi' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </AppLayout>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.05);
}
</style>
