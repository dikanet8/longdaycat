<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onUnmounted, nextTick } from 'vue';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from "html5-qrcode";

const props = defineProps({
    products: Array,
    categories: Array
});

const activeCategoryId = ref('all');
const showScannerModal = ref(false);
const barcodeInput = ref('');
const scannerInput = ref(null);
const html5QrCode = ref(null);
const isScanning = ref(false);
const scanError = ref('');
const lastScanned = ref('');
const showBlink = ref(false);
const isProcessing = ref(false);

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

const handleBarcodeScan = (decodedText) => {
    if (isProcessing.value) return;
    
    const code = (typeof decodedText === 'string' ? decodedText : barcodeInput.value).trim();
    if (!code) return;

    isProcessing.value = true;
    lastScanned.value = code;
    console.log("Barcode detected:", code);
    
    // Always show blink so user knows scanner "responded"
    showBlink.value = true;
    setTimeout(() => showBlink.value = false, 300);

    const product = props.products.find(p => p.kode_produk === code);
    
    if (product) {
        addToCart(product);
        barcodeInput.value = '';
        
        // Close scanner after success
        setTimeout(() => {
            closeScanner();
            isProcessing.value = false;
        }, 500);
    } else {
        console.warn("Product not found for code:", code);
        // Reset lock after a short delay
        setTimeout(() => {
            isProcessing.value = false;
        }, 1500);
    }
};

const isSecureContext = computed(() => {
    return window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
});

const startCamera = async () => {
    try {
        if (!isSecureContext.value) {
            scanError.value = "Akses kamera memerlukan HTTPS (kecuali localhost).";
            return;
        }

        isScanning.value = true;
        scanError.value = '';
        
        await nextTick();

        console.log("Starting scanner on HTTPS context...");
        
        // 2. Start QR Scanner
        const readerElement = document.getElementById("reader");
        if (!readerElement) {
            scanError.value = "Elemen 'reader' tidak ditemukan.";
            isScanning.value = false;
            return;
        }

        if (html5QrCode.value) {
            await stopCamera();
        }

        html5QrCode.value = new Html5Qrcode("reader");
        const config = { 
            fps: 20,
            aspectRatio: 1.0
        };
        
        // Define formats to support
        const formatsToSupport = [
            Html5QrcodeSupportedFormats.QR_CODE,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
            Html5QrcodeSupportedFormats.ITF,
        ];
        
        await html5QrCode.value.start(
            { facingMode: "environment" }, 
            { ...config, formatsToSupport }, 
            (decodedText) => {
                handleBarcodeScan(decodedText);
            },
            (errorMessage) => {
                // scanning...
            }
        );
    } catch (err) {
        console.error("Scanner error:", err);
        scanError.value = "Gagal memuat scanner: " + (err.message || err);
        isScanning.value = false;
    }
};

const stopCamera = async () => {
    if (html5QrCode.value && html5QrCode.value.isScanning) {
        await html5QrCode.value.stop();
        html5QrCode.value = null;
    }
    isScanning.value = false;
};

const openScanner = () => {
    isProcessing.value = false; // Reset lock when opening
    showScannerModal.value = true;
    setTimeout(() => {
        if (scannerInput.value) scannerInput.value.focus();
    }, 100);
};

const closeScanner = async () => {
    await stopCamera();
    showScannerModal.value = false;
};

onUnmounted(() => {
    stopCamera();
});

const filteredProducts = computed(() => {
    return props.products.filter(p => {
        const matchesSearch = p.nama_produk.toLowerCase().includes(search.value.toLowerCase()) ||
                            p.kode_produk.toLowerCase().includes(search.value.toLowerCase());
        const matchesCategory = activeCategoryId.value === 'all' || p.kategori_id === activeCategoryId.value;
        return matchesSearch && matchesCategory;
    });
});

const addToCart = (product) => {
    if (product.stok <= 0) return;
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
        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100dvh-100px)] lg:h-[calc(100dvh-120px)] overflow-hidden relative">
            
            <!-- LEFT: PRODUCT SELECTION -->
            <div class="flex-1 flex flex-col min-h-0 space-y-4 lg:space-y-6 pb-20 lg:pb-0">
                <!-- Search & Actions -->
                <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md p-3 rounded-sm shadow-sm border border-slate-200 dark:border-white/5 flex gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Cari menu..." 
                            class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-sm py-2.5 pl-10 pr-4 text-slate-800 dark:text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 transition-all text-sm"
                        />
                    </div>
                    <button 
                        @click="openScanner"
                        class="bg-blue-600 hover:bg-blue-700 text-white p-2.5 rounded-sm shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2"
                        title="Scan Barcode"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        <span class="hidden sm:inline font-bold text-xs uppercase">Scan</span>
                    </button>
                </div>

                <!-- Categories -->
                <div class="flex gap-2 overflow-x-auto pb-2 custom-scrollbar-h no-scrollbar">
                    <button 
                        @click="activeCategoryId = 'all'"
                        :class="[
                            'px-4 py-2 rounded-sm text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap',
                            activeCategoryId === 'all' 
                                ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' 
                                : 'bg-white dark:bg-slate-900/40 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-white/5 hover:border-blue-500/30'
                        ]"
                    >
                        Semua
                    </button>
                    <button 
                        v-for="cat in categories" 
                        :key="cat.id"
                        @click="activeCategoryId = cat.id"
                        :class="[
                            'px-4 py-2 rounded-sm text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap',
                            activeCategoryId === cat.id 
                                ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' 
                                : 'bg-white dark:bg-slate-900/40 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-white/5 hover:border-blue-500/30'
                        ]"
                    >
                        {{ cat.nama_kategori }}
                    </button>
                </div>

                <!-- Product Grid -->
                <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 lg:gap-4 pb-4">
                        <div 
                            v-for="product in filteredProducts" 
                            :key="product.id"
                            @click="addToCart(product)"
                            :class="[
                                'group relative bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-sm p-3 shadow-sm transition-all overflow-hidden border border-slate-200 dark:border-white/5',
                                product.stok > 0 ? 'hover:shadow-xl hover:scale-[1.02] cursor-pointer hover:border-blue-500/30' : 'opacity-60 grayscale cursor-not-allowed'
                            ]"
                        >
                            <div class="aspect-square rounded-sm bg-slate-100 dark:bg-slate-800 mb-2 lg:mb-3 overflow-hidden relative shadow-inner">
                                <img v-if="product.gambar" :src="product.gambar" class="w-full h-full object-cover transition-transform group-hover:scale-110" />
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                    <svg class="w-8 h-8 lg:w-12 lg:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                
                                <!-- Stock Badge -->
                                <div :class="[
                                    'absolute top-2 right-2 px-2 py-1 backdrop-blur text-[9px] lg:text-[10px] font-black rounded-sm shadow-sm z-10',
                                    product.stok > 0 ? 'bg-white/90 dark:bg-slate-900/90 text-slate-900 dark:text-white' : 'bg-red-500 text-white'
                                ]">
                                    {{ product.stok > 0 ? product.stok : 'HABIS' }}
                                </div>

                                <!-- Sold Out Overlay -->
                                <div v-if="product.stok <= 0" class="absolute inset-0 bg-slate-900/40 flex items-center justify-center">
                                    <span class="bg-red-600 text-white text-[10px] font-black px-3 py-1 rounded-sm rotate-12 shadow-lg">STOK HABIS</span>
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
                    'fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] lg:bottom-0 z-50 lg:relative lg:inset-auto lg:z-0 lg:w-[400px] flex flex-col bg-white dark:bg-slate-900 backdrop-blur-xl lg:backdrop-blur-md rounded-t-sm lg:rounded-sm shadow-2xl lg:shadow-xl border-t lg:border border-slate-100 dark:border-white/5 transition-transform duration-500 ease-in-out lg:translate-y-0',
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
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-lg shadow-xl shadow-blue-500/20 transition-all active:scale-95 uppercase tracking-widest text-[11px]"
                    >
                        Proses Pembayaran
                    </button>
                </div>
            </div>

            <!-- MOBILE FLOATING SUMMARY BAR -->
            <div 
                v-if="cart.length > 0 && !isCartOpen"
                @click="isCartOpen = true"
                class="lg:hidden fixed bottom-[calc(5.5rem+env(safe-area-inset-bottom))] inset-x-4 z-[40] bg-blue-600 text-white p-4 rounded-2xl shadow-2xl flex items-center justify-between animate-bounce-subtle cursor-pointer"
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
            <Teleport to="body">
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-4"
                >
                    <div v-if="showPaymentModal" class="fixed inset-0 z-[9999] overflow-y-auto">
                        <div class="flex items-center justify-center min-h-screen p-4 pb-32 sm:p-6 text-center">
                            <div class="fixed inset-0 bg-slate-900/80 transition-opacity" @click="showPaymentModal = false"></div>
                            
                            <div class="relative bg-white dark:bg-slate-900 rounded-md shadow-2xl w-full max-w-lg overflow-hidden transform transition-all border border-slate-200 dark:border-white/10 text-left">
                                <div class="p-8">
                                    <div class="flex justify-between items-center mb-8">
                                        <div>
                                            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Pembayaran</h3>
                                            <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mt-1">Selesaikan transaksi pesanan</p>
                                        </div>
                                        <button @click="showPaymentModal = false" class="p-2 bg-slate-100 dark:bg-white/5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-md transition-all">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="space-y-6">
                                        <!-- Simple Total -->
                                        <div class="p-6 bg-slate-50 dark:bg-white/5 rounded-md text-center border border-slate-100 dark:border-white/5">
                                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Tagihan</p>
                                            <p class="text-4xl font-black text-slate-900 dark:text-white">{{ formatPrice(totalPrice) }}</p>
                                        </div>

                                        <!-- Method Select -->
                                        <div class="grid grid-cols-2 gap-4">
                                            <button 
                                                @click="paymentForm.metode_bayar = 'cash'"
                                                :class="[paymentForm.metode_bayar === 'cash' ? 'bg-blue-600 text-white shadow-lg' : 'bg-slate-50 dark:bg-white/5 text-slate-400', 'flex items-center justify-center gap-2 py-4 rounded-md transition-all font-black uppercase tracking-widest text-[10px] active:scale-95 border border-transparent']"
                                            >
                                                Tunai
                                            </button>
                                            <button 
                                                @click="paymentForm.metode_bayar = 'qris'"
                                                :class="[paymentForm.metode_bayar === 'qris' ? 'bg-blue-600 text-white shadow-lg' : 'bg-slate-50 dark:bg-white/5 text-slate-400', 'flex items-center justify-center gap-2 py-4 rounded-md transition-all font-black uppercase tracking-widest text-[10px] active:scale-95 border border-transparent']"
                                            >
                                                QRIS
                                            </button>
                                        </div>

                                        <!-- Amount Input -->
                                        <div class="space-y-2">
                                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Jumlah Bayar</label>
                                            <div class="relative">
                                                <input 
                                                    v-model="paymentForm.jumlah_bayar"
                                                    type="number" 
                                                    class="w-full bg-slate-50 dark:bg-slate-900/50 border-2 border-slate-200 dark:border-white/5 focus:border-blue-600 rounded-md py-4 px-6 text-2xl font-black text-slate-900 dark:text-white focus:ring-0 transition-all text-right"
                                                    placeholder="0"
                                                    @focus="$event.target.select()"
                                                />
                                            </div>
                                        </div>

                                        <!-- Quick Amounts -->
                                        <div class="grid grid-cols-3 gap-3">
                                            <button 
                                                @click="paymentForm.jumlah_bayar = totalPrice"
                                                class="px-2 py-3 bg-emerald-500 text-white hover:bg-emerald-600 rounded-md text-[10px] font-black transition-all active:scale-95"
                                            >
                                                UANG PAS
                                            </button>
                                            <button 
                                                v-for="cash in [50000, 100000]" 
                                                :key="cash"
                                                @click="paymentForm.jumlah_bayar = cash"
                                                class="px-2 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 hover:border-blue-600 hover:text-blue-600 rounded-md text-[10px] font-black transition-all active:scale-95"
                                            >
                                                {{ formatPrice(cash) }}
                                            </button>
                                        </div>

                                        <!-- Final Change & Submit -->
                                        <div class="pt-6 border-t border-slate-100 dark:border-white/5 space-y-6">
                                            <div class="flex justify-between items-center px-2">
                                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Kembalian</p>
                                                <p :class="[kembalian > 0 ? 'text-emerald-500' : 'text-slate-400', 'text-2xl font-black transition-colors']">{{ formatPrice(kembalian) }}</p>
                                            </div>
                                            <button 
                                                @click="processPayment"
                                                :disabled="paymentForm.processing || paymentForm.jumlah_bayar < totalPrice"
                                                class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-md shadow-lg transition-all disabled:opacity-50 uppercase tracking-widest text-[11px]"
                                            >
                                                {{ paymentForm.processing ? 'Memproses...' : 'Selesaikan Pembayaran' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <Teleport to="body">
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="showScannerModal" class="fixed inset-0 z-[9999] overflow-y-auto">
                        <div class="flex items-center justify-center min-h-screen p-4 pb-32 sm:p-6 text-center">
                            <div class="fixed inset-0 bg-slate-900/80 transition-opacity" @click="closeScanner"></div>
                            <div class="relative bg-white dark:bg-slate-900 rounded-md shadow-2xl p-8 w-full max-w-md border border-slate-200 dark:border-white/10 text-center transform transition-all text-left">
                            <div class="mb-6">
                                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-1">Scan Barcode</h3>
                                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest">Arahkan kamera ke barcode produk</p>
                            </div>
                            
                            <!-- Camera Preview -->
                            <div class="relative aspect-square bg-black rounded-md overflow-hidden mb-8 border-4 border-slate-100 dark:border-white/5 shadow-inner">
                                <div id="reader" class="w-full h-full object-cover"></div>
                                
                                <!-- Scan Line Animation -->
                                <div v-if="isScanning" class="absolute top-0 left-0 w-full h-1 bg-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.8)] animate-scan z-20"></div>

                                <!-- Overlay when not scanning -->
                                <div v-if="!isScanning" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-sm p-6">
                                    <div class="w-20 h-20 bg-blue-600 rounded-md flex items-center justify-center mb-6 text-white shadow-xl">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812-1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <button 
                                        @click="startCamera"
                                        class="bg-white text-slate-900 font-black py-4 px-8 rounded-md shadow-xl transition-all active:scale-95 text-[10px] uppercase tracking-widest"
                                    >
                                        Mulai Scan
                                    </button>
                                    <p v-if="scanError" class="mt-6 text-red-400 text-[10px] font-black uppercase">{{ scanError }}</p>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="relative">
                                    <input 
                                        ref="scannerInput"
                                        v-model="barcodeInput"
                                        @keyup.enter="handleBarcodeScan"
                                        type="text"
                                        class="w-full bg-slate-50 dark:bg-slate-900/50 border-2 border-transparent focus:border-blue-600/20 rounded-md py-4 px-6 text-center text-sm font-black tracking-[0.3em] focus:ring-0 transition-all uppercase"
                                        placeholder="KODE MANUAL"
                                    />
                                </div>
                                
                                <button @click="closeScanner" class="w-full text-slate-400 hover:text-red-500 font-black uppercase tracking-widest text-[10px] py-2 transition-colors">
                                    Tutup Scanner
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
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
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
