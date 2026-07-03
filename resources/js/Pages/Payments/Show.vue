<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, Link } from'@inertiajs/vue3';

import { onMounted } from 'vue';

const props = defineProps({
  transaction: Object,
  payment: Object,
  setting: Object
});

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits: 0 }).format(price);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day:'numeric',
    month:'long',
    year:'numeric',
    hour:'2-digit',
    minute:'2-digit'
  });
};

const printReceipt = () => {
  window.print();
};

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('print') === 'true') {
    setTimeout(() => {
      printReceipt();
    }, 300);
  }
});
</script>

<template>
  <Head :title="'Detail ' + transaction.kode_transaksi" />

  <AppLayout>
    <div class="no-print">
      <div class="relative max-w-md mx-auto mt-2 lg:-mt-4">
        <!-- Left/Right Ticket Cutouts -->
        <div class="absolute -left-4 top-[140px] w-8 h-8 bg-slate-50 dark:bg-slate-900 rounded-full shadow-inner z-10 hidden sm:block"></div>
        <div class="absolute -right-4 top-[140px] w-8 h-8 bg-slate-50 dark:bg-slate-900 rounded-full shadow-inner z-10 hidden sm:block"></div>
        
        <!-- Receipt Card -->
        <div class="bg-white dark:bg-slate-800 rounded-md shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden relative border border-slate-100 dark:border-slate-700">
          
          <!-- Decorative Top Edge -->
          <div class="h-2 w-full bg-gradient-to-r from-blue-500 to-purple-500"></div>
          
          <!-- Header -->
          <div class="p-6 sm:p-8 pb-6 text-center bg-slate-50/50 dark:bg-slate-800/30">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-md flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/30 overflow-hidden">
              <img v-if="setting?.logo_toko" :src="setting.logo_toko" class="w-full h-full object-cover bg-white" />
              <span v-else class="text-white font-black italic text-2xl">{{ setting?.nama_toko ? setting.nama_toko.charAt(0).toUpperCase() : 'L' }}</span>
            </div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">{{ setting?.nama_toko || 'Longdaycat.co' }}</h2>
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
                <p class="font-bold text-slate-900 dark:text-white text-xs truncate">{{ formatDate(transaction.created_at) }}</p>
              </div>
              <div class="overflow-hidden">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Metode Bayar</p>
                <span class="px-2 py-1 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded text-[10px] font-black uppercase tracking-wider inline-block shadow-sm border border-slate-100 dark:border-slate-600 truncate max-w-full">{{ transaction.metode_bayar }}</span>
              </div>
              <div class="text-right overflow-hidden">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Kasir</p>
                <p class="font-bold text-slate-900 dark:text-white text-xs capitalize truncate">{{ transaction.user?.name ||'Sistem' }}</p>
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
              <p class="font-bold text-slate-900 dark:text-white font-mono">{{ formatPrice(transaction.subtotal ?? transaction.total_harga) }}</p>
            </div>
            <div v-if="transaction.diskon > 0" class="flex justify-between text-xs">
              <p class="text-slate-500 font-bold uppercase tracking-widest">Diskon</p>
              <p class="font-bold text-red-500 font-mono">-{{ formatPrice(transaction.diskon) }}</p>
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
            <button @click="printReceipt" class="flex-1 px-3 sm:px-4 py-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-white font-black text-[10px] sm:text-xs uppercase tracking-widest rounded-md transition-all flex items-center justify-center gap-2 shadow-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              Cetak
            </button>

          </div>
        </div>
      </div>
    </div>

    <!-- PRINT VERSION (Thermal Layout) -->
    <Teleport to="body">
      <div class="print-receipt hidden text-slate-900 font-mono text-[12px] leading-tight">
        <div class="text-center mb-4">
        <h2 class="text-lg font-bold uppercase">{{ setting?.nama_toko || 'LONGDAYCAT.CO' }}</h2>
        <p class="whitespace-pre-line">{{ setting?.alamat_toko || 'Jln. Raya Utama No. 123' }}</p>
        <p>{{ setting?.telepon_toko || '0812-3456-7890' }}</p>
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
          <span class="text-right">{{ formatDate(transaction.created_at) }}</span>
        </div>
        <div class="flex justify-between">
          <span>KASIR:</span>
          <span class="text-right uppercase">{{ transaction.user?.name ||'Sistem' }}</span>
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
          <span>{{ formatPrice(transaction.subtotal ?? transaction.total_harga) }}</span>
        </div>
        <div v-if="transaction.diskon > 0" class="flex justify-between font-bold">
          <span>DISKON</span>
          <span>-{{ formatPrice(transaction.diskon) }}</span>
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
          <p class="whitespace-pre-line">{{ setting?.deskripsi_struk || 'TERIMA KASIH' }}</p>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<style>
@media print {
  /* Hide the entire app layout during print */
  #app {
    display: none !important;
  }

  body, html {
    background-color: white !important;
    margin: 0;
    padding: 0;
    height: auto !important;
  }

  .print-receipt {
    display: block !important;
    width: 100% !important;
    max-width: 80mm !important; /* Adapts to both 58mm and 80mm thermal printers */
    margin: 0 auto !important;
    padding: 5mm !important;
    color: black !important;
    background: white !important;
    z-index: 999999 !important;
  }

  @page {
    margin: 0;
  }

  /* Force black text for all thermal prints */
  .print-receipt * {
    color: black !important;
    border-color: black !important;
  }
}
</style>
