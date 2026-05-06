<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/axios'; // Path ../../ karena masuk 2 folder

const router = useRouter();

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
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
  <div class="animate-in fade-in duration-700 space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-emerald-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
        <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Total Pendapatan</p>
        <h3 class="text-2xl font-black tracking-tighter">{{ formatCurrency(overviewStats.total_revenue) }}</h3>
        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full"><i class="fas fa-arrow-trend-up"></i> Uang Masuk</div>
        <i class="fas fa-wallet absolute right-8 bottom-8 text-5xl opacity-20"></i>
      </div>
      <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-rose-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
        <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Total Pengeluaran</p>
        <h3 class="text-2xl font-black tracking-tighter">{{ formatCurrency(overviewStats.total_expense) }}</h3>
        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full"><i class="fas fa-arrow-trend-down"></i> Operasional</div>
        <i class="fas fa-file-invoice-dollar absolute right-8 bottom-8 text-5xl opacity-20"></i>
      </div>
      <div class="bg-gradient-to-br from-amber-400 to-amber-500 rounded-[2.5rem] p-8 text-white shadow-xl shadow-amber-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
        <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Piutang (Belum Cair)</p>
        <h3 class="text-2xl font-black tracking-tighter">{{ formatCurrency(overviewStats.total_receivable) }}</h3>
        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full"><i class="fas fa-clock"></i> Menunggu</div>
        <i class="fas fa-hand-holding-usd absolute right-8 bottom-8 text-5xl opacity-20"></i>
      </div>
      <div class="bg-gradient-to-br from-indigo-600 to-slate-900 rounded-[2.5rem] p-8 text-white shadow-xl shadow-indigo-200 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
        <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Net Profit Group</p>
        <h3 class="text-2xl font-black tracking-tighter" :class="overviewStats.net_profit < 0 ? 'text-rose-300' : ''">{{ formatCurrency(overviewStats.net_profit) }}</h3>
        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full"><i class="fas fa-scale-balanced"></i> {{ overviewStats.active_projects_count }} Proyek Aktif</div>
        <i class="fas fa-vault absolute right-8 bottom-8 text-5xl opacity-20"></i>
      </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <div class="lg:col-span-8 bg-white rounded-[3rem] p-8 shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-8">
          <div>
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Kinerja Entitas (PT)</h3>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Perbandingan Pendapatan & Pengeluaran</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center"><i class="fas fa-chart-bar"></i></div>
        </div>
        <div class="space-y-6">
          <div v-if="overviewStats.pt_performance.length === 0" class="py-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">Belum ada data kinerja entitas</div>
          <div v-for="pt in overviewStats.pt_performance" :key="pt.id" class="space-y-2">
            <div class="flex justify-between items-end">
              <span class="text-[11px] font-black text-slate-700 uppercase tracking-tight">{{ pt.name }}</span>
              <span class="text-[10px] font-black text-emerald-600">{{ pt.margin }}% Margin</span>
            </div>
            <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex">
              <div class="h-full bg-emerald-500 transition-all duration-1000" :style="{ width: `${(pt.revenue / maxPtRevenue) * 100}%` }"></div>
              <div v-if="pt.expense > 0" class="h-full bg-rose-500 transition-all duration-1000 opacity-80" :style="{ width: `${(pt.expense / maxPtRevenue) * 100}%`, marginLeft: `-${(pt.expense / maxPtRevenue) * 100}%` }"></div>
            </div>
            <div class="flex justify-between text-[8px] font-black uppercase text-slate-400">
              <span>Masuk: {{ formatCurrency(pt.revenue) }}</span>
              <span>Keluar: {{ formatCurrency(pt.expense) }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="lg:col-span-4 bg-white rounded-[3rem] p-8 shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-8">
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Aktivitas Terkini</h3>
        </div>
        <div class="space-y-4">
          <div v-if="recentTransactions.length === 0" class="py-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">Belum ada transaksi</div>
          <div v-for="trx in recentTransactions" :key="trx.id" @click="trx.project_id ? router.push('/projects/' + trx.project_id) : null" class="flex items-center gap-4 p-3 rounded-2xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100" :class="trx.project_id ? 'cursor-pointer hover:shadow-sm' : 'cursor-default'">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm" :class="trx.type === 'inflow' ? 'bg-emerald-50 text-emerald-500' : 'bg-rose-50 text-rose-500'"><i class="fas" :class="trx.type === 'inflow' ? 'fa-arrow-down' : 'fa-arrow-up'"></i></div>
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <p class="text-[10px] font-black text-slate-700 uppercase truncate max-w-[120px]">{{ trx.description || trx.project_name || 'General' }}</p>
                <i v-if="trx.project_id" class="fas fa-external-link-alt text-[8px] text-indigo-400" title="Click to view project"></i>
              </div>
              <p class="text-[8px] font-bold text-slate-400 mt-0.5">{{ trx.transaction_date }}</p>
            </div>
            <div class="text-right">
              <p class="text-[11px] font-black italic" :class="trx.type === 'inflow' ? 'text-emerald-600' : 'text-rose-600'">{{ formatCurrency(trx.amount) }}</p>
              <span class="text-[8px] font-bold px-2 py-0.5 rounded uppercase" :class="trx.status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ trx.status }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>