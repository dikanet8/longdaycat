<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, router, Link } from'@inertiajs/vue3';
import { ref, computed } from'vue';
import Pagination from'@/Components/Pagination.vue';
import { Document, Paragraph, Table, TableRow, TableCell, TextRun, HeadingLevel, AlignmentType, WidthType, BorderStyle, Packer, ShadingType } from'docx';
import { saveAs } from'file-saver';

import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from'chart.js';
import { Line } from'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
  stats: Object,
  chart: Object,
  filters: Object,
  recent_transactions: Object
});

const formFilters = ref({
  date: (props.filters && props.filters.date) ? props.filters.date :'',
  month: props.filters ? props.filters.month : new Date().getMonth() + 1,
  year: props.filters ? props.filters.year : new Date().getFullYear(),
  per_page: (props.filters && props.filters.per_page) ? props.filters.per_page : 10
});

import { watch } from'vue';
watch(() => formFilters.value.per_page, (value) => {
  applyFilter();
});

const months = [
 'Januari','Februari','Maret','April','Mei','Juni',
 'Juli','Agustus','September','Oktober','November','Desember'
];

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits: 0 }).format(price || 0);
};

const stats = computed(() => [
  { name:'Total Pendapatan', value: formatPrice(props.stats?.total_pendapatan || 0), rawValue: props.stats?.total_pendapatan || 0, change:'+0%', icon:'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', color:'text-emerald-400' },
  { name:'Total Transaksi', value: props.stats?.total_transaksi || 0, rawValue: props.stats?.total_transaksi || 0, change:'+0%', icon:'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', color:'text-blue-400' },
  { name:'Produk Terlaris', value: props.stats?.produk_terlaris ||'-', rawValue: props.stats?.produk_terlaris ||'-', change:'Top #1', icon:'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', color:'text-amber-400' },
  { name:'Total Produk Terjual', value: (props.stats?.total_produk_terjual || 0) +' pcs', rawValue: props.stats?.total_produk_terjual || 0, change:'+0%', icon:'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', color:'text-purple-400' },
]);

const chartData = computed(() => ({
  labels: props.chart?.labels || [],
  datasets: [
    {
      label:'Penjualan',
      backgroundColor:'rgba(59, 130, 246, 0.1)',
      borderColor:'#3b82f6',
      pointBackgroundColor:'#3b82f6',
      pointBorderColor:'#fff',
      pointHoverBackgroundColor:'#fff',
      pointHoverBorderColor:'#3b82f6',
      borderWidth: 3,
      fill: true,
      tension: 0.4,
      data: props.chart?.data || []
    }
  ]
}));

const applyFilter = () => {
  router.get('/reports/sales', formFilters.value, { preserveState: true });
};

const printReport = () => {
  window.print();
};

const isExportingWord = ref(false);

const exportWord = async () => {
  isExportingWord.value = true;
  try {
    const period = getFilterPeriod();
    const printNow = new Date().toLocaleString('id-ID', { day:'numeric', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' });
    const trxList = props.recent_transactions?.data || [];

    const headerBg = { type: ShadingType.SOLID, color:'1e40af', fill:'1e40af' };
    const rowAltBg = { type: ShadingType.SOLID, color:'eff6ff', fill:'eff6ff' };

    const tableRows = [
      new TableRow({
        tableHeader: true,
        children: ['No','Kode Transaksi','Tanggal','Item','Total','Metode','Kasir'].map(h =>
          new TableCell({
            shading: headerBg,
            children: [new Paragraph({
              alignment: AlignmentType.CENTER,
              children: [new TextRun({ text: h, bold: true, color:'FFFFFF', size: 20, font:'Calibri' })]
            })]
          })
        )
      }),
      ...trxList.map((trx, idx) => new TableRow({
        children: [
          new TableCell({ shading: idx % 2 === 1 ? rowAltBg : undefined, children: [new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: String(idx + 1), size: 18, font:'Calibri' })] })] }),
          new TableCell({ shading: idx % 2 === 1 ? rowAltBg : undefined, children: [new Paragraph({ children: [new TextRun({ text: trx.kode_transaksi, bold: true, size: 18, font:'Calibri' })] })] }),
          new TableCell({ shading: idx % 2 === 1 ? rowAltBg : undefined, children: [new Paragraph({ children: [new TextRun({ text: new Date(trx.created_at).toLocaleDateString('id-ID'), size: 18, font:'Calibri' })] })] }),
          new TableCell({ shading: idx % 2 === 1 ? rowAltBg : undefined, children: (trx.details || []).map(d => new Paragraph({ children: [new TextRun({ text: `${d.produk?.nama_produk || d.kode_produk} x${d.jumlah}`, size: 18, font:'Calibri' })] })) }),
          new TableCell({ shading: idx % 2 === 1 ? rowAltBg : undefined, children: [new Paragraph({ alignment: AlignmentType.RIGHT, children: [new TextRun({ text: formatPrice(trx.total_harga), bold: true, color:'1d4ed8', size: 18, font:'Calibri' })] })] }),
          new TableCell({ shading: idx % 2 === 1 ? rowAltBg : undefined, children: [new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: trx.metode_bayar, size: 18, font:'Calibri' })] })] }),
          new TableCell({ shading: idx % 2 === 1 ? rowAltBg : undefined, children: [new Paragraph({ children: [new TextRun({ text: trx.user?.name ||'Sistem', size: 18, font:'Calibri' })] })] }),
        ]
      }))
    ];

    const doc = new Document({
      styles: {
        default: {
          document: { run: { font:'Calibri', size: 22 } }
        }
      },
      sections: [{
        properties: { page: { margin: { top: 720, bottom: 720, left: 900, right: 900 } } },
        children: [
          // Header garis biru atas
          new Paragraph({ border: { top: { style: BorderStyle.THICK, size: 24, color:'1e40af' } }, text:'' }),
          new Paragraph({ spacing: { after: 100 } }),

          // Nama perusahaan
          new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 40 },
            children: [new TextRun({ text:'LONGDAYCAT.CO', bold: true, size: 52, color:'1e40af', font:'Calibri' })]
          }),
          new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 40 },
            children: [new TextRun({ text:'Sistem Manajemen Inventori & Penjualan', size: 22, color:'64748b', font:'Calibri' })]
          }),
          new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 200 },
            children: [new TextRun({ text:'Jl. Contoh No. 123, Kota Anda | Telp: (021) 0000-0000 | info@longdaycat.co', size: 18, color:'94a3b8', font:'Calibri' })]
          }),

          // Garis pemisah
          new Paragraph({ border: { bottom: { style: BorderStyle.SINGLE, size: 6, color:'1e40af' } }, spacing: { after: 200 } }),

          // Judul laporan
          new Paragraph({
            heading: HeadingLevel.HEADING_1,
            alignment: AlignmentType.CENTER,
            spacing: { before: 200, after: 100 },
            children: [new TextRun({ text:'LAPORAN PENJUALAN TERPERINCI', bold: true, size: 36, color:'0f172a', font:'Calibri' })]
          }),
          new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 400 },
            children: [new TextRun({ text: `Periode: ${period} | Dicetak: ${printNow}`, size: 20, color:'475569', font:'Calibri', italics: true })]
          }),

          // Ringkasan Stats
          new Paragraph({ spacing: { before: 100, after: 100 }, children: [new TextRun({ text:'RINGKASAN STATISTIK', bold: true, size: 24, color:'1e40af', font:'Calibri' })] }),
          new Table({
            width: { size: 100, type: WidthType.PERCENTAGE },
            rows: [
              new TableRow({
                children: [
                  new TableCell({ shading: { type: ShadingType.SOLID, color:'dbeafe', fill:'dbeafe' }, children: [new Paragraph({ children: [new TextRun({ text:'Total Pendapatan', bold: true, size: 20, color:'1e40af', font:'Calibri' })] })] }),
                  new TableCell({ shading: { type: ShadingType.SOLID, color:'dbeafe', fill:'dbeafe' }, children: [new Paragraph({ alignment: AlignmentType.RIGHT, children: [new TextRun({ text: formatPrice(props.stats?.total_pendapatan || 0), bold: true, size: 24, color:'16a34a', font:'Calibri' })] })] }),
                  new TableCell({ shading: { type: ShadingType.SOLID, color:'f0fdf4', fill:'f0fdf4' }, children: [new Paragraph({ children: [new TextRun({ text:'Total Transaksi', bold: true, size: 20, color:'15803d', font:'Calibri' })] })] }),
                  new TableCell({ shading: { type: ShadingType.SOLID, color:'f0fdf4', fill:'f0fdf4' }, children: [new Paragraph({ alignment: AlignmentType.RIGHT, children: [new TextRun({ text: String(props.stats?.total_transaksi || 0), bold: true, size: 24, color:'1d4ed8', font:'Calibri' })] })] }),
                  new TableCell({ shading: { type: ShadingType.SOLID, color:'fefce8', fill:'fefce8' }, children: [new Paragraph({ children: [new TextRun({ text:'Produk Terjual', bold: true, size: 20, color:'a16207', font:'Calibri' })] })] }),
                  new TableCell({ shading: { type: ShadingType.SOLID, color:'fefce8', fill:'fefce8' }, children: [new Paragraph({ alignment: AlignmentType.RIGHT, children: [new TextRun({ text: `${props.stats?.total_produk_terjual || 0} pcs`, bold: true, size: 24, color:'b45309', font:'Calibri' })] })] }),
                ]
              })
            ]
          }),
          new Paragraph({ spacing: { after: 300 } }),

          // Tabel transaksi
          new Paragraph({ spacing: { before: 100, after: 100 }, children: [new TextRun({ text:'DAFTAR TRANSAKSI', bold: true, size: 24, color:'1e40af', font:'Calibri' })] }),
          new Table({
            width: { size: 100, type: WidthType.PERCENTAGE },
            rows: tableRows
          }),
          new Paragraph({ spacing: { after: 600 } }),

          // Tanda tangan
          new Paragraph({ border: { top: { style: BorderStyle.SINGLE, size: 4, color:'e2e8f0' } }, spacing: { before: 400, after: 200 } }),
          new Table({
            width: { size: 100, type: WidthType.PERCENTAGE },
            borders: { top: { style: BorderStyle.NONE }, bottom: { style: BorderStyle.NONE }, left: { style: BorderStyle.NONE }, right: { style: BorderStyle.NONE }, insideH: { style: BorderStyle.NONE }, insideV: { style: BorderStyle.NONE } },
            rows: [
              new TableRow({ children: [
                new TableCell({ children: [
                  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text:'Mengetahui,', size: 20, font:'Calibri', color:'475569' })] }),
                  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 800 }, children: [new TextRun({ text:'_____________________', size: 20, color:'94a3b8', font:'Calibri' })] }),
                  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text:'Pimpinan / Owner', bold: true, size: 20, font:'Calibri' })] }),
                  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text:'Longdaycat.Co', size: 18, color:'64748b', font:'Calibri', italics: true })] }),
                ] }),
                new TableCell({ children: [
                  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text:'Dibuat oleh,', size: 20, font:'Calibri', color:'475569' })] }),
                  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 800 }, children: [new TextRun({ text:'_____________________', size: 20, color:'94a3b8', font:'Calibri' })] }),
                  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text:'Admin / Kasir', bold: true, size: 20, font:'Calibri' })] }),
                  new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text:'Sistem Longdaycat', size: 18, color:'64748b', font:'Calibri', italics: true })] }),
                ] }),
              ]})
            ]
          }),

          // Footer
          new Paragraph({ spacing: { before: 400 }, border: { top: { style: BorderStyle.SINGLE, size: 4, color:'1e40af' } } }),
          new Paragraph({
            alignment: AlignmentType.CENTER,
            children: [new TextRun({ text: `Dokumen ini digenerate otomatis oleh sistem Longdaycat.Co pada ${printNow}`, size: 16, color:'94a3b8', italics: true, font:'Calibri' })]
          }),
        ]
      }]
    });

    const blob = await Packer.toBlob(doc);
    saveAs(blob, `Laporan-Penjualan-${period.replace(/\s/g,'-')}.docx`);
  } catch (e) {
    console.error('Export Word gagal:', e);
    alert('Gagal export Word:' + e.message);
  } finally {
    isExportingWord.value = false;
  }
};

const getFilterPeriod = () => {
  try {
    if (formFilters.value.date) {
      const d = new Date(formFilters.value.date);
      if (!isNaN(d.getTime())) {
        return d.toLocaleDateString('id-ID', {
          day:'numeric',
          month:'long',
          year:'numeric'
        });
      }
    }
  } catch (e) {
    console.error(e);
  }
  
  const monthIndex = parseInt(formFilters.value.month);
  if (!isNaN(monthIndex) && monthIndex >= 1 && monthIndex <= 12 && formFilters.value.year) {
    return `${months[monthIndex - 1]} ${formFilters.value.year}`;
  }
  return `Tahun ${formFilters.value.year || new Date().getFullYear()}`;
};

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      mode:'index',
      intersect: false,
      backgroundColor:'rgba(15, 23, 42, 0.9)',
      titleFont: { size: 14, weight:'bold' },
      bodyFont: { size: 13 },
      padding: 12,
      cornerRadius: 12,
      displayColors: false,
      callbacks: {
        label: function(context) {
          let label = context.dataset.label ||'';
          if (label) {
            label +=':';
          }
          if (context.parsed.y !== null) {
            label += new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
          }
          return label;
        }
      }
    }
  },
  scales: {
    y: {
      grid: { display: true, color:'rgba(203, 213, 225, 0.1)', drawBorder: false },
      ticks: { 
        display: true, 
        color:'#64748b', 
        font: { size: 11 }, 
        beginAtZero: true,
        callback: function(value) {
          if (value >= 1000000000) {
            return'Rp' + (value / 1000000000).toFixed(1) +' M';
          } else if (value >= 1000000) {
            return'Rp' + (value / 1000000).toFixed(1) +' Jt';
          } else if (value >= 1000) {
            return'Rp' + (value / 1000).toFixed(0) +'K';
          }
          return'Rp' + value;
        }
      }
    },
    x: {
      grid: { display: false },
      ticks: { color:'#64748b', font: { size: 11 } }
    }
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleString('id-ID', {
    day:'numeric',
    month:'short',
    year:'numeric'
  });
};

const printDate = computed(() => {
  return new Date().toLocaleString('id-ID', {
    day:'numeric',
    month:'short',
    year:'numeric',
    hour:'2-digit',
    minute:'2-digit'
  });
});

const getExportUrl = () => {
  const params = new URLSearchParams();
  if (formFilters.value.date) params.append('date', formFilters.value.date);
  if (formFilters.value.month) params.append('month', formFilters.value.month);
  if (formFilters.value.year) params.append('year', formFilters.value.year);
  params.append('export','csv');
  return `/reports/sales?${params.toString()}`;
};
</script>

<template>
  <Head title="Laporan Penjualan" />

  <AppLayout>
    <!-- Print Only Header (PDF) - Premium Design -->
    <div class="hidden print:block">
      <!-- Kop Surat -->
      <div class="print-header" style="border-top: 6px solid #1e40af; padding-top: 20px; margin-bottom: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
          <div>
            <div style="font-size: 28px; font-weight: 900; color: #1e40af; letter-spacing: -1px;">LONGDAYCAT.CO</div>
            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Sistem Manajemen Inventori &amp; Penjualan</div>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">Jl. Contoh No. 123, Kota Anda | Telp: (021) 0000-0000</div>
          </div>
          <div style="text-align: right;">
            <div style="font-size: 11px; font-weight: 700; color: #334155;">LAPORAN PENJUALAN</div>
            <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Periode: {{ getFilterPeriod() }}</div>
            <div style="font-size: 9px; color: #94a3b8; margin-top: 2px;">Dicetak: {{ printDate }}</div>
          </div>
        </div>
      </div>
      <div style="border-bottom: 2px solid #1e40af; margin-bottom: 16px;"></div>



      <!-- Judul Tabel -->
      <div style="font-size: 11px; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0;">Daftar Transaksi Terperinci</div>
    </div>

    <div class="space-y-8">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Laporan Penjualan</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-xs md:text-sm">Analisis performa penjualan produk Anda.</p>
        </div>
      </div>

      <!-- Filters Bar (Above Cards, Borderless) -->
      <div class="grid grid-cols-2 md:flex md:flex-wrap items-end gap-3 no-print bg-slate-50 dark:bg-slate-900 p-4 rounded-xl border border-slate-100 dark:border-white/5">
        <div class="flex flex-col col-span-2 md:col-span-1">
          <label class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 font-bold ml-1 mb-1.5">Tanggal</label>
          <input type="date" v-model="formFilters.date" @change="applyFilter" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full md:w-36 py-2.5 px-3.5 shadow-sm" />
        </div>
        <div class="flex flex-col" v-if="!formFilters.date">
          <label class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 font-bold ml-1 mb-1.5">Bulan</label>
          <select v-model="formFilters.month" @change="applyFilter" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full md:w-32 py-2.5 px-3.5 shadow-sm cursor-pointer">
            <option v-for="(m, i) in months" :key="m" :value="i+1">{{ m }}</option>
          </select>
        </div>
        <div class="flex flex-col" v-if="!formFilters.date">
          <label class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 font-bold ml-1 mb-1.5">Tahun</label>
          <select v-model="formFilters.year" @change="applyFilter" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full md:w-24 py-2.5 px-3.5 shadow-sm cursor-pointer">
            <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="flex flex-col col-span-2 md:col-span-1 justify-end w-full md:w-auto mt-2 md:mt-0">
          <div class="flex items-center gap-2 w-full md:w-auto">
            <!-- PDF -->
            <button 
              @click="printReport" 
              class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-sm transition-all active:scale-95 h-[38px] cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              <span>PDF</span>
            </button>
            <!-- Word -->
            <button 
              @click="exportWord"
              :disabled="isExportingWord"
              class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-700 hover:bg-blue-800 disabled:opacity-60 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-sm transition-all active:scale-95 h-[38px] cursor-pointer"
            >
              <svg v-if="!isExportingWord" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              <span>{{ isExportingWord ?'...' :'Word' }}</span>
            </button>
            <!-- Excel/CSV -->
            <a 
              :href="getExportUrl()" 
              class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-sm transition-all active:scale-95 h-[38px] cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              <span>Excel</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-for="stat in stats" :key="stat.name" 
          class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-slate-900 dark:ring-white/10 flex flex-col justify-between"
        >
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ stat.name }}</span>
            <div class="p-2 bg-slate-50 dark:bg-slate-800 rounded-lg" :class="stat.color">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white mt-2 truncate">{{ stat.value }}</div>
          <div class="mt-2 flex items-center gap-1.5 text-xs font-semibold">
            <span class="text-emerald-600 dark:text-emerald-400">
              {{ stat.change }}
            </span>
          </div>
        </div>
      </div>

      <!-- Chart -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-md overflow-hidden shadow-sm border-t-4 border-t-blue-600 no-print">
        <div class="p-6 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
          <div>
            <h3 class="font-bold text-lg text-slate-900 dark:text-white">Trend Penjualan</h3>
            <p class="text-xs text-slate-500 font-medium">Visualisasi pendapatan dalam periode terpilih</p>
          </div>
        </div>
        <div class="p-6 h-[300px] sm:h-[400px]">
          <Line :data="chartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Detailed Table -->
      <div class="bg-white dark:bg-slate-900 rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-100 dark:border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <h3 class="font-bold text-lg text-slate-900 dark:text-white">Daftar Transaksi Terperinci</h3>
        </div>
        <!-- Mobile Card List (Hidden on Desktop) -->
        <div class="block md:hidden divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="trx in recent_transactions?.data || []" :key="trx.id" class="p-5 space-y-3 hover:bg-slate-50 dark:hover:bg-white/[0.01] transition-colors">
            <div class="flex items-center justify-between">
              <span class="font-black text-slate-900 dark:text-white text-sm uppercase tracking-tighter">{{ trx.kode_transaksi }}</span>
              <span class="text-[10px] text-slate-500 font-semibold">{{ formatDate(trx.created_at) }}</span>
            </div>

            <!-- Item Details -->
            <div class="space-y-1 bg-slate-50 dark:bg-white/5 p-3 rounded-lg">
              <div v-for="d in trx.details" :key="d.id" class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                <span>{{ d.produk?.nama_produk || d.kode_produk }}</span>
                <span class="text-slate-400 dark:text-slate-500 text-[10px] ml-1.5 font-medium">
                  ({{ d.produk?.ukuran ||'-' }} / {{ d.produk?.warna ||'-' }})
                </span>
                <span class="text-slate-500 dark:text-slate-400 ml-2">x{{ d.jumlah }}</span>
              </div>
            </div>

            <!-- Info & Action -->
            <div class="flex items-center justify-between pt-2">
              <div>
                <span class="text-[10px] font-black uppercase text-slate-400 mr-2">Total:</span>
                <span class="font-black text-blue-600 dark:text-blue-400 text-sm tracking-tighter">{{ formatPrice(trx.total_harga) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-slate-100 dark:bg-white/5 text-[9px] font-black uppercase rounded text-slate-600 dark:text-slate-400">{{ trx.metode_bayar }}</span>
                <Link :href="`/payments/${trx.id}`" class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-black text-[10px] uppercase rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50">Detail</Link>
              </div>
            </div>
          </div>

          <div v-if="!recent_transactions?.data || recent_transactions.data.length === 0" class="p-8 text-center text-xs text-slate-400 font-bold uppercase tracking-wider">
            Tidak ada transaksi pada periode ini
          </div>
        </div>

        <!-- Desktop Table View (Hidden on Mobile) -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
              <tr>
                <th class="px-8 py-5">Tanggal</th>
                <th class="px-8 py-5">Kode</th>
                <th class="px-8 py-5">Item</th>
                <th class="px-8 py-5">Total</th>
                <th class="px-8 py-5">Metode</th>
                <th class="px-8 py-5">Kasir</th>
                <th class="px-8 py-5 text-right no-print">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
              <tr v-for="trx in recent_transactions?.data || []" :key="trx.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                <td class="px-8 py-5 text-xs text-slate-500 font-medium">{{ formatDate(trx.created_at) }}</td>
                <td class="px-8 py-5">
                  <span class="font-black text-slate-900 dark:text-white text-sm uppercase tracking-tighter">{{ trx.kode_transaksi }}</span>
                </td>
                <td class="px-8 py-5">
                  <div class="space-y-1">
                    <div v-for="d in trx.details" :key="d.id" class="text-xs font-semibold">
                      <span class="text-slate-900 dark:text-white">{{ d.produk?.nama_produk || d.kode_produk }}</span>
                      <span class="text-slate-400 dark:text-slate-500 text-[10px] ml-1.5 font-medium">
                        ({{ d.produk?.ukuran ||'-' }} / {{ d.produk?.warna ||'-' }})
                      </span>
                      <span class="text-slate-500 dark:text-slate-400 ml-2">x{{ d.jumlah }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-5 font-black text-blue-600 dark:text-blue-400 text-sm tracking-tighter">{{ formatPrice(trx.total_harga) }}</td>
                <td class="px-8 py-5">
                  <span class="px-2 py-1 bg-slate-100 dark:bg-white/5 text-[10px] font-black uppercase rounded">{{ trx.metode_bayar }}</span>
                </td>
                <td class="px-8 py-5">
                  <span class="text-xs font-bold text-slate-700 dark:text-slate-300 capitalize">{{ trx.user?.name ||'Sistem' }}</span>
                </td>
                <td class="px-8 py-5 text-right no-print">
                  <Link :href="`/payments/${trx.id}`" class="text-blue-600 font-black text-[10px] uppercase hover:underline">Detail</Link>
                </td>
              </tr>
              <tr v-if="!recent_transactions?.data || recent_transactions.data.length === 0">
                <td colspan="7" class="px-8 py-12 text-center text-xs text-slate-400 font-bold uppercase tracking-wider bg-slate-50/10 dark:bg-white/[0.01]">
                  <div class="flex flex-col items-center justify-center space-y-2">
                    <svg class="w-8 h-8 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>Tidak ada transaksi pada periode ini</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination (Responsive Grid/Flex) -->
        <div class="px-6 py-5 border-t border-slate-100 dark:border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/50 dark:bg-white/[0.02] no-print">
          <!-- Per Page Selector -->
          <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-start">
            <div class="flex items-center gap-2">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
              <select v-model="formFilters.per_page" class="bg-white dark:bg-slate-800 border-none rounded-md text-[11px] font-bold text-slate-700 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 py-1.5 pl-3 pr-8 w-16 cursor-pointer">
                <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n">{{ n }}</option>
              </select>
            </div>
            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider sm:hidden">
              Total: {{ recent_transactions?.total || 0 }}
            </span>
          </div>

          <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
            <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wider hidden sm:block">
              Menampilkan {{ recent_transactions?.from || 0 }}-{{ recent_transactions?.to || 0 }} dari {{ recent_transactions?.total || 0 }}
            </p>
            <div class="w-full sm:w-auto flex justify-center">
              <Pagination :links="recent_transactions?.links || []" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Print Signature & Footer (PDF only) -->
    <div class="hidden print:block mt-8">
      <div class="print-signature">
        <div class="print-signature-box">
          <div class="print-signature-label">Mengetahui,</div>
          <div class="print-signature-line"></div>
          <div class="print-signature-name">Pimpinan / Owner</div>
          <div class="print-signature-title">Longdaycat.Co</div>
        </div>
        <div class="print-signature-box">
          <div class="print-signature-label">Dibuat oleh,</div>
          <div class="print-signature-line"></div>
          <div class="print-signature-name">Admin / Kasir</div>
          <div class="print-signature-title">Sistem Longdaycat</div>
        </div>
      </div>
      <div class="print-footer">
        Dokumen ini digenerate otomatis oleh sistem Longdaycat.Co — {{ printDate }}
      </div>
    </div>
  </AppLayout>
</template>

<style>
@media print {
  @page {
    size: A4 landscape;
    margin: 12mm 14mm;
  }

  html, body, #app, #app > div, #app > div > div, main {
    height: auto !important;
    min-height: 0 !important;
    max-height: none !important;
    overflow: visible !important;
    position: static !important;
    display: block !important;
    background-color: white !important;
    color: #0f172a !important;
    background: none !important;
    font-family:'Calibri','Arial', sans-serif !important;
  }

  /* Sembunyikan elemen UI */
  aside, header, nav, .no-print, button, a, select, .chart-container { display: none !important; }

  main { margin: 0 !important; padding: 0 !important; width: 100% !important; }

  /* Stats grid di print */
  .grid {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 6px !important;
    margin-bottom: 12px !important;
  }
  .grid > div {
    border: 1px solid #e2e8f0 !important;
    padding: 8px 10px !important;
    border-radius: 4px !important;
    background: #f8fafc !important;
    box-shadow: none !important;
    color: #0f172a !important;
  }

  /* Tabel utama */
  table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin-top: 0 !important;
    font-size: 10px !important;
  }

  thead tr {
    background: #1e40af !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  th {
    background-color: #1e40af !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 9px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 8px 10px !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    border: none !important;
  }

  td {
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 7px 10px !important;
    font-size: 10px !important;
    color: #1e293b !important;
    vertical-align: top !important;
  }

  /* Zebra stripes */
  tbody tr:nth-child(even) {
    background-color: #eff6ff !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  tbody tr:nth-child(odd) { background-color: #ffffff !important; }

  tr { page-break-inside: avoid !important; }

  /* Tanda tangan setelah tabel */
  .print-signature {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 40px !important;
    margin-top: 40px !important;
    padding-top: 16px !important;
    border-top: 1px solid #e2e8f0 !important;
  }
  .print-signature-box { text-align: center !important; }
  .print-signature-label { font-size: 10px !important; color: #64748b !important; }
  .print-signature-line { margin: 48px auto 4px !important; width: 160px !important; border-top: 1px solid #94a3b8 !important; }
  .print-signature-name { font-size: 10px !important; font-weight: 700 !important; color: #0f172a !important; }
  .print-signature-title { font-size: 9px !important; color: #94a3b8 !important; font-style: italic !important; }

  /* Footer */
  .print-footer {
    margin-top: 16px !important;
    border-top: 1px solid #1e40af !important;
    padding-top: 6px !important;
    text-align: center !important;
    font-size: 8px !important;
    color: #94a3b8 !important;
    font-style: italic !important;
  }
}
</style>
