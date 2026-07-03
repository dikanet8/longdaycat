<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, router, Link } from'@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import FrontendPagination from '@/Components/FrontendPagination.vue';

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
  setting: Object,
});

const formFilters = ref({
  start_date: props.filters?.start_date || (() => { const d = new Date(); d.setDate(1); return d.toISOString().split('T')[0]; })(),
  end_date: props.filters?.end_date || new Date().toISOString().split('T')[0],
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
  const startDate = new Date(formFilters.value.start_date);
  startDate.setHours(0, 0, 0, 0);
  const endDate = new Date(formFilters.value.end_date);
  endDate.setHours(23, 59, 59, 999);

  return (props.all_transactions || []).filter(trx => {
    const trxDate = new Date(trx.tanggal || trx.created_at);
    return trxDate >= startDate && trxDate <= endDate;
  });
});

const completedTransactions = computed(() => {
  return filteredTransactions.value.filter(t => t.status === 'selesai');
});

const reportStats = computed(() => {
  const total_transaksi = completedTransactions.value.length;
  const total_pendapatan = completedTransactions.value.reduce((acc, t) => acc + parseFloat(t.total_harga || 0), 0);
  const total_produk_terjual = completedTransactions.value.reduce((acc, t) => {
    return acc + (t.details || []).reduce((sum, d) => sum + parseInt(d.jumlah || 0), 0);
  }, 0);
  
  return { total_transaksi, total_pendapatan, total_produk_terjual };
});
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
  const startDate = new Date(formFilters.value.start_date);
  startDate.setHours(0, 0, 0, 0);
  const endDate = new Date(formFilters.value.end_date);
  endDate.setHours(23, 59, 59, 999);
  
  const labels = [];
  const data = [];

  const diffTime = Math.abs(endDate - startDate);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays <= 31) {
    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
      labels.push(`${d.getDate()} ${months[d.getMonth()].substring(0, 3)}`);
      const sum = props.all_transactions
        .filter(t => t.status === 'selesai' && new Date(t.tanggal || t.created_at).toDateString() === d.toDateString())
        .reduce((acc, t) => acc + parseFloat(t.total_harga || 0), 0);
      data.push(sum);
    }
  } else if (diffDays <= 366) {
    const startMonth = startDate.getMonth();
    const startYear = startDate.getFullYear();
    const endMonth = endDate.getMonth();
    const endYear = endDate.getFullYear();
    
    let currYear = startYear;
    let currMonth = startMonth;
    
    while (currYear < endYear || (currYear === endYear && currMonth <= endMonth)) {
      labels.push(`${months[currMonth].substring(0, 3)} ${currYear}`);
      const sum = props.all_transactions
        .filter(t => {
           if (t.status !== 'selesai') return false;
           const tDate = new Date(t.tanggal || t.created_at);
           return tDate.getMonth() === currMonth && tDate.getFullYear() === currYear;
        })
        .reduce((acc, t) => acc + parseFloat(t.total_harga || 0), 0);
      data.push(sum);
      currMonth++;
      if (currMonth > 11) {
        currMonth = 0;
        currYear++;
      }
    }
  } else {
    const startYear = startDate.getFullYear();
    const endYear = endDate.getFullYear();
    for (let y = startYear; y <= endYear; y++) {
      labels.push(y.toString());
      const sum = props.all_transactions
        .filter(t => t.status === 'selesai' && new Date(t.tanggal || t.created_at).getFullYear() === y)
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
  
  const total = Object.values(methodRevenues).reduce((acc, curr) => acc + curr, 0);
  const labelsWithNominal = Object.entries(methodRevenues).map(([method, value]) => {
    const formattedNominal = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
    return `${method} - ${formattedNominal}`;
  });

  return {
    labels: labelsWithNominal,
    datasets: [{
      data: Object.values(methodRevenues),
      backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
      borderWidth: 0,
      hoverOffset: 4
    }]
  };
});

const topProductsData = computed(() => {
  const products = {};
  completedTransactions.value.forEach(trx => {
    (trx.details || []).forEach(d => {
      const name = d.produk?.nama_produk || d.kode_produk;
      products[name] = (products[name] || 0) + parseInt(d.jumlah);
    });
  });

  const sorted = Object.entries(products).sort((a, b) => b[1] - a[1]).slice(0, 5);
  
  return {
    labels: sorted.map(i => i[0].length > 15 ? i[0].substring(0, 15) + '...' : i[0]),
    datasets: [{
      label: 'Terjual (Pcs)',
      data: sorted.map(i => i[1]),
      backgroundColor: '#f59e0b',
      borderRadius: 4,
      barThickness: 24
    }],
    rawTop: sorted
  };
});

const paymentMethodsData = computed(() => {
  const counts = {};
  const amounts = {};
  completedTransactions.value.forEach(trx => {
    const method = trx.metode_bayar || 'tunai';
    counts[method] = (counts[method] || 0) + 1;
    amounts[method] = (amounts[method] || 0) + parseFloat(trx.total_harga || 0);
  });
  return Object.keys(counts).map(k => ({
    method: k,
    count: counts[k],
    amount: amounts[k]
  })).sort((a, b) => b.count - a.count);
});

const topProductsOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(15, 23, 42, 0.9)',
      titleFont: { size: 14, weight: 'bold' },
      bodyFont: { size: 13 },
      padding: 12,
      cornerRadius: 12,
    }
  },
  scales: {
    x: { 
      grid: { display: true, color: '#e2e8f0' }, 
      ticks: { font: { size: 10, family: 'Calibri' }, stepSize: 1, precision: 0 } 
    },
    y: { grid: { display: false }, ticks: { font: { size: 11, family: 'Calibri', weight: 'bold' }, color: '#334155' } }
  }
};

const currentPage = ref(1);

watch(formFilters, () => {
  currentPage.value = 1;
}, { deep: true });

const recent_transactions = computed(() => {
  const perPage = parseInt(formFilters.value.per_page) || 10;
  const page = currentPage.value;
  const total = completedTransactions.value.length;
  
  const from = (page - 1) * perPage;
  const to = Math.min(from + perPage, total);
  
  return {
    data: completedTransactions.value.slice(from, to),
    from: total === 0 ? 0 : from + 1,
    to: to,
    total: total
  };
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
  const printElement = document.querySelector('.pdf-print-area');
  if (!printElement) return;
  
  // Capture canvas charts as images before printing
  const canvases = document.querySelectorAll('.chart-container canvas, canvas');
  if (canvases.length >= 3) {
    const paymentImg = printElement.querySelector('#print-chart-payment');
    const productsImg = printElement.querySelector('#print-chart-products');
    
    // index 0 is Tren Penjualan, 1 is Metode Pembayaran, 2 is Produk Terlaris
    if (paymentImg) paymentImg.src = canvases[1].toDataURL('image/png');
    if (productsImg) productsImg.src = canvases[2].toDataURL('image/png');
  }
  
  const printContent = printElement.innerHTML;
  
  const iframe = document.createElement('iframe');
  iframe.style.position = 'absolute';
  iframe.style.width = '0';
  iframe.style.height = '0';
  iframe.style.border = 'none';
  document.body.appendChild(iframe);
  
  const doc = iframe.contentWindow.document;
  doc.open();
  doc.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <base href="${window.location.origin}">
        <title>Cetak Laporan Penjualan</title>
        <style>
          @page { size: A4 portrait; margin: 12mm 14mm; }
          body { font-family: 'Calibri', 'Arial', sans-serif; background: white; color: #0f172a; padding: 0; margin: 0; }
          .print-header { border-top: 6px solid #1e40af; padding-top: 20px; margin-bottom: 8px; }
          table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 30px; }
          th { background-color: #1e40af !important; color: white !important; padding: 8px 10px; font-weight: 800; text-transform: uppercase; text-align: left; -webkit-print-color-adjust: exact; print-color-adjust: exact; border: none; }
          td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; color: #1e293b; }
          tr:nth-child(even) td { background-color: #eff6ff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
          tr { page-break-inside: avoid; }
        </style>
      </head>
      <body>
        ${printContent}
      </body>
    </html>
  `);
  doc.close();
  
  setTimeout(() => {
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
    setTimeout(() => {
      document.body.removeChild(iframe);
    }, 1000);
  }, 1000);
};

const showFabMenu = ref(false);

const getFilterPeriod = () => {
  try {
    const start = new Date(formFilters.value.start_date);
    const end = new Date(formFilters.value.end_date);
    const formatDate = (d) => `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    if (formFilters.value.start_date === formFilters.value.end_date) {
      return formatDate(start);
    }
    return `${formatDate(start)} - ${formatDate(end)}`;
  } catch (e) {
    return 'Periode Kustom';
  }
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
            label = label.split(' - ')[0] + ': ';
          }
          
          let value = context.parsed;

          if (value !== null) {
            label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
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
    <div class="pdf-print-area">
      <!-- Kop Surat -->
      <div class="print-header" style="border-top: 6px solid #1e40af; padding-top: 20px; margin-bottom: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
          <div style="display: flex; gap: 16px; align-items: center;">
            <img v-if="setting?.logo_toko" :src="setting.logo_toko" alt="Logo" style="width: 60px; height: 60px; object-fit: contain;" />
            <div>
              <div style="font-size: 28px; font-weight: 900; color: #1e40af; letter-spacing: -1px; text-transform: uppercase;">{{ setting?.nama_toko || 'LONGDAYCAT.CO' }}</div>
              <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Sistem Manajemen Inventori &amp; Penjualan</div>
              <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">{{ setting?.alamat_toko || 'Jl. Contoh No. 123, Kota Anda' }} | Telp: {{ setting?.telepon_toko || '(021) 0000-0000' }}</div>
            </div>
          </div>
          <div style="text-align: right;">
            <div style="font-size: 11px; font-weight: 700; color: #334155;">LAPORAN PENJUALAN</div>
            <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Periode: {{ getFilterPeriod() }}</div>
            <div style="font-size: 9px; color: #94a3b8; margin-top: 2px;">Dicetak: {{ printDate }}</div>
          </div>
        </div>
      </div>
      <div style="border-bottom: 2px solid #1e40af; margin-bottom: 16px;"></div>

      <!-- Laporan Statistik -->
      <div style="margin-bottom: 20px;">
        <div style="font-weight: bold; font-size: 14px; margin-bottom: 8px; color: #1e40af;">RINGKASAN STATISTIK</div>
        <table style="width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 16px;">
          <tr>
            <td style="padding: 8px; border: 1px solid #e2e8f0; background: #dbeafe; font-weight: bold; color: #1e40af;">Total Pendapatan</td>
            <td style="padding: 8px; border: 1px solid #e2e8f0; background: #dbeafe; font-weight: bold; text-align: right; color: #16a34a; font-size: 14px;">{{ formatPrice(reportStats.total_pendapatan) }}</td>
            <td style="padding: 8px; border: 1px solid #e2e8f0; background: #f0fdf4; font-weight: bold; color: #15803d;">Total Transaksi</td>
            <td style="padding: 8px; border: 1px solid #e2e8f0; background: #f0fdf4; font-weight: bold; text-align: right; color: #1d4ed8; font-size: 14px;">{{ reportStats.total_transaksi }}</td>
            <td style="padding: 8px; border: 1px solid #e2e8f0; background: #fefce8; font-weight: bold; color: #a16207;">Produk Terjual</td>
            <td style="padding: 8px; border: 1px solid #e2e8f0; background: #fefce8; font-weight: bold; text-align: right; color: #b45309; font-size: 14px;">{{ reportStats.total_produk_terjual }} pcs</td>
          </tr>
        </table>
        
        <div style="display: flex; gap: 20px; align-items: stretch; margin-bottom: 20px; page-break-inside: avoid;">
          <!-- Metode Pembayaran -->
          <div style="flex: 1; text-align: center; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; background: #f8fafc; display: flex; flex-direction: column;">
            <div style="font-weight: bold; font-size: 12px; margin-bottom: 12px; color: #334155;">Ringkasan Metode Pembayaran</div>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center;">
              <img id="print-chart-payment" style="width: 100%; height: 220px; object-fit: contain; display: block;" />
            </div>
          </div>

          <!-- 5 Produk Terlaris -->
          <div style="flex: 1.5; text-align: center; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; background: #f8fafc; display: flex; flex-direction: column;">
            <div style="font-weight: bold; font-size: 12px; margin-bottom: 12px; color: #334155;">5 Produk Terlaris</div>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center;">
              <img id="print-chart-products" style="width: 100%; height: 220px; object-fit: contain; display: block;" />
            </div>
          </div>
        </div>
      </div>

      <!-- Tabel Detail Transaksi -->
      <div style="font-weight: bold; font-size: 14px; margin-bottom: 8px; color: #1e40af; margin-top: 24px;">DAFTAR TRANSAKSI</div>
      <table style="width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 30px;">
        <thead>
          <tr style="background-color: #1e40af; color: white;">
            <th style="padding: 8px; text-align: center; border: 1px solid #1e40af;">No</th>
            <th style="padding: 8px; text-align: left; border: 1px solid #1e40af;">Tanggal</th>
            <th style="padding: 8px; text-align: left; border: 1px solid #1e40af;">Kode Transaksi</th>
            <th style="padding: 8px; text-align: left; border: 1px solid #1e40af;">Detail Item</th>
            <th style="padding: 8px; text-align: right; border: 1px solid #1e40af;">Total Harga</th>
            <th style="padding: 8px; text-align: center; border: 1px solid #1e40af;">Metode Pembayaran</th>
            <th style="padding: 8px; text-align: left; border: 1px solid #1e40af;">Kasir</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(trx, idx) in completedTransactions" :key="trx.id" :style="idx % 2 === 1 ? 'background-color: #eff6ff;' : ''">
            <td style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: center;">{{ idx + 1 }}</td>
            <td style="padding: 6px 8px; border: 1px solid #cbd5e1;">{{ formatDate(trx.created_at) }}</td>
            <td style="padding: 6px 8px; border: 1px solid #cbd5e1; font-weight: bold;">{{ trx.kode_transaksi }}</td>
            <td style="padding: 6px 8px; border: 1px solid #cbd5e1;">
              <div v-for="d in trx.details" :key="d.id" style="margin-bottom: 2px;">
                {{ d.produk?.nama_produk || d.kode_produk }} x{{ d.jumlah }}
              </div>
            </td>
            <td style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right; font-weight: bold; color: #1d4ed8;">{{ formatPrice(trx.total_harga) }}</td>
            <td style="padding: 6px 8px; border: 1px solid #cbd5e1; text-transform: uppercase; text-align: center;">{{ trx.metode_bayar }}</td>
            <td style="padding: 6px 8px; border: 1px solid #cbd5e1;">{{ trx.user?.name || 'Sistem' }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Tanda Tangan -->
      <div style="display: flex; justify-content: space-between; margin-top: 40px; page-break-inside: avoid;">
        <div style="text-align: center; width: 200px;">
          <div style="font-size: 12px; color: #64748b;">Mengetahui,</div>
          <div style="margin-top: 70px; border-top: 1px solid #94a3b8; padding-top: 4px;">
            <div style="font-weight: bold; font-size: 12px;">Pimpinan / Owner</div>
            <div style="font-size: 11px; color: #94a3b8; font-style: italic;">{{ setting?.nama_toko || 'Longdaycat.Co' }}</div>
          </div>
        </div>
        <div style="text-align: center; width: 200px;">
          <div style="font-size: 12px; color: #64748b;">Dibuat oleh,</div>
          <div style="margin-top: 70px; border-top: 1px solid #94a3b8; padding-top: 4px;">
            <div style="font-weight: bold; font-size: 12px;">Admin / Kasir</div>
            <div style="font-size: 11px; color: #94a3b8; font-style: italic;">Sistem Longdaycat</div>
          </div>
        </div>
      </div>
    </div>

    <div class="space-y-8 print-hidden">
      <!-- Page Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 print:hidden">
        <div>
          <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Laporan Penjualan</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-2 font-medium text-sm sm:text-base">Ringkasan performa dan tren transaksi bisnis Anda.</p>
        </div>
      </div>

      <!-- Middle Level: Control Bar (Filters & Export) -->
      <div class="flex flex-col lg:flex-row items-center justify-between gap-4 print:hidden">
        <!-- Filters -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full lg:w-auto">
          <div class="bg-white dark:bg-slate-900 grid grid-cols-2 sm:flex sm:items-center gap-2 sm:gap-0 rounded-lg p-1 border border-slate-200 dark:border-white/10 shadow-sm w-full sm:w-auto">
            <input type="date" v-model="formFilters.start_date" @change="applyFilter" class="w-full sm:w-auto bg-white dark:bg-slate-900 sm:bg-transparent sm:dark:bg-transparent border-none rounded-lg text-sm font-bold text-slate-700 dark:text-slate-300 focus:ring-0 py-2 sm:py-2 pl-3 sm:pl-4 shadow-sm sm:shadow-none cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition-colors" />
            <div class="hidden sm:flex items-center justify-center px-2 text-slate-400 font-bold">-</div>
            <input type="date" v-model="formFilters.end_date" @change="applyFilter" class="w-full sm:w-auto bg-white dark:bg-slate-900 sm:bg-transparent sm:dark:bg-transparent border-none rounded-lg text-sm font-bold text-slate-700 dark:text-slate-300 focus:ring-0 py-2 sm:py-2 pl-3 sm:pl-4 shadow-sm sm:shadow-none cursor-pointer hover:bg-white dark:hover:bg-slate-700 transition-colors" />
          </div>
        </div>

        <!-- Export Buttons (Desktop Only, Mobile uses FAB) -->
        <div class="hidden lg:flex items-center gap-3">
          <button @click="printReport" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-black transition-all active:scale-95">
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Cetak Laporan
          </button>
        </div>
      </div>



      <!-- Middle-Bottom Level: Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 print:grid-cols-2 print:break-inside-avoid mb-6">
        
        <!-- Visual Centric Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-2xl shadow-sm p-6 print:hidden">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Tren Penjualan</h2>
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-blue-500 shadow-sm shadow-blue-500/50"></span>
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Harga</span>
            </div>
          </div>
          <div class="h-[350px] sm:h-[400px] w-full relative">
            <Line :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Doughnut Chart (Payment Methods) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-2xl shadow-sm p-6 flex flex-col">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Metode Pembayaran</h2>
          </div>
          <div class="h-[250px] sm:h-[300px] w-full max-w-[320px] mx-auto relative my-auto">
             <Doughnut :data="doughnutData" :options="doughnutOptions" />
          </div>
        </div>

        <!-- Top Selling Items -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-2xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">5 Produk Terlaris</h2>
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-amber-500 shadow-sm shadow-amber-500/50"></span>
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Terjual (Pcs)</span>
            </div>
          </div>
          <div class="h-[250px] sm:h-[300px] w-full relative">
             <Bar :data="topProductsData" :options="topProductsOptions" />
          </div>
        </div>
      </div>

      <!-- Table Header (Outside Card like Products) -->
      <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-6 print:hidden">
        <div>
          <h3 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Riwayat Transaksi</h3>
          <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-sm">Data detail setiap transaksi pada periode yang dipilih.</p>
        </div>
      </div>

      <!-- Detailed Table Container -->
      <div class="bg-white dark:bg-slate-900 rounded-md overflow-hidden shadow-sm dark:shadow-2xl border border-slate-200 dark:border-white/5 print:hidden">
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

            <!-- Info -->
            <div class="flex items-center justify-between pt-1">
              <div>
                <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Total Harga</span>
                <span class="font-black text-emerald-600 dark:text-emerald-400 text-sm tracking-tight">{{ formatPrice(trx.total_harga) }}</span>
              </div>
              <div>
                <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-[9px] font-black uppercase tracking-wider rounded-md text-slate-600 dark:text-slate-400">{{ trx.metode_bayar }}</span>
              </div>
            </div>

            <div class="flex items-center gap-2 border-t border-slate-50 dark:border-white/5 pt-3">
              <Link :href="`/payments/${trx.id}`" class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md transition-colors active:scale-95" title="Detail">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Detail Transaksi
              </Link>
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
          <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-[0.2em] font-black">
              <tr>
                <th class="px-8 py-5">Tanggal</th>
                <th class="px-8 py-5">Kode Transaksi</th>
                <th class="px-8 py-5">Detail Item</th>
                <th class="px-8 py-5">Total Harga</th>
                <th class="px-8 py-5">Metode Pembayaran</th>
                <th class="px-8 py-5">Kasir</th>
                <th class="px-8 py-5 text-right no-print">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y-2 divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
              <tr v-for="trx in recent_transactions?.data || []" :key="trx.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                <td class="px-8 py-5 text-sm text-slate-600 dark:text-slate-400 font-semibold">{{ formatDate(trx.created_at) }}</td>
                <td class="px-8 py-5 font-black text-sm tracking-tight text-slate-900 dark:text-white">{{ trx.kode_transaksi }}</td>
                <td class="px-8 py-5">
                  <div class="space-y-1.5">
                    <div v-for="d in trx.details" :key="d.id" class="flex items-center gap-2">
                      <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                      <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ d.produk?.nama_produk || d.kode_produk }}</span>
                      <span class="text-xs font-black text-blue-600 dark:text-blue-400">x{{ d.jumlah }}</span>
                      <span class="text-[10px] ml-auto font-medium px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 text-slate-500 shadow-sm border border-slate-200 dark:border-white/10">
                        {{ d.produk?.ukuran || '-' }} / {{ d.produk?.warna || '-' }}
                      </span>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-5 font-black text-emerald-600 dark:text-emerald-400 text-sm tracking-tight">{{ formatPrice(trx.total_harga) }}</td>
                <td class="px-8 py-5">
                  <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-xs font-black uppercase tracking-wider rounded-lg text-slate-700 dark:text-slate-300 shadow-sm">{{ trx.metode_bayar }}</span>
                </td>
                <td class="px-8 py-5">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-[10px]">
                      {{ (trx.user?.name || 'S').charAt(0).toUpperCase() }}
                    </div>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300 capitalize">{{ trx.user?.name || 'Sistem' }}</span>
                  </div>
                </td>
                <td class="px-8 py-5 text-right no-print">
                  <div class="flex items-center justify-end gap-2">
                    <Link :href="`/payments/${trx.id}`" class="p-2 text-slate-400 hover:text-blue-500 transition-colors" title="Detail Transaksi">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </Link>
                  </div>
                </td>
              </tr>
              <tr v-if="!recent_transactions?.data || recent_transactions.data.length === 0">
                <td colspan="7" class="px-8 py-20 text-center">
                  <div class="flex flex-col items-center justify-center">
                    <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center mb-4">
                      <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                      </svg>
                    </div>
                    <h3 class="text-base font-black text-slate-800 dark:text-white mb-1">Tidak Ada Transaksi</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Belum ada data transaksi pada periode yang dipilih.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination & Per Page -->
        <div class="px-6 py-4 border-t-2 border-slate-100 dark:border-white/5 flex flex-col md:flex-row items-center justify-between gap-4 bg-slate-50/50 dark:bg-white/[0.02] no-print">
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
            <FrontendPagination :total="recent_transactions.total" :perPage="parseInt(formFilters.per_page) || 10" v-model="currentPage" />
          </div>
        </div>
      </div>
    </div>

    <!-- Formal Print Table (PDF only, positioned AFTER charts) -->
    <div class="hidden print:block print-area" style="margin-top: 32px !important;">
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

    <!-- Mobile FAB (PDF Print) -->
    <div class="lg:hidden fixed bottom-24 right-6 z-50 flex flex-col items-end gap-3 print:hidden">
      <button 
        @click="printReport"
        class="w-14 h-14 bg-red-500 hover:bg-red-600 text-white rounded-full shadow-xl shadow-red-500/30 flex items-center justify-center transition-all focus:outline-none focus:ring-4 focus:ring-red-500/30 hover:scale-105 active:scale-95 z-50 relative"
        title="Cetak Laporan"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
      </button>
    </div>

  </AppLayout>
</template>

<style>
.pdf-print-area {
  display: none;
}
</style>
