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
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from'chart.js';
import { Line, Bar, Doughnut } from'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
  all_transactions: Array,
  filters: Object,
});

const formFilters = ref({
  type: props.filters?.type || 'harian',
  day: props.filters?.day || new Date().getDate(),
  month: props.filters?.month || new Date().getMonth() + 1,
  year: props.filters?.year || new Date().getFullYear(),
  per_page: props.filters?.per_page || 10
});

const months = [
 'Januari','Februari','Maret','April','Mei','Juni',
 'Juli','Agustus','September','Oktober','November','Desember'
];

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits: 0 }).format(price || 0);
};

const filteredTransactions = computed(() => {
  const type = formFilters.value.type;
  const day = parseInt(formFilters.value.day);
  const month = parseInt(formFilters.value.month);
  const year = parseInt(formFilters.value.year);

  return (props.all_transactions || []).filter(trx => {
    const trxDate = new Date(trx.tanggal || trx.created_at);
    if (type === 'harian' && day) {
      return trxDate.getFullYear() === year && (trxDate.getMonth() + 1) === month && trxDate.getDate() === day;
    } else if (type === 'bulanan') {
      return trxDate.getFullYear() === year && (trxDate.getMonth() + 1) === month;
    } else if (type === 'tahunan') {
      return trxDate.getFullYear() === year;
    }
    return true;
  });
});

const completedTransactions = computed(() => filteredTransactions.value.filter(t => t.status === 'selesai'));
const cancelledTransactions = computed(() => filteredTransactions.value.filter(t => t.status === 'dibatalkan'));

const totalPendapatan = computed(() => completedTransactions.value.reduce((sum, trx) => sum + parseFloat(trx.total_harga || 0), 0));
const totalTransaksi = computed(() => completedTransactions.value.length);
const rataRataTransaksi = computed(() => totalTransaksi.value > 0 ? totalPendapatan.value / totalTransaksi.value : 0);

const metodeFavorit = computed(() => {
  const counts = {};
  completedTransactions.value.forEach(trx => {
    counts[trx.metode_bayar] = (counts[trx.metode_bayar] || 0) + 1;
  });
  const max = Object.entries(counts).sort((a, b) => b[1] - a[1])[0];
  return max ? max[0] : '-';
});

const stats = computed(() => [
  { name:'Total Pendapatan', value: formatPrice(totalPendapatan.value), rawValue: totalPendapatan.value, icon:'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', color:'text-emerald-500' },
  { name:'Rata-rata Transaksi', value: formatPrice(rataRataTransaksi.value), rawValue: rataRataTransaksi.value, icon:'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', color:'text-blue-500' },
  { name:'Metode Terfavorit', value: metodeFavorit.value, rawValue: metodeFavorit.value, icon:'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', color:'text-amber-500' },
  { name:'Transaksi Batal', value: cancelledTransactions.value.length +' Trx', rawValue: cancelledTransactions.value.length, icon:'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', color:'text-red-500' },
]);

const chartData = computed(() => {
  const type = formFilters.value.type;
  const month = parseInt(formFilters.value.month);
  const year = parseInt(formFilters.value.year);
  const labels = [];
  const data = [];

  if (type === 'tahunan') {
    for (let m = 1; m <= 12; m++) {
      labels.push(months[m-1].substring(0, 3));
      const sum = completedTransactions.value
        .filter(t => new Date(t.tanggal || t.created_at).getMonth() + 1 === m)
        .reduce((acc, t) => acc + parseFloat(t.total_harga || 0), 0);
      data.push(sum);
    }
  } else if (type === 'bulanan') {
    const daysInMonth = new Date(year, month, 0).getDate();
    for (let d = 1; d <= daysInMonth; d++) {
      labels.push(d.toString());
      const sum = completedTransactions.value
        .filter(t => new Date(t.tanggal || t.created_at).getDate() === d)
        .reduce((acc, t) => acc + parseFloat(t.total_harga || 0), 0);
      data.push(sum);
    }
  } else {
    // Harian - last 7 days from selected date
    const day = parseInt(formFilters.value.day);
    const baseDate = new Date(year, month - 1, day || new Date().getDate());
    for (let i = 6; i >= 0; i--) {
      const d = new Date(baseDate);
      d.setDate(baseDate.getDate() - i);
      labels.push(`${d.getDate()} ${months[d.getMonth()].substring(0, 3)}`);
      const sum = props.all_transactions
        .filter(t => t.status === 'selesai' && new Date(t.tanggal || t.created_at).toDateString() === d.toDateString())
        .reduce((acc, t) => acc + parseFloat(t.total_harga || 0), 0);
      data.push(sum);
    }
  }

  return {
    labels,
    datasets: [{
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
      data
    }]
  };
});

const doughnutData = computed(() => {
  const methodRevenues = {};
  completedTransactions.value.forEach(trx => {
    const method = trx.metode_bayar.charAt(0).toUpperCase() + trx.metode_bayar.slice(1);
    methodRevenues[method] = (methodRevenues[method] || 0) + parseFloat(trx.total_harga || 0);
  });
  
  return {
    labels: Object.keys(methodRevenues),
    datasets: [{
      data: Object.values(methodRevenues),
      backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
      borderWidth: 0,
      hoverOffset: 4
    }]
  };
});

const recent_transactions = computed(() => {
  return { data: completedTransactions.value.slice(0, formFilters.value.per_page) };
});

const applyFilter = () => {
  // No-op for frontend filtering so select @change works seamlessly
};

const printChartData = computed(() => ({
  labels: chartData.value.labels,
  datasets: [{
    label: 'Pendapatan',
    backgroundColor: '#1e40af',
    borderColor: '#1e40af',
    borderWidth: 1,
    data: chartData.value.datasets[0].data
  }]
}));

const printReport = () => {
  window.print();
};

const showFabMenu = ref(false);
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
    if (formFilters.value.type === 'harian' && formFilters.value.day) {
      return `${formFilters.value.day} ${months[formFilters.value.month - 1]} ${formFilters.value.year}`;
    }
  } catch (e) {
    console.error(e);
  }
  
  if (formFilters.value.type === 'bulanan' || formFilters.value.type === 'harian') {
    const monthIndex = parseInt(formFilters.value.month);
    if (!isNaN(monthIndex) && monthIndex >= 1 && monthIndex <= 12 && formFilters.value.year) {
      return `${months[monthIndex - 1]} ${formFilters.value.year}`;
    }
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
        min: 0,
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

const printChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  animation: false,
  plugins: {
    legend: { display: false },
    tooltip: { enabled: false }
  },
  scales: {
    y: {
      grid: { display: true, color: '#e2e8f0' },
      ticks: { 
        display: true, 
        color: '#0f172a', 
        font: { size: 10, family: 'Calibri' }, 
        beginAtZero: true,
        min: 0,
        callback: function(value) {
          if (value >= 1000000000) {
            return 'Rp' + (value / 1000000000).toFixed(1) + ' M';
          } else if (value >= 1000000) {
            return 'Rp' + (value / 1000000).toFixed(1) + ' Jt';
          } else if (value >= 1000) {
            return 'Rp' + (value / 1000).toFixed(0) + 'K';
          }
          return 'Rp' + value;
        }
      }
    },
    x: {
      grid: { display: false },
      ticks: { color: '#0f172a', font: { size: 10, family: 'Calibri' } }
    }
  }
};



const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: '#64748b',
        font: { size: 12, weight: 'bold' },
        padding: 20,
        usePointStyle: true,
        pointStyle: 'circle'
      }
    },
    tooltip: {
      backgroundColor: 'rgba(15, 23, 42, 0.9)',
      titleFont: { size: 14, weight: 'bold' },
      bodyFont: { size: 13 },
      padding: 12,
      cornerRadius: 12,
      callbacks: {
        label: function(context) {
          let label = context.label || '';
          if (label) {
            label += ': ';
          }
          
          let total = context.dataset.data.reduce((acc, curr) => acc + curr, 0);
          let value = context.parsed;
          let percentage = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';

          if (value !== null) {
            label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value) + ' (' + percentage + ')';
          }
          return label;
        }
      }
    }
  },
  cutout: '75%'
};

const formatDate = (date) => {
  const d = new Date(date);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
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
  if (formFilters.value.type) params.append('type', formFilters.value.type);
  if (formFilters.value.day) params.append('day', formFilters.value.day);
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

      <!-- Formal Print Table -->
      <table>
        <thead>
          <tr>
            <th style="width: 5%; text-align: center;">No</th>
            <th style="width: 15%; text-align: left;">Tanggal</th>
            <th style="width: 15%; text-align: left;">Kode Transaksi</th>
            <th style="width: 30%; text-align: left;">Detail Item</th>
            <th style="width: 15%; text-align: right;">Total Harga</th>
            <th style="width: 10%; text-align: center;">Metode</th>
            <th style="width: 10%; text-align: left;">Kasir</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(trx, idx) in recent_transactions?.data || []" :key="'print-'+trx.id">
            <td style="text-align: center;">{{ idx + 1 }}</td>
            <td>{{ formatDate(trx.created_at) }}</td>
            <td style="font-weight: bold;">{{ trx.kode_transaksi }}</td>
            <td>
              <div v-for="d in trx.details" :key="'print-d-'+d.id">
                &bull; {{ d.produk?.nama_produk || d.kode_produk }} x{{ d.jumlah }}
              </div>
            </td>
            <td style="text-align: right; font-weight: bold;">{{ formatPrice(trx.total_harga) }}</td>
            <td style="text-align: center; text-transform: uppercase;">{{ trx.metode_bayar }}</td>
            <td>{{ trx.user?.name || 'Sistem' }}</td>
          </tr>
          <tr v-if="!recent_transactions?.data || recent_transactions.data.length === 0">
            <td colspan="7" style="text-align: center; font-style: italic; color: #64748b;">Tidak ada transaksi pada periode ini</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="space-y-8">
      <!-- Page Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 no-print">
        <div>
          <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Laporan Penjualan</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-2 font-medium text-sm sm:text-base">Ringkasan performa dan tren transaksi bisnis Anda.</p>
        </div>
      </div>

      <!-- Middle Level: Control Bar (Filters & Export) -->
      <div class="flex flex-col lg:flex-row items-center justify-between gap-4 p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-white/5 shadow-sm no-print">
        <!-- Filters -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full lg:w-auto">
          <div class="bg-slate-50 dark:bg-slate-800 grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-0 rounded-xl p-2 sm:p-1 shadow-inner border border-slate-100 dark:border-white/5 w-full sm:w-auto">
            <select v-model="formFilters.type" @change="applyFilter" class="w-full sm:w-auto bg-white dark:bg-slate-900 sm:bg-transparent sm:dark:bg-transparent border-none rounded-lg text-sm font-bold text-slate-700 dark:text-slate-300 focus:ring-0 py-2 sm:py-2 pl-3 sm:pl-4 pr-8 shadow-sm sm:shadow-none cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition-colors">
              <option value="harian">Harian</option>
              <option value="bulanan">Bulanan</option>
              <option value="tahunan">Tahunan</option>
            </select>
            <div class="hidden sm:block h-6 w-[1px] bg-slate-200 dark:bg-white/10 shrink-0 mx-1" v-if="formFilters.type !== 'tahunan'"></div>
            <select v-model="formFilters.day" @change="applyFilter" v-if="formFilters.type === 'harian'" class="w-full sm:w-auto bg-white dark:bg-slate-900 sm:bg-transparent sm:dark:bg-transparent border-none rounded-lg text-sm font-bold text-slate-700 dark:text-slate-300 focus:ring-0 py-2 pl-3 sm:pl-4 pr-8 shadow-sm sm:shadow-none cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition-colors">
              <option value="">Tanggal</option>
              <option v-for="d in 31" :key="d" :value="d">{{ d }}</option>
            </select>
            <div class="hidden sm:block h-6 w-[1px] bg-slate-200 dark:bg-white/10 shrink-0 mx-1" v-if="formFilters.type === 'harian'"></div>
            <select v-model="formFilters.month" @change="applyFilter" v-if="formFilters.type !== 'tahunan'" class="w-full sm:w-auto bg-white dark:bg-slate-900 sm:bg-transparent sm:dark:bg-transparent border-none rounded-lg text-sm font-bold text-slate-700 dark:text-slate-300 focus:ring-0 py-2 pl-3 sm:pl-4 pr-8 shadow-sm sm:shadow-none cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition-colors">
              <option v-for="(m, i) in months" :key="m" :value="i+1">{{ m }}</option>
            </select>
            <div class="hidden sm:block h-6 w-[1px] bg-slate-200 dark:bg-white/10 shrink-0 mx-1"></div>
            <select v-model="formFilters.year" @change="applyFilter" class="w-full sm:w-auto bg-white dark:bg-slate-900 sm:bg-transparent sm:dark:bg-transparent border-none rounded-lg text-sm font-bold text-slate-700 dark:text-slate-300 focus:ring-0 py-2 pl-3 sm:pl-4 pr-8 shadow-sm sm:shadow-none cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition-colors">
              <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
        </div>

        <!-- Export Buttons (Desktop Only, Mobile uses FAB) -->
        <div class="hidden lg:flex items-center gap-3">
          <button @click="printReport" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-black transition-all active:scale-95">
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            PDF
          </button>
          <button @click="exportWord" :disabled="isExportingWord" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 disabled:opacity-60 rounded-xl text-sm font-black transition-all active:scale-95">
            <svg v-if="!isExportingWord" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <svg v-else class="w-4 h-4 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            {{ isExportingWord ? '...' : 'Word' }}
          </button>
          <a :href="getExportUrl()" class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white rounded-xl text-sm font-black shadow-lg shadow-emerald-500/30 transition-all hover:-translate-y-0.5 active:scale-95 active:translate-y-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Excel
          </a>
        </div>
      </div>



      <!-- Middle-Bottom Level: Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 no-print">
        
        <!-- Visual Centric Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-2xl overflow-hidden shadow-sm p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Tren Penjualan</h2>
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-blue-500 shadow-sm shadow-blue-500/50"></span>
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Harga</span>
            </div>
          </div>
          <div class="h-[350px] sm:h-[400px] w-full">
            <Line :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Doughnut Chart (Payment Methods) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-2xl overflow-hidden shadow-sm p-6 flex flex-col">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Metode Pembayaran</h2>
          </div>
          <div class="h-[350px] sm:h-[400px] w-full relative flex items-center justify-center pb-8">
             <Doughnut :data="doughnutData" :options="doughnutOptions" />
          </div>
        </div>
      </div>

      <!-- Detailed Table -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm print:hidden">
        <div class="p-6 border-b border-slate-100 dark:border-white/5">
          <h3 class="font-black text-xl text-slate-900 dark:text-white tracking-tight">Riwayat Transaksi</h3>
          <p class="text-xs font-medium text-slate-500 mt-1">Data detail setiap transaksi pada periode yang dipilih.</p>
        </div>
        <!-- Mobile Card List (Hidden on Desktop) -->
        <div class="block md:hidden divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="trx in recent_transactions?.data || []" :key="trx.id" class="p-5 space-y-4 hover:bg-slate-50 dark:hover:bg-white/[0.01] transition-colors">
            <div class="flex items-center justify-between">
              <div class="inline-flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-500">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm tracking-tight">{{ trx.kode_transaksi }}</span>
              </div>
              <span class="text-[10px] text-slate-500 font-semibold">{{ formatDate(trx.created_at) }}</span>
            </div>

            <!-- Item Details -->
            <div class="space-y-2 bg-slate-50/50 dark:bg-white/5 p-3.5 rounded-xl border border-slate-100 dark:border-white/5">
              <div v-for="d in trx.details" :key="d.id" class="flex flex-col gap-1">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ d.produk?.nama_produk || d.kode_produk }}</span>
                  <span class="text-xs font-black text-blue-600 dark:text-blue-400">x{{ d.jumlah }}</span>
                </div>
                <span class="text-[10px] font-medium px-2 py-0.5 w-fit rounded-md bg-white dark:bg-slate-800 text-slate-500 shadow-sm border border-slate-200 dark:border-white/10">
                  {{ d.produk?.ukuran || '-' }} / {{ d.produk?.warna || '-' }}
                </span>
              </div>
            </div>

            <!-- Info & Action -->
            <div class="flex items-center justify-between pt-1">
              <div>
                <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Total Harga</span>
                <span class="font-black text-emerald-600 dark:text-emerald-400 text-sm tracking-tight">{{ formatPrice(trx.total_harga) }}</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-[9px] font-black uppercase tracking-wider rounded-md text-slate-600 dark:text-slate-400">{{ trx.metode_bayar }}</span>
                <Link :href="`/payments/${trx.id}`" class="px-4 py-1.5 bg-blue-600 text-white font-black text-[10px] uppercase tracking-wider rounded-lg shadow-md shadow-blue-500/30 hover:bg-blue-700 active:scale-95 transition-all">Detail</Link>
              </div>
            </div>
          </div>

          <div v-if="!recent_transactions?.data || recent_transactions.data.length === 0" class="p-10 text-center text-xs text-slate-400 font-bold uppercase tracking-wider flex flex-col items-center justify-center gap-3">
            <svg class="w-10 h-10 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <span>Tidak ada transaksi pada periode ini</span>
          </div>
        </div>

        <!-- Desktop Table View (Hidden on Mobile) -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/80 dark:bg-slate-800/50 backdrop-blur-sm border-b border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
              <tr>
                <th class="px-6 py-4">Tanggal</th>
                <th class="px-6 py-4">Kode Transaksi</th>
                <th class="px-6 py-4">Detail Item</th>
                <th class="px-6 py-4">Total Harga</th>
                <th class="px-6 py-4">Metode Pembayaran</th>
                <th class="px-6 py-4">Kasir</th>
                <th class="px-6 py-4 text-right no-print">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
              <tr v-for="trx in recent_transactions?.data || []" :key="trx.id" class="hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors group">
                <td class="px-6 py-5 text-sm text-slate-600 dark:text-slate-400 font-semibold">{{ formatDate(trx.created_at) }}</td>
                <td class="px-6 py-5">
                  <div class="inline-flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <span class="font-black text-slate-900 dark:text-white text-sm tracking-tight">{{ trx.kode_transaksi }}</span>
                  </div>
                </td>
                <td class="px-6 py-5">
                  <div class="space-y-1.5">
                    <div v-for="d in trx.details" :key="d.id" class="flex items-center gap-2">
                      <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                      <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ d.produk?.nama_produk || d.kode_produk }}</span>
                      <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        {{ d.produk?.ukuran || '-' }} / {{ d.produk?.warna || '-' }}
                      </span>
                      <span class="text-sm font-black text-blue-600 dark:text-blue-400">x{{ d.jumlah }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-5 font-black text-emerald-600 dark:text-emerald-400 text-sm tracking-tight">{{ formatPrice(trx.total_harga) }}</td>
                <td class="px-6 py-5">
                  <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-xs font-black uppercase tracking-wider rounded-lg text-slate-700 dark:text-slate-300 shadow-sm">{{ trx.metode_bayar }}</span>
                </td>
                <td class="px-6 py-5">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-[10px]">
                      {{ (trx.user?.name || 'S').charAt(0).toUpperCase() }}
                    </div>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300 capitalize">{{ trx.user?.name || 'Sistem' }}</span>
                  </div>
                </td>
                <td class="px-6 py-5 text-right no-print">
                  <Link :href="`/payments/${trx.id}`" class="inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-xl text-blue-600 dark:text-blue-400 font-black text-xs uppercase tracking-wider hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-200 dark:hover:border-blue-800 transition-all shadow-sm active:scale-95">Detail</Link>
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

        <!-- Pagination & Per Page -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 flex flex-col lg:flex-row items-center justify-between gap-6 bg-slate-50/30 dark:bg-white/[0.01] no-print">
          <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-2 order-2 lg:order-1">
            <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
              Menampilkan <span class="font-bold text-slate-900 dark:text-white">{{ recent_transactions?.from || 0 }}-{{ recent_transactions?.to || 0 }}</span> dari <span class="font-bold text-slate-900 dark:text-white">{{ recent_transactions?.total || 0 }}</span> transaksi
            </p>
            <div class="flex items-center gap-2 border-l border-slate-200 dark:border-white/10 pl-4 h-5">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
              <select v-model="formFilters.per_page" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-700 dark:text-slate-300 focus:ring-0 cursor-pointer min-w-[40px]">
                <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n" class="bg-white dark:bg-slate-900 font-sans font-normal">{{ n }}</option>
              </select>
            </div>
          </div>
          <div class="order-1 lg:order-2 w-full lg:w-auto flex justify-center">
            <Pagination :links="recent_transactions?.links || []" />
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

    <!-- Floating Action Button for Export (Mobile Only) -->
    <div class="fixed bottom-24 right-6 lg:right-10 z-[9999] flex md:hidden flex-col items-end gap-3 no-print">
      <!-- Backdrop for closing when clicked outside -->
      <div v-if="showFabMenu" @click="showFabMenu = false" class="fixed inset-0 z-40 bg-transparent"></div>

      <!-- Menu Items -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-4 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-4 scale-95"
      >
        <div v-if="showFabMenu" class="flex flex-col gap-3 mb-2 items-end z-50 relative">
          <!-- PDF -->
          <button 
            @click="printReport(); showFabMenu = false" 
            class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-full shadow-lg border border-slate-200 dark:border-slate-700 transition-all group"
          >
            <span class="text-sm font-bold">Cetak PDF</span>
            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            </div>
          </button>

          <!-- Word -->
          <button 
            @click="exportWord(); showFabMenu = false"
            :disabled="isExportingWord"
            class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-full shadow-lg border border-slate-200 dark:border-slate-700 transition-all disabled:opacity-50 group"
          >
            <span class="text-sm font-bold">{{ isExportingWord ? 'Mengekspor...' : 'Export Word' }}</span>
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
          </button>

          <!-- Excel -->
          <a 
            :href="getExportUrl()" 
            @click="setTimeout(() => showFabMenu = false, 300)"
            class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-full shadow-lg border border-slate-200 dark:border-slate-700 transition-all group"
          >
            <span class="text-sm font-bold">Export Excel</span>
            <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
          </a>
        </div>
      </Transition>

      <!-- Main FAB Button -->
      <button 
        @click="showFabMenu = !showFabMenu"
        class="w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-xl shadow-blue-600/30 flex items-center justify-center transition-all focus:outline-none focus:ring-4 focus:ring-blue-600/30 hover:scale-105 active:scale-95 z-50 relative"
        title="Export Laporan"
      >
        <svg 
          class="w-6 h-6 transition-transform duration-300"
          :class="{ 'rotate-45': showFabMenu }"
          fill="none" stroke="currentColor" viewBox="0 0 24 24"
        >
          <path v-if="!showFabMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </div>

  </AppLayout>
</template>

<style>
@media print {
  @page {
    size: A4 portrait;
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
