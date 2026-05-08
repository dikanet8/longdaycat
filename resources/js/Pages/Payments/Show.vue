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

            <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-3xl border border-slate-200 dark:border-white/5 overflow-hidden">
                <!-- Receipt Header -->
                <div class="p-8 border-b-2 border-dashed border-slate-100 dark:border-white/5 text-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/30">
                        <span class="text-white font-black italic text-2xl">L</span>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Longdaycat.co</h2>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Struk Pembayaran</p>
                </div>

                <!-- Transaction Info -->
                <div class="p-8 space-y-6">
                    <div class="grid grid-cols-2 gap-8 text-sm">
                        <div class="space-y-4">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Kode Transaksi</p>
                                <p class="font-black text-slate-900 dark:text-white text-base">{{ transaction.kode_transaksi }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Metode Bayar</p>
                                <span class="px-2 py-1 bg-slate-100 dark:bg-white/5 text-slate-900 dark:text-white rounded text-[10px] font-black uppercase">{{ transaction.metode_bayar }}</span>
                            </div>
                        </div>
                        <div class="space-y-4 text-right">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tanggal</p>
                                <p class="font-bold text-slate-900 dark:text-white text-sm">{{ formatDate(transaction.tanggal) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Status</p>
                                <p class="font-black text-emerald-500 uppercase text-xs">{{ transaction.status }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Kasir</p>
                                <p class="font-bold text-slate-900 dark:text-white text-xs lowercase">{{ transaction.user?.name || 'Sistem' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="mt-8">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Rincian Pesanan</p>
                        <div class="space-y-4">
                            <div v-for="item in transaction.details" :key="item.id" class="flex justify-between items-center">
                                <div class="flex-1">
                                    <p class="font-bold text-slate-800 dark:text-white text-sm capitalize">{{ item.produk?.nama_produk }}</p>
                                    <p class="text-xs text-slate-400 font-medium">{{ item.jumlah }} x {{ formatPrice(item.harga_satuan) }}</p>
                                </div>
                                <p class="font-black text-slate-900 dark:text-white text-sm">{{ formatPrice(item.subtotal) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="pt-8 border-t border-slate-100 dark:border-white/5 space-y-3">
                        <div class="flex justify-between text-sm">
                            <p class="text-slate-500 font-bold uppercase text-[10px] tracking-widest">Subtotal</p>
                            <p class="font-bold text-slate-900 dark:text-white">{{ formatPrice(transaction.total_harga) }}</p>
                        </div>
                        <div class="flex justify-between text-sm">
                            <p class="text-slate-500 font-bold uppercase text-[10px] tracking-widest">Pajak (0%)</p>
                            <p class="font-bold text-slate-900 dark:text-white">Rp 0</p>
                        </div>
                        <div class="flex justify-between items-center pt-4">
                            <p class="text-slate-900 dark:text-white font-black uppercase text-xs tracking-widest">Total Bayar</p>
                            <p class="text-3xl font-black text-blue-600 dark:text-blue-400 tracking-tighter">{{ formatPrice(transaction.total_harga) }}</p>
                        </div>
                    </div>

                    <!-- Payment Details (if any) -->
                    <div v-if="payment" class="mt-8 p-4 bg-slate-50 dark:bg-white/5 rounded-2xl grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Jumlah Bayar</p>
                            <p class="font-bold text-slate-900 dark:text-white text-sm">{{ formatPrice(payment.jumlah_bayar) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Kembalian</p>
                            <p class="font-bold text-emerald-500 text-sm">{{ formatPrice(payment.jumlah_bayar - transaction.total_harga) }}</p>
                        </div>
                    </div>

                    <!-- Receipt Footer -->
                    <div class="pt-8 text-center space-y-6">
                        <p class="text-slate-400 text-xs italic">"Terima kasih telah berkunjung ke Longdaycat.co. Semoga hari Anda menyenangkan!"</p>
                        
                        <div class="flex flex-col sm:flex-row justify-center gap-4">
                            <button @click="printReceipt" class="flex-1 sm:flex-none px-8 py-4 bg-slate-900 dark:bg-white/10 text-white font-black text-[10px] uppercase tracking-widest rounded-2xl hover:scale-105 transition-all flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Cetak Struk
                            </button>
                            
                            <Link href="/pos" class="flex-1 sm:flex-none px-8 py-4 bg-emerald-500 text-white font-black text-[10px] uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-500/20 hover:scale-105 transition-all flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Transaksi Baru
                            </Link>
                        </div>
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

            <div class="flex justify-between mb-1">
                <span>ID: {{ transaction.kode_transaksi }}</span>
                <span>{{ formatDate(transaction.tanggal) }}</span>
            </div>
            <div class="mb-1">
                <span>KASIR: {{ transaction.user?.name || 'Sistem' }}</span>
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
