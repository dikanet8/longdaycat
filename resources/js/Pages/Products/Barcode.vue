<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, router } from'@inertiajs/vue3';
import { ref, watch, onMounted, nextTick } from'vue';
import Pagination from'@/Components/Pagination.vue';
import JsBarcode from'jsbarcode';

const props = defineProps({
  products: Object,
  categories: Array,
  filters: Object
});

const search = ref(props.filters?.search ||'');
const perPage = ref(props.filters?.per_page || 10);
const barcodeType = ref('CODE128'); //'CODE128' or'QR'

const labelWidth = ref(64); // in mm
const labelHeight = ref(34); // in mm
const printColumns = ref(3); // columns in page
const showName = ref(true);
const showPrice = ref(true);
const showCodeText = ref(true);
const labelGap = ref(2); // in mm

const presets = [
  { name:'Label Roll (1 Kolom - 40x25mm)', width: 40, height: 25, columns: 1, gap: 0 },
  { name:'Label Roll (1 Kolom - 50x30mm)', width: 50, height: 30, columns: 1, gap: 0 },
  { name:'Label Sheet (3 Kolom - A4)', width: 64, height: 34, columns: 3, gap: 2 },
  { name:'Label Sheet (4 Kolom - A4)', width: 48, height: 25, columns: 4, gap: 2 },
  { name:'Kustom...', width: 40, height: 25, columns: 3, gap: 2 }
];
const selectedPresetIndex = ref(2); // Default to A4 sheet

const applyPreset = (indexVal) => {
  const idx = parseInt(indexVal, 10);
  selectedPresetIndex.value = idx;
  if (presets[idx] && presets[idx].name !=='Kustom...') {
    labelWidth.value = presets[idx].width;
    labelHeight.value = presets[idx].height;
    printColumns.value = presets[idx].columns;
    labelGap.value = presets[idx].gap;
  }
};

const printList = ref([]);

let searchTimeout = null;
watch(search, (value) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/barcodes', { search: value, per_page: perPage.value }, {
      preserveState: true,
      replace: true
    });
  }, 300);
});

watch(perPage, (value) => {
  router.get('/barcodes', { search: search.value, per_page: value }, {
    preserveState: true,
    replace: true
  });
});

const generateBarcodes = () => {
  nextTick(() => {
    props.products.data.forEach(product => {
      if (product.kode_produk && barcodeType.value ==='CODE128') {
        try {
          JsBarcode(`#barcode-${product.id}`, product.kode_produk, {
            format:"CODE128",
            width: 1.5,
            height: 40,
            displayValue: true,
            fontSize: 14,
            margin: 0,
            background:"transparent",
          });
        } catch (e) {
          console.error("Error generating barcode for", product.kode_produk, e);
        }
      }
    });
  });
};

const isInPrintList = (id) => {
  return printList.value.some(p => p.id === id);
};

const getPrintQty = (id) => {
  return printList.value.find(p => p.id === id)?.qty || 0;
};

onMounted(() => {
  generateBarcodes();
});

watch(() => props.products.data, () => {
  generateBarcodes();
}, { deep: true });

watch(barcodeType, () => {
  generateBarcodes();
});

const addToPrintList = (product) => {
  const exists = printList.value.find(p => p.id === product.id);
  if (exists) {
    exists.qty++;
  } else {
    printList.value.push({
      id: product.id,
      kode_produk: product.kode_produk,
      nama_produk: product.nama_produk,
      harga: product.harga,
      qty: 1
    });
  }
};

const removeFromPrintList = (index) => {
  printList.value.splice(index, 1);
};

const clearPrintList = () => {
  printList.value = [];
};

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits: 0 }).format(price);
};

const printBarcodes = () => {
  // Generate barcodes for the print view before printing
  nextTick(() => {
    if (barcodeType.value ==='CODE128') {
      const barcodeLineWidth = labelWidth.value < 45 ? 1.2 : 1.5;
      const barcodeHeight = Math.max(30, Math.min(60, labelHeight.value * 1.5));
      printList.value.forEach((item, index) => {
        for (let i = 0; i < item.qty; i++) {
          try {
            JsBarcode(`#print-barcode-${index}-${i}`, item.kode_produk, {
              format:"CODE128",
              width: barcodeLineWidth,
              height: barcodeHeight,
              displayValue: showCodeText.value,
              fontSize: 10,
              margin: 0,
            });
          } catch (e) {
            console.error(e);
          }
        }
      });
    }
    
    setTimeout(() => {
      window.print();
    }, 500);
  });
};
</script>

<template>
  <Head title="Cetak Barcode" />

  <AppLayout>
    <div class="space-y-6 print:hidden">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Cetak Barcode</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-sm">Pilih produk dan tentukan jumlah barcode yang ingin dicetak.</p>
        </div>
        
        <div class="bg-white dark:bg-slate-900 p-3 md:p-2 rounded-3xl md:rounded-md border border-slate-200 dark:border-white/5 shadow-sm flex items-center gap-2">
          <button 
            @click="barcodeType ='CODE128'"
            :class="[barcodeType ==='CODE128' ?'bg-blue-600 text-white shadow-md' :'text-slate-500 hover:bg-slate-100 dark:hover:bg-white/5','px-4 py-2 rounded-md text-[10px] font-black uppercase tracking-widest transition-all']"
          >
            Barcode
          </button>
          <button 
            @click="barcodeType ='QR'"
            :class="[barcodeType ==='QR' ?'bg-blue-600 text-white shadow-md' :'text-slate-500 hover:bg-slate-100 dark:hover:bg-white/5','px-4 py-2 rounded-md text-[10px] font-black uppercase tracking-widest transition-all']"
          >
            QR Code
          </button>
        </div>

        <div class="hidden md:flex items-center gap-3">
          <button 
            @click="printBarcodes"
            :disabled="printList.length === 0"
            class="px-6 py-3 bg-blue-600 disabled:bg-slate-400 hover:bg-blue-500 text-white rounded-md shadow-lg transition-all font-bold text-sm flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Cetak ({{ printList.reduce((acc, curr) => acc + curr.qty, 0) }} Label)
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Product List -->
        <div class="lg:col-span-2 space-y-4">
          <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm flex items-center gap-4">
            <div class="relative flex-1">
              <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input 
                v-model="search" 
                type="text" 
                placeholder="Cari berdasarkan nama atau kode produk..." 
                class="w-full pl-10 pr-3 py-2.5 md:py-2 bg-slate-50 dark:bg-white/5 border-none rounded-md md:rounded-md text-xs focus:ring-2 focus:ring-blue-500 transition-all ring-1 ring-slate-200 dark:ring-white/10 dark:text-white">
            </div>
          </div>

          <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
                <tr>
                  <th class="px-4 sm:px-6 py-4">Produk</th>
                  <th class="px-6 py-4 hidden sm:table-cell">Preview Barcode</th>
                  <th class="px-4 sm:px-6 py-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <tr v-for="product in products.data" :key="product.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02]">
                  <td class="px-4 sm:px-6 py-4">
                    <p class="font-bold text-slate-900 dark:text-white text-sm capitalize">{{ product.nama_produk }}</p>
                    <p class="text-[10px] text-slate-500 font-mono mt-0.5">{{ product.kode_produk }}</p>
                    <p class="text-xs font-bold text-blue-600 dark:text-blue-400 mt-1">{{ formatPrice(product.harga) }}</p>
                  </td>
                  <td class="px-6 py-4 hidden sm:table-cell">
                    <div class="bg-white p-2 rounded inline-block">
                      <svg v-if="barcodeType ==='CODE128'" :id="'barcode-' + product.id"></svg>
                      <div v-else class="w-16 h-16 bg-slate-100 rounded-sm flex items-center justify-center text-[8px] font-bold text-slate-400">QR Preview</div>
                    </div>
                  </td>
                  <td class="px-4 sm:px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-3">
                      <div v-if="isInPrintList(product.id)" class="px-2 py-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-black rounded-full border border-emerald-500/20">
                        {{ getPrintQty(product.id) }}x Dalam Antrean
                      </div>
                      <button @click="addToPrintList(product)" class="p-2 sm:px-3 sm:py-1.5 bg-slate-100 hover:bg-blue-100 text-blue-600 dark:bg-slate-800 dark:hover:bg-blue-900/40 dark:text-blue-400 text-xs font-bold rounded-lg transition-colors">
                        <span class="hidden sm:inline">Tambah</span>
                        <svg class="w-5 h-5 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="products.data.length === 0">
                  <td colspan="3" class="px-6 py-12 text-center text-slate-500">
                    Tidak ada produk ditemukan.
                  </td>
                </tr>
              </tbody>
            </table>
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 flex items-center justify-end gap-6 bg-slate-50/50 dark:bg-white/[0.02]">
              <div class="flex items-center gap-2 mr-auto">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
                <select v-model="perPage" class="bg-white dark:bg-slate-800 border-none rounded-md text-[11px] font-bold text-slate-700 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 py-1.5 pl-3 pr-8 w-20 cursor-pointer">
                  <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n">{{ n }}</option>
                </select>
              </div>
              
              <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wider hidden sm:block">
                Menampilkan {{ products.from || 0 }}-{{ products.to || 0 }} dari {{ products.total || 0 }}
              </p>
              <Pagination :links="products.links" />
            </div>
          </div>
        </div>

        <!-- Right: Print Settings & Queue -->
        <div class="space-y-6 lg:sticky lg:top-6 h-fit">
          <!-- Print Settings Card -->
          <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm p-5">
            <h3 class="font-bold text-slate-900 dark:text-white mb-4 pb-4 border-b border-slate-100 dark:border-white/5 flex items-center gap-2">
              <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Pengaturan Layout Cetak
            </h3>
            
            <div class="space-y-4">
              <!-- Preset Select -->
              <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-400">Preset Kertas</label>
                <select @change="applyPreset($event.target.value)" class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 py-2.5 px-3 cursor-pointer focus:ring-2 focus:ring-blue-500">
                  <option v-for="(p, idx) in presets" :key="idx" :value="idx" :selected="idx === selectedPresetIndex">{{ p.name }}</option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <!-- Width -->
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-wider text-slate-400">Lebar (mm)</label>
                  <input type="number" v-model="labelWidth" @input="selectedPresetIndex = 4" class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-lg text-xs font-bold text-slate-800 dark:text-white py-2 px-3 focus:ring-2 focus:ring-blue-500" min="10" max="150" />
                </div>
                <!-- Height -->
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-wider text-slate-400">Tinggi (mm)</label>
                  <input type="number" v-model="labelHeight" @input="selectedPresetIndex = 4" class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-lg text-xs font-bold text-slate-800 dark:text-white py-2 px-3 focus:ring-2 focus:ring-blue-500" min="10" max="150" />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <!-- Columns -->
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-wider text-slate-400">Kolom</label>
                  <input type="number" v-model="printColumns" @input="selectedPresetIndex = 4" class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-lg text-xs font-bold text-slate-800 dark:text-white py-2 px-3 focus:ring-2 focus:ring-blue-500" min="1" max="10" />
                </div>
                <!-- Gap -->
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-wider text-slate-400">Jarak (mm)</label>
                  <input type="number" v-model="labelGap" @input="selectedPresetIndex = 4" class="w-full bg-slate-50 dark:bg-slate-800/50 border-none rounded-lg text-xs font-bold text-slate-800 dark:text-white py-2 px-3 focus:ring-2 focus:ring-blue-500" min="0" max="20" />
                </div>
              </div>

              <!-- Options Checkboxes -->
              <div class="pt-3 border-t border-slate-100 dark:border-white/5 space-y-2">
                <label class="flex items-center gap-2.5 text-xs text-slate-700 dark:text-slate-300 font-medium cursor-pointer">
                  <input type="checkbox" v-model="showName" class="rounded border-slate-300 dark:border-white/10 text-blue-600 focus:ring-blue-500/50 bg-transparent" />
                  Tampilkan Nama Produk
                </label>
                <label class="flex items-center gap-2.5 text-xs text-slate-700 dark:text-slate-300 font-medium cursor-pointer">
                  <input type="checkbox" v-model="showPrice" class="rounded border-slate-300 dark:border-white/10 text-blue-600 focus:ring-blue-500/50 bg-transparent" />
                  Tampilkan Harga
                </label>
                <label class="flex items-center gap-2.5 text-xs text-slate-700 dark:text-slate-300 font-medium cursor-pointer">
                  <input type="checkbox" v-model="showCodeText" class="rounded border-slate-300 dark:border-white/10 text-blue-600 focus:ring-blue-500/50 bg-transparent" />
                  Tampilkan Kode Text
                </label>
              </div>
            </div>
          </div>

          <!-- Print Queue Card -->
          <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm p-5 flex flex-col">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-100 dark:border-white/5">
              <h3 class="font-bold text-slate-900 dark:text-white">Daftar Cetak</h3>
              <button v-if="printList.length > 0" @click="clearPrintList" class="text-xs text-red-500 hover:underline font-bold">Kosongkan</button>
            </div>

            <div v-if="printList.length === 0" class="text-center py-8 text-slate-500 text-sm">
              Belum ada barcode yang dipilih.
            </div>

            <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2" style="scrollbar-width: thin;">
              <div v-for="(item, index) in printList" :key="index" class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-white/10 shadow-sm relative group transition-all hover:border-blue-500/50">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-800/50 text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-black text-sm text-slate-900 dark:text-white truncate">{{ item.nama_produk }}</p>
                    <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 font-mono mt-0.5 bg-slate-100 dark:bg-slate-700/50 inline-block px-1.5 py-0.5 rounded">{{ item.kode_produk }}</p>
                  </div>
                  <button @click="removeFromPrintList(index)" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors opacity-0 group-hover:opacity-100 absolute top-3 right-3 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
                <div class="mt-4 pt-3 border-t border-dashed border-slate-200 dark:border-white/10 flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-600 dark:text-slate-400">Jumlah Cetak</span>
                  <div class="flex items-center bg-slate-100 dark:bg-slate-900 rounded-lg p-1">
                    <button @click="item.qty > 1 ? item.qty-- : null" class="w-7 h-7 rounded-md bg-white dark:bg-slate-800 flex items-center justify-center font-bold text-slate-600 dark:text-slate-300 shadow-sm hover:text-blue-600 transition-colors">-</button>
                    <input type="number" v-model="item.qty" class="w-12 text-center text-sm bg-transparent border-none font-black text-slate-900 dark:text-white p-0 focus:ring-0" min="1" />
                    <button @click="item.qty++" class="w-7 h-7 rounded-md bg-white dark:bg-slate-800 flex items-center justify-center font-bold text-slate-600 dark:text-slate-300 shadow-sm hover:text-blue-600 transition-colors">+</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Live Print Preview Card -->
          <div v-if="printList.length > 0" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-white/5 p-5 shadow-sm">
            <h3 class="font-bold text-slate-900 dark:text-white mb-4 pb-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
              <span>Live Preview Cetak</span>
              <span class="text-[9px] bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-0.5 rounded font-black uppercase tracking-wider">Skala Layar</span>
            </h3>
            <div class="overflow-x-auto p-3 bg-slate-50 dark:bg-slate-950 rounded-xl max-h-[400px] scrollbar-thin">
              <div class="grid gap-2 justify-start mx-auto" :style="{
                gridTemplateColumns: printColumns === 1 ?'1fr' : `repeat(${printColumns}, ${labelWidth * 1.5}px)`,
              }">
                <template v-for="(item, index) in printList" :key="'preview-'+index">
                  <div v-for="i in item.qty" :key="'preview-'+index+'-'+i" :style="{
                    width: (labelWidth * 1.5) +'px',
                    height: (labelHeight * 1.5) +'px',
                  }" class="flex flex-col items-center justify-center border border-dashed border-slate-300 dark:border-white/10 bg-white dark:bg-slate-900 p-1.5 text-center overflow-hidden shrink-0 select-none">
                    <p v-if="showName" class="text-[8px] font-black truncate w-full text-slate-900 dark:text-white capitalize leading-none mb-0.5">{{ item.nama_produk }}</p>
                    <p v-if="showPrice" class="text-[8px] font-bold text-blue-600 dark:text-blue-400 leading-none mb-1">{{ formatPrice(item.harga) }}</p>
                    
                    <!-- Barcode/QR Placeholder -->
                    <div v-if="barcodeType ==='CODE128'" class="w-full flex justify-center py-0.5">
                      <div class="h-6 w-5/6 bg-slate-100 dark:bg-slate-800 flex flex-col justify-between p-0.5">
                        <div class="w-full h-full flex gap-px bg-white dark:bg-slate-900 overflow-hidden">
                          <div v-for="n in 25" :key="n" :style="{ width: (n % 3 === 0 ?'2px' :'1px') }" class="h-full bg-slate-800 dark:bg-slate-300"></div>
                        </div>
                      </div>
                    </div>
                    <div v-else class="w-8 h-8 flex items-center justify-center bg-white border border-slate-200">
                       <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=50x50&data=${item.kode_produk}`" class="w-6 h-6" />
                    </div>
                    <p v-if="showCodeText" class="text-[7px] font-mono text-slate-500 dark:text-slate-400 mt-1 truncate w-full leading-none">{{ item.kode_produk }}</p>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Print View (Only visible when printing) -->
    <div class="barcode-print-container bg-white text-black" :style="{
     '--label-width': labelWidth +'mm',
     '--label-height': labelHeight +'mm',
     '--print-columns': printColumns === 1 ?'1fr' : `repeat(${printColumns}, var(--label-width))`,
     '--label-gap': labelGap +'mm',
    }">
      <div class="print-grid">
        <template v-for="(item, index) in printList" :key="'p-'+index">
          <div v-for="i in item.qty" :key="'p-'+index+'-'+i" class="print-label">
            <p v-if="showName" class="text-[10px] font-bold truncate w-full mb-0.5 leading-none">{{ item.nama_produk }}</p>
            <p v-if="showPrice" class="text-[10px] font-bold mb-1 leading-none">{{ formatPrice(item.harga) }}</p>
            
            <svg v-if="barcodeType ==='CODE128'" :id="'print-barcode-' + index +'-' + (i - 1)" class="max-w-full h-auto"></svg>
            <div v-else class="flex items-center justify-center bg-white">
               <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${item.kode_produk}`" class="w-12 h-12" />
            </div>
            
            <p v-if="showCodeText && barcodeType !=='CODE128'" class="text-[8px] font-mono mt-1 leading-none">{{ item.kode_produk }}</p>
          </div>
        </template>
      </div>
    </div>
    <!-- Mobile FAB for Print -->
    <div class="fixed bottom-24 right-6 md:hidden z-50 print:hidden">
      <button 
        @click="printBarcodes"
        :disabled="printList.length === 0"
        class="w-14 h-14 bg-blue-600 disabled:bg-slate-400 disabled:scale-100 hover:bg-blue-500 hover:scale-105 active:scale-95 text-white rounded-full shadow-[0_8px_30px_rgb(37,99,235,0.4)] transition-all flex items-center justify-center group relative"
      >
        <div v-if="printList.length > 0" class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-black w-6 h-6 flex items-center justify-center rounded-full border-2 border-white dark:border-slate-900 shadow-sm animate-bounce">
          {{ printList.reduce((acc, curr) => acc + curr.qty, 0) }}
        </div>
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
      </button>
    </div>
  </AppLayout>
</template>

<style>
.barcode-print-container {
  display: none;
}

@media print {
  /* Hide layout elements */
  .print\:hidden,
  header,
  aside,
  nav,
  .no-print {
    display: none !important;
  }

  html, body, #app {
    height: auto !important;
    min-height: 0 !important;
    overflow: visible !important;
  }

  body {
    background-color: white !important;
    color: black !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  /* Reset main container padding/scroll for printing */
  main {
    padding: 0 !important;
    overflow: visible !important;
  }

  .barcode-print-container {
    display: block !important;
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    height: auto !important;
    z-index: 999999 !important;
    background-color: white !important;
  }

  .print-grid {
    display: grid;
    grid-template-columns: var(--print-columns);
    gap: var(--label-gap);
    justify-content: start;
    align-content: start;
    padding: 0;
    margin: 0;
  }

  .print-label {
    width: var(--label-width);
    height: var(--label-height);
    page-break-inside: avoid;
    break-inside: avoid;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    box-sizing: border-box;
    padding: 1.5mm;
    overflow: hidden;
    border: none !important; /* No borders when printing */
  }
}
</style>
