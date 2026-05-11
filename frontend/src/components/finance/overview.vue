<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/axios';

const router = useRouter();

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

// Fungsi URL yang kebal error & seragam dengan modul lain
const getFileUrl = (path: string | null | number | undefined): string => {
  if (!path || path === "0" || path === 0) return '';
  if (typeof path === 'string' && (path.startsWith('data:') || path.startsWith('http'))) return path;
  
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'; 
  const baseUrl = apiUrl.replace('/api', '');
  
  let cleanPath = String(path).startsWith('/') ? String(path).substring(1) : String(path);
  return cleanPath.toLowerCase().startsWith('uploads/') ? `${baseUrl}/${cleanPath}` : `${baseUrl}/uploads/${cleanPath}`;
};

// Fungsi Helper untuk handle Error Image Fallback
const handleImageError = (event: Event) => {
  const target = event.target as HTMLImageElement;
  if (target) {
    target.src = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/14/Kemhan_Logo.png/600px-Kemhan_Logo.png';
  }
};

const overviewStats = ref({
  total_revenue: 0, total_expense: 0, total_receivable: 0, net_profit: 0, active_projects_count: 0, pt_performance: [] as any[]
});
const recentTransactions = ref<any[]>([]);

const maxPtRevenue = computed(() => {
  if (!overviewStats.value.pt_performance || overviewStats.value.pt_performance.length === 0) return 1;
  return Math.max(...overviewStats.value.pt_performance.map(pt => pt.revenue));
});

const fetchOverviewData = async () => {
  try {
    const [resStats, resTrx] = await Promise.all([
      api.get('/finance/consolidated?pt_id=all'),
      api.get('/finance/transactions')
    ]);
    overviewStats.value = resStats.data;
    recentTransactions.value = resTrx.data.slice(0, 5); 
  } catch (error) {
    console.error("Gagal load Overview Data", error);
  }
};

onMounted(() => { fetchOverviewData(); });
</script>

<template>
  <div class="animate-in fade-in duration-700 space-y-6 text-slate-800 font-sans">
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-linear-to-br from-emerald-500 to-emerald-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-emerald-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
        <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Total Pendapatan</p>
        <h3 class="text-2xl font-black tracking-tighter">{{ formatCurrency(overviewStats.total_revenue) }}</h3>
        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full"><i class="fas fa-arrow-trend-up"></i> Uang Masuk</div>
        <i class="fas fa-wallet absolute right-8 bottom-8 text-5xl opacity-20 group-hover:-translate-y-2 transition-transform duration-500"></i>
      </div>
      
      <div class="bg-linear-to-br from-rose-500 to-rose-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-rose-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
        <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Total Pengeluaran</p>
        <h3 class="text-2xl font-black tracking-tighter">{{ formatCurrency(overviewStats.total_expense) }}</h3>
        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full"><i class="fas fa-arrow-trend-down"></i> Operasional & Belanja</div>
        <i class="fas fa-file-invoice-dollar absolute right-8 bottom-8 text-5xl opacity-20 group-hover:-translate-y-2 transition-transform duration-500"></i>
      </div>
      
      <div class="bg-linear-to-br from-amber-400 to-amber-500 rounded-[2.5rem] p-8 text-white shadow-xl shadow-amber-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
        <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Piutang (Belum Cair)</p>
        <h3 class="text-2xl font-black tracking-tighter">{{ formatCurrency(overviewStats.total_receivable) }}</h3>
        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full"><i class="fas fa-clock"></i> Invoice Menunggu</div>
        <i class="fas fa-hand-holding-usd absolute right-8 bottom-8 text-5xl opacity-20 group-hover:-translate-y-2 transition-transform duration-500"></i>
      </div>
      
      <div class="bg-linear-to-br from-indigo-600 to-slate-900 rounded-[2.5rem] p-8 text-white shadow-xl shadow-indigo-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
        <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Net Profit Group</p>
        <h3 class="text-2xl font-black tracking-tighter" :class="overviewStats.net_profit < 0 ? 'text-rose-300' : ''">{{ formatCurrency(overviewStats.net_profit) }}</h3>
        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full"><i class="fas fa-scale-balanced"></i> {{ overviewStats.active_projects_count }} Proyek Aktif</div>
        <i class="fas fa-vault absolute right-8 bottom-8 text-5xl opacity-20 group-hover:-translate-y-2 transition-transform duration-500"></i>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <div class="lg:col-span-7 bg-white rounded-[3rem] p-8 shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-8">
          <div>
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Kinerja Entitas (PT)</h3>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Perbandingan Pendapatan & Pengeluaran Aktual</p>
          </div>
          <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center shadow-inner"><i class="fas fa-chart-bar text-xl"></i></div>
        </div>

        <div class="space-y-6">
          <div v-if="overviewStats.pt_performance.length === 0" class="py-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">Belum ada data kinerja entitas</div>
          
          <div v-for="pt in overviewStats.pt_performance" :key="pt.id" class="space-y-2 group">
            <div class="flex justify-between items-end">
              <span class="text-[11px] font-black text-slate-700 uppercase tracking-tight group-hover:text-indigo-600 transition-colors"><i class="fas fa-building mr-1 text-slate-300"></i> {{ pt.name }}</span>
              <span class="text-[10px] font-black" :class="pt.margin >= 0 ? 'text-emerald-500' : 'text-rose-500'">{{ pt.margin }}% Margin</span>
            </div>
            
            <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
              <div class="h-full bg-emerald-500 transition-all duration-1000 relative" :style="{ width: `${(pt.revenue / maxPtRevenue) * 100}%` }">
                 <div class="absolute inset-0 bg-white/20 w-full h-full"></div>
              </div>
              <div v-if="pt.expense > 0" class="h-full bg-rose-500 transition-all duration-1000 opacity-80" :style="{ width: `${(pt.expense / maxPtRevenue) * 100}%`, marginLeft: `-${(pt.expense / maxPtRevenue) * 100}%` }"></div>
            </div>
            
            <div class="flex justify-between text-[8px] font-black uppercase text-slate-400">
              <span>Masuk: <span class="text-emerald-500">{{ formatCurrency(pt.revenue) }}</span></span>
              <span>Keluar: <span class="text-rose-500">{{ formatCurrency(pt.expense) }}</span></span>
            </div>
          </div>
        </div>
      </div>

      <div class="lg:col-span-5 bg-white rounded-[3rem] p-8 shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-8">
          <div>
             <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Aktivitas Terkini</h3>
             <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">5 Transaksi Keuangan Terakhir</p>
          </div>
          <button @click="router.push('/finance?tab=transaksi')" class="text-[9px] font-black text-indigo-500 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100 uppercase transition-colors">Lihat Semua</button>
        </div>
        
        <div class="space-y-4">
          <div v-if="recentTransactions.length === 0" class="py-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest border-2 border-dashed rounded-2xl">Belum ada transaksi</div>
          
          <div v-for="trx in recentTransactions" :key="trx.id" @click="trx.project_id ? router.push('/projects/' + trx.project_id) : null" class="flex items-center gap-4 p-3 rounded-2xl bg-slate-50 hover:bg-white transition-all border border-slate-100 hover:border-indigo-200 hover:shadow-md" :class="trx.project_id ? 'cursor-pointer' : 'cursor-default'">
            
            <div class="w-12 h-12 rounded-full border border-slate-200 p-0.5 bg-white overflow-hidden flex shrink-0 items-center justify-center shadow-sm">
                <img v-if="trx.project_logo" 
                     :src="getFileUrl(trx.project_logo)" 
                     @error="handleImageError"
                     class="w-full h-full object-cover rounded-full bg-white p-0.5">
                <img v-else src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/14/Kemhan_Logo.png/600px-Kemhan_Logo.png" class="w-full h-full object-cover rounded-full bg-white p-1" alt="Kemhan Logo">
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-[10px] font-black text-slate-700 uppercase truncate max-w-32.5 group-hover:text-indigo-600">{{ trx.description || trx.project_name || 'General Transaction' }}</p>
                <span v-if="trx.type === 'inflow'" class="w-4 h-4 bg-emerald-100 text-emerald-600 flex items-center justify-center rounded text-[8px]"><i class="fas fa-arrow-down"></i></span>
                <span v-else class="w-4 h-4 bg-rose-100 text-rose-600 flex items-center justify-center rounded text-[8px]"><i class="fas fa-arrow-up"></i></span>
              </div>
              <div class="flex items-center gap-2 mt-1">
                 <p class="text-[8px] font-bold text-slate-400">{{ trx.transaction_date }}</p>
                 <span class="text-[7px] font-black px-1.5 py-0.5 rounded uppercase" :class="trx.status.toLowerCase() === 'selesai' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ trx.status }}</span>
              </div>
            </div>
            
            <div class="text-right shrink-0">
              <p class="text-[11px] font-black tracking-tight" :class="trx.type === 'inflow' ? 'text-emerald-600' : 'text-rose-600'">{{ formatCurrency(trx.amount) }}</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>