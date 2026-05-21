<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    transaction: Object,
    payment: Object
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const printReceipt = () => {
    window.print();
};
</script>

<template>
    <Head :title="'Detail ' + transaction.kode_transaksi" />

    <AppLayout>
        <div class="space-y-8 no-print">
            <div class="flex items-center gap-4">
                <Link href="/payments" class="p-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-white/5 hover:bg-slate-50 transition-all text-slate-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Detail Transaksi</h1>
            </div>

            <!-- Alert Sukses (di atas detail transaksi) -->
            <div v-if="$page.props.flash.success || $page.props.flash.message" class="max-w-md mx-auto p-4 bg-emerald-50 dark:bg-emerald-950/10 border border-emerald-200 dark:border-emerald-900/30 border-l-4 border-l-emerald-500 rounded-xl text-emerald-800 dark:text-emerald-300 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[9px] font-black uppercase tracking-widest opacity-60">Sukses</p>
                    <p class="text-xs font-bold leading-tight">{{ $page.props.flash.success || $page.props.flash.message }}</p>
                </div>
            </div>

            <div class="relative max-w-md mx-auto mt-6">
                <!-- Left/Right Ticket Cutouts -->
                <div class="absolute -left-4 top-[140px] w-8 h-8 bg-slate-50 dark:bg-slate-900 rounded-full shadow-inner z-10 hidden sm:block"></div>
                <div class="absolute -right-4 top-[140px] w-8 h-8 bg-slate-50 dark:bg-slate-900 rounded-full shadow-inner z-10 hidden sm:block"></div>
                
                <!-- Receipt Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden relative border border-slate-100 dark:border-slate-700">
                    
                    <!-- Decorative Top Edge -->
                    <div class="h-2 w-full bg-gradient-to-r from-blue-500 to-purple-500"></div>
                    
                    <!-- Header -->
                    <div class="p-6 sm:p-8 pb-6 text-center bg-slate-50/50 dark:bg-slate-800/30">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/30">
                            <span class="text-white font-black italic text-2xl">L</span>
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Longdaycat.co</h2>
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.3em] mt-2">Struk Pembayaran</p>
                    </div>

                    <!-- Dashed Divider with Cutouts -->
                    <div class="relative h-0.5 flex items-center justify-between px-2 bg-slate-50/50 dark:bg-slate-800/30">
                        <div class="w-full border-t-2 border-dashed border-slate-200 dark:border-slate-600"></div>
                    </div>

                    <!-- Meta Info -->
                    <div class="px-6 sm:px-8 py-5 sm:py-6 bg-slate-50/50 dark:bg-slate-800/30">
                        <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                            <div class="overflow-hidden">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Kode Transaksi</p>
                                <p class="font-bold text-slate-900 dark:text-white text-xs font-mono truncate tracking-tight" :title="transaction.kode_transaksi">{{ transaction.kode_transaksi }}</p>
                            </div>
                            <div class="text-right overflow-hidden">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Tanggal</p>
                                <p class="font-bold text-slate-900 dark:text-white text-xs truncate">{{ formatDate(transaction.tanggal) }}</p>
                            </div>
                            <div class="overflow-hidden">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Metode Bayar</p>
                                <span class="px-2 py-1 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-[10px] font-black uppercase tracking-wider inline-block shadow-sm border border-slate-100 dark:border-slate-600 truncate max-w-full">{{ transaction.metode_bayar }}</span>
                            </div>
                            <div class="text-right overflow-hidden">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Kasir</p>
                                <p class="font-bold text-slate-900 dark:text-white text-xs capitalize truncate">{{ transaction.user?.name || 'Sistem' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="p-6 sm:p-8 bg-white dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700">
                        <div class="space-y-4">
                            <div v-for="item in transaction.details" :key="item.id" class="flex justify-between items-start group">
                                <div class="flex-1 pr-4">
                                    <p class="font-bold text-slate-800 dark:text-white text-sm capitalize group-hover:text-blue-600 transition-colors">{{ item.produk?.nama_produk }}</p>
                                    <p class="text-xs text-slate-500 mt-1 font-mono">{{ item.jumlah }} x {{ formatPrice(item.harga_satuan) }}</p>
                                </div>
                                <p class="font-black text-slate-900 dark:text-white text-sm font-mono mt-0.5">{{ formatPrice(item.subtotal) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Subtotals -->
                    <div class="px-6 sm:px-8 py-5 sm:py-6 bg-slate-50/80 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-700 space-y-3">
                        <div class="flex justify-between text-xs">
                            <p class="text-slate-500 font-bold uppercase tracking-widest">Subtotal</p>
                            <p class="font-bold text-slate-900 dark:text-white font-mono">{{ formatPrice(transaction.total_harga) }}</p>
                        </div>
                        <div class="flex justify-between text-xs">
                            <p class="text-slate-500 font-bold uppercase tracking-widest">Pajak (0%)</p>
                            <p class="font-bold text-slate-900 dark:text-white font-mono">Rp 0</p>
                        </div>
                        
                        <div class="pt-4 mt-4 border-t-2 border-dashed border-slate-200 dark:border-slate-600 flex justify-between items-end">
                            <p class="text-slate-900 dark:text-white font-black uppercase text-sm tracking-widest">Total Bayar</p>
                            <p class="text-2xl font-black text-blue-600 dark:text-blue-400 tracking-tighter">{{ formatPrice(transaction.total_harga) }}</p>
                        </div>
                    </div>

                    <!-- Payment & Change block (if any) -->
                    <div v-if="payment" class="px-6 sm:px-8 py-5 sm:py-6 bg-slate-900 dark:bg-slate-900 text-white flex justify-between items-center border-t border-slate-800">
                        <div>
                            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">Uang Tunai</p>
                            <p class="font-bold text-lg tracking-tight font-mono">{{ formatPrice(payment.jumlah_bayar) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-emerald-400 text-[10px] font-black uppercase tracking-widest mb-1">Kembalian</p>
                            <p class="font-black text-xl tracking-tight text-emerald-500 font-mono">{{ formatPrice(payment.jumlah_bayar - transaction.total_harga) }}</p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 bg-white dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700 flex gap-2 sm:gap-3">
                        <button @click="printReceipt" class="flex-1 px-3 sm:px-4 py-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-white font-black text-[10px] sm:text-xs uppercase tracking-widest rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Cetak
                        </button>
                        <Link href="/pos" class="flex-1 px-3 sm:px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-black text-[10px] sm:text-xs uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            POS Baru
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRINT VERSION (Thermal Layout) -->
        <div class="print-receipt hidden text-slate-900 font-mono text-[12px] leading-tight">
            <div class="text-center mb-4">
                <h2 class="text-lg font-bold">LONGDAYCAT.CO</h2>
                <p>Jln. Raya Utama No. 123</p>
                <p>0812-3456-7890</p>
                <div class="border-b border-dashed border-black my-2"></div>
                <p class="text-sm font-bold">STRUK PEMBAYARAN</p>
                <div class="border-b border-dashed border-black my-2"></div>
            </div>

            <div class="flex flex-col mb-1 gap-0.5">
                <div class="flex justify-between">
                    <span>ID TRX:</span>
                    <span class="text-right">{{ transaction.kode_transaksi }}</span>
                </div>
                <div class="flex justify-between">
                    <span>TGL:</span>
                    <span class="text-right">{{ formatDate(transaction.tanggal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>KASIR:</span>
                    <span class="text-right uppercase">{{ transaction.user?.name || 'Sistem' }}</span>
                </div>
            </div>
            <div class="border-b border-dashed border-black my-2"></div>

            <div v-for="item in transaction.details" :key="'print-'+item.id" class="mb-2">
                <div class="flex justify-between font-bold">
                    <span>{{ item.produk?.nama_produk }}</span>
                    <span>{{ formatPrice(item.subtotal) }}</span>
                </div>
                <div class="text-xs">
                    {{ item.jumlah }} x {{ formatPrice(item.harga_satuan) }}
                </div>
            </div>

            <div class="border-b border-dashed border-black my-2"></div>
            
            <div class="space-y-1">
                <div class="flex justify-between font-bold">
                    <span>SUBTOTAL</span>
                    <span>{{ formatPrice(transaction.total_harga) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>PAJAK (0%)</span>
                    <span>Rp 0</span>
                </div>
                <div class="border-b border-dashed border-black my-2"></div>
                <div class="flex justify-between text-lg font-bold">
                    <span>TOTAL</span>
                    <span>{{ formatPrice(transaction.total_harga) }}</span>
                </div>
                
                <div v-if="payment" class="pt-2">
                    <div class="flex justify-between">
                        <span>BAYAR ({{ transaction.metode_bayar }})</span>
                        <span>{{ formatPrice(payment.jumlah_bayar) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>KEMBALI</span>
                        <span>{{ formatPrice(payment.jumlah_bayar - transaction.total_harga) }}</span>
                    </div>
                </div>
            </div>

            <div class="border-b border-dashed border-black my-2 pt-4"></div>
            <div class="text-center mt-4">
                <p>TERIMA KASIH</p>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    /* Hide everything except the print version */
    .no-print, header, aside, nav, .bg-blue-500\/5 {
        display: none !important;
    }

    body {
        background-color: white !important;
        margin: 0;
        padding: 0;
    }

    .print-receipt {
        display: block !important;
        width: 80mm; /* Standard thermal printer width */
        margin: 0 auto;
        padding: 5mm;
        color: black !important;
        background: white !important;
    }

    @page {
        size: 80mm auto;
        margin: 0;
    }

    /* Force black text for all thermal prints */
    .print-receipt * {
        color: black !important;
        border-color: black !important;
    }
}
</style>
