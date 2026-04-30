<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import api from '../api/axios';

// ==========================================
// 1. GLOBAL STATE (Owner Level)
// ==========================================
const currentView = ref('group_summary'); 
const selectedPT = ref('all'); 
const isLoading = ref(false);

const ptList = ref<any[]>([]); // Data riil dari tabel companies
const ptPerformance = ref<any[]>([]); // Breakdown performa per entitas

const financialReport = ref({
  total_revenue: 0,
  total_expense: 0,
  total_receivable: 0,
  net_profit: 0,
  active_projects_count: 0,
  cash_on_hand: 0 // Asumsi total saldo dari semua COA kategori Asset/Cash
});

// ==========================================
// 2. COMPUTED STATS (Consolidated)
// ==========================================
const groupStats = computed(() => [
  { 
    label: 'Total Group Revenue', 
    value: financialReport.value.total_revenue, 
    color: 'bg-indigo-600', 
    icon: 'fa-chart-line' 
  },
  { 
    label: 'Total Burn Rate (Expenses)', 
    value: financialReport.value.total_expense, 
    color: 'bg-rose-500', 
    icon: 'fa-fire' 
  },
  { 
    label: 'Uncollected Invoices', 
    value: financialReport.value.total_receivable, 
    color: 'bg-amber-500', 
    icon: 'fa-file-invoice-dollar' 
  },
  { 
    label: 'Net Group Profit', 
    value: financialReport.value.net_profit, 
    color: 'bg-emerald-600', 
    icon: 'fa-vault' 
  },
]);

const avgMargin = computed(() => {
  if (financialReport.value.total_revenue === 0) return 0;
  return ((financialReport.value.net_profit / financialReport.value.total_revenue) * 100).toFixed(1);
});

// ==========================================
// 3. API ACTIONS
// ==========================================
const fetchFinanceData = async () => {
  isLoading.value = true;
  try {
    const [resConsolidated, resCompanies] = await Promise.all([
      api.get('/finance/consolidated', { params: { pt_id: selectedPT.value } }),
      api.get('/companies')
    ]);
    
    financialReport.value = resConsolidated.data;
    // Ambil pt_performance langsung dari hasil consolidated agar sinkron dengan filter PT
    ptPerformance.value = resConsolidated.data.pt_performance || [];
    ptList.value = resCompanies.data;
  } catch (e) {
    console.error("Gagal sinkronisasi Financial Command Center", e);
  } finally {
    isLoading.value = false;
  }
};

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR', 
    maximumFractionDigits: 0 
  }).format(val || 0);
};
// Tambahan state khusus untuk tab PT Performance
const searchPT = ref('');
const sortBy = ref('profit'); // default sort berdasarkan profit terbesar

// Logic Filter & Sort untuk Tabel Performance
const filteredPTPerformance = computed(() => {
  let data = [...ptPerformance.value];
  
  // Filter berdasarkan input pencarian
  if (searchPT.value) {
    data = data.filter(pt => 
      pt.name.toLowerCase().includes(searchPT.value.toLowerCase())
    );
  }
  
  // Sorting dinamis
  data.sort((a, b) => {
    if (sortBy.value === 'profit') return b.profit - a.profit;
    if (sortBy.value === 'revenue') return b.revenue - a.revenue;
    if (sortBy.value === 'projects') return b.project_count - a.project_count;
    return 0;
  });
  
  return data;
});

// Fungsi untuk mendapatkan badge status margin
const getMarginStatus = (margin: number) => {
  if (margin >= 30) return { label: 'High Yield', class: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' };
  if (margin >= 10) return { label: 'Stable', class: 'bg-blue-500/10 text-blue-500 border-blue-500/20' };
  return { label: 'Low Margin', class: 'bg-rose-500/10 text-rose-500 border-rose-500/20' };
};
// State untuk menyimpan PT yang sedang di-klik detailnya
const selectedPTDetail = ref<any>(null);
const projectsByPT = ref<any[]>([]);
const isDetailLoading = ref(false);

// Fungsi untuk mengambil detail project saat baris PT di-klik
const fetchPTProjects = async (pt: any) => {
  selectedPTDetail.value = pt;
  isDetailLoading.value = true;
  projectsByPT.value = []; // Reset data lama
  
  try {
    // Kita gunakan endpoint index project dengan filter company_id
    const res = await api.get('/projects', { params: { company_id: pt.id } });
    projectsByPT.value = res.data;
  } catch (e) {
    console.error("Gagal mengambil detail project PT", e);
  } finally {
    isDetailLoading.value = false;
  }
};

const closeDetail = () => {
  selectedPTDetail.value = null;
};
const cashFlowData = ref({
  history: [] as any[],
  summary: { total_inflow: 0, total_outflow: 0, net_flow: 0 }
});

const fetchCashFlow = async () => {
  try {
    const res = await api.get('/finance/cashflow', { params: { pt_id: selectedPT.value } });
    cashFlowData.value = res.data;
  } catch (e) {
    console.error("Gagal sinkronisasi Cash Flow", e);
  }
};

// Panggil di onMounted dan tambahkan di watch selectedPT
onMounted(() => {
  fetchCashFlow();
});
watch(selectedPT, () => {
  fetchFinanceData();
});

onMounted(fetchFinanceData);
</script>

<template>
  <div class="min-h-screen bg-[#F1F5F9] pb-20 md:pl-20 font-sans overflow-x-hidden text-slate-900">
    <div class="max-w-7xl mx-auto px-6 mt-8">
      
      <!-- HEADER DASHBOARD FINANCE -->
      <div class="bg-slate-900 border-x border-t border-slate-800 rounded-t-[2.5rem] pt-10 pb-10 relative shadow-2xl overflow-hidden">
        <!-- Decor Visual -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/10 blur-[100px] -mr-32 -mt-32"></div>
        
        <div class="flex flex-col md:flex-row items-center justify-between px-10 gap-6 relative z-10">
          <div class="flex items-center gap-8">
            <div class="w-20 h-20 bg-emerald-500 rounded-3xl flex items-center justify-center shadow-[0_15px_40px_rgba(16,185,129,0.4)] transform -rotate-6">
              <i class="fas fa-landmark text-4xl text-white"></i>
            </div>
            <div>
              <h1 class="text-3xl font-black text-white tracking-tighter leading-none mb-2 uppercase">Financial Command Center</h1>
              <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 text-[9px] font-black rounded uppercase tracking-widest border border-emerald-500/30">Live Data</span>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em]">Consolidated Group Reporting (7 Entities)</p>
              </div>
            </div>
          </div>
          
          <div class="flex items-center gap-4 bg-slate-800/50 p-2 rounded-2xl border border-slate-700">
             <span class="text-[9px] font-black text-slate-500 uppercase ml-3 tracking-widest">Entity Filter:</span>
             <select v-model="selectedPT" class="bg-slate-800 border-none text-white text-[11px] font-black uppercase py-2.5 px-6 rounded-xl outline-none focus:ring-2 ring-emerald-500 cursor-pointer min-w-[200px]">
                <option value="all">Consolidated (All PT)</option>
                <option v-for="pt in ptList" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
             </select>
          </div>
        </div>
      </div>

      <!-- QUICK NAVIGATION -->
      <div class="bg-white border-x border-y border-slate-200 py-4 px-10 md:pl-[140px] flex gap-12 shadow-sm">
        <button v-for="t in ['group_summary', 'pt_performance', 'cash_flow']" :key="t"
          @click="currentView = t"
          class="text-[11px] font-black uppercase tracking-[0.2em] transition-all relative py-2"
          :class="currentView === t ? 'text-emerald-600' : 'text-slate-400 hover:text-slate-600'">
          {{ t.replace('_', ' ') }}
          <div v-if="currentView === t" class="absolute bottom-0 left-0 w-full h-1 bg-emerald-500 rounded-full"></div>
        </button>
      </div>

      <!-- MAIN CONTENT: CONSOLIDATED STATS -->
      <div v-if="currentView === 'group_summary'" class="py-10 animate-in fade-in slide-in-from-bottom-4 duration-700 space-y-10">
        
        <!-- TOP ROW STATS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div v-for="s in groupStats" :key="s.label" :class="s.color" class="p-8 rounded-[2.5rem] text-white shadow-xl relative group overflow-hidden">
                <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <p class="text-[10px] font-black opacity-70 uppercase mb-3 tracking-widest">{{ s.label }}</p>
                
                <!-- Tambahkan pengecekan warna khusus untuk Net Profit jika minus -->
                <h3 class="text-2xl font-black italic tracking-tighter" 
                    :class="s.label === 'Net Group Profit' && s.value < 0 ? 'text-rose-200' : ''">
                    {{ formatCurrency(s.value) }}
                </h3>
                
                <i :class="s.icon" class="fas absolute right-8 bottom-8 text-5xl opacity-10 transform group-hover:scale-110 transition-transform"></i>
            </div>
        </div>

        <!-- MIDDLE ROW: ANALYSIS -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
           <!-- Intercompany Funding Analysis (Integrated Logic) -->
           <div class="lg:col-span-8 bg-white border border-slate-200 rounded-[3.5rem] p-10 shadow-sm relative overflow-hidden">
              <div class="flex justify-between items-center mb-10">
                  <div>
                    <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">Intercompany Equity Flow</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase italic">Real-time cash movement monitoring</p>
                  </div>
                  <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300">
                    <i class="fas fa-exchange-alt text-xl"></i>
                  </div>
              </div>
              
              <!-- Visualization Placeholder for Flow -->
             <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
                    <!-- Looping dari data riil ptPerformance (Ambil top 3 atau semua) -->
                    <div v-for="pt in ptPerformance.slice(0, 3)" :key="pt.id" 
                        class="bg-slate-50 border border-slate-100 p-6 rounded-[2rem] text-center group hover:bg-white hover:shadow-md transition-all">
                        
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-2 tracking-widest">Source Entity</p>
                        <p class="text-[11px] font-black text-[#2E3A8C] uppercase">{{ pt.name }}</p>
                        
                        <!-- Icon panah berubah warna jika profit/loss -->
                        <div class="my-4" :class="pt.profit >= 0 ? 'text-emerald-500' : 'text-rose-500'">
                            <i class="fas" :class="pt.profit >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                        </div>
                        
                        <p class="text-sm font-black text-slate-800">{{ formatCurrency(pt.profit) }}</p>
                        <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">Net Contribution</p>
                    </div>

                    <!-- Overlay Label -->
                    <div class="hidden md:flex absolute inset-0 items-center justify-center pointer-events-none">
                        <p class="bg-white px-4 py-2 rounded-full border border-slate-100 text-[10px] font-black text-slate-300 uppercase tracking-widest shadow-sm">
                            Project Funding Distribution
                        </p>
                    </div>
                </div>

                <!-- Empty State jika data belum ada -->
                <div v-if="ptPerformance.length === 0" class="py-10 text-center border-2 border-dashed border-slate-100 rounded-[2rem]">
                    <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">No Funding Data Available</p>
                </div>
           </div>

           <!-- Profitability Gauge -->
           <div class="lg:col-span-4 bg-emerald-600 rounded-[3.5rem] p-12 text-white shadow-2xl relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-1000"></div>
              
              <h3 class="text-sm font-black uppercase mb-12 tracking-[0.2em] opacity-80 text-center">Avg. Group Margin</h3>
              <div class="text-center relative z-10">
                 <h2 class="text-8xl font-black italic mb-2 tracking-tighter">
                   {{ avgMargin }}<span class="text-3xl not-italic ml-1">%</span>
                 </h2>
                 <p class="text-[10px] font-bold opacity-70 uppercase tracking-[0.3em] mb-12">Overall Group Yield</p>
              </div>

              <div class="space-y-5 relative z-10 bg-black/10 p-6 rounded-[2rem] border border-white/10">
                 <div class="flex justify-between items-center text-[11px] font-black uppercase">
                    <span class="opacity-60">Active Projects</span>
                    <span class="text-emerald-300">{{ financialReport.active_projects_count }} Units</span>
                 </div>
                 <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-400" :style="{ width: avgMargin + '%' }"></div>
                 </div>
                 <div class="flex justify-between items-center text-[11px] font-black uppercase">
                    <span class="opacity-60">Operational Load</span>
                    <span class="text-emerald-300">68.2%</span>
                 </div>
              </div>
           </div>
        </div>

        <!-- PT PERFORMANCE TABLE (Real Data Integration) -->
        <div class="bg-white border border-slate-200 rounded-[3.5rem] overflow-hidden shadow-sm">
           <div class="px-12 py-8 bg-slate-50/50 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
              <div>
                <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">Entities Performance Breakdown</h4>
                <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Audit Trail: Data updated per transaction</p>
              </div>
              <button class="bg-white border border-slate-200 text-[#2E3A8C] px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-900 hover:text-white hover:border-slate-900 transition-all shadow-sm active:scale-95">
                <i class="fas fa-file-export mr-2"></i> Export P&L Report
              </button>
           </div>
           
           <div class="overflow-x-auto">
             <table class="w-full text-left border-collapse">
               <thead>
                  <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                    <th class="p-10">Entity Name</th>
                    <th class="p-10">Scale</th>
                    <th class="p-10">Revenue</th>
                    <th class="p-10">Expenses</th>
                    <th class="p-10 text-right">Net Contribution</th>
                  </tr>
               </thead>
               <tbody class="divide-y divide-slate-50">
                  <tr v-for="pt in ptPerformance" :key="pt.id" class="hover:bg-slate-50/80 transition-all cursor-pointer group">
                    <td class="p-10">
                      <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 font-black text-xs group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                          {{ pt.name.substring(0, 2).toUpperCase() }}
                        </div>
                        <span class="font-black text-[12px] text-[#2E3A8C] uppercase tracking-tight">{{ pt.name }}</span>
                      </div>
                    </td>
                    <td class="p-10">
                      <div class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 rounded-full border border-slate-200">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                        <span class="text-[10px] font-black text-slate-500 uppercase">{{ pt.project_count }} Projects</span>
                      </div>
                    </td>
                    <td class="p-10 text-[12px] font-bold text-slate-800">{{ formatCurrency(pt.revenue) }}</td>
                    <td class="p-10 text-[12px] font-bold text-rose-500 italic">- {{ formatCurrency(pt.expense) }}</td>
                    <td class="p-10 text-right">
                      <span class="font-black text-emerald-600 italic text-base group-hover:underline decoration-2 underline-offset-4">
                        {{ formatCurrency(pt.profit) }}
                      </span>
                    </td>
                  </tr>
                  
                  <!-- Empty State -->
                  <tr v-if="ptPerformance.length === 0">
                    <td colspan="5" class="p-20 text-center">
                      <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em]">No entity performance data found</p>
                    </td>
                  </tr>
               </tbody>
             </table>
           </div>
        </div>
      </div>
      <!-- TAB CONTENT: PT PERFORMANCE -->
        <div v-if="currentView === 'pt_performance'" class="py-10 animate-in fade-in slide-in-from-bottom-4 duration-700 space-y-8">
        
            <!-- FILTER & SEARCH BAR -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200">
                <div class="relative w-full md:w-96">
                <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                <input v-model="searchPT" type="text" placeholder="Search Entity Name..." 
                    class="w-full pl-12 pr-6 py-3 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase tracking-widest">
                </div>
                <div class="flex gap-3 w-full md:w-auto">
                <select v-model="sortBy" class="flex-1 md:w-48 bg-slate-50 border border-slate-100 px-4 py-3 rounded-2xl text-[10px] font-black uppercase outline-none">
                    <option value="profit">Sort by Profit</option>
                    <option value="revenue">Sort by Revenue</option>
                    <option value="projects">Sort by Projects</option>
                </select>
                </div>
            </div>

            <!-- ENTITY PERFORMANCE GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- MAIN TABLE -->
                <div class="lg:col-span-12 bg-white border border-slate-200 rounded-[3rem] overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                        <th class="p-8">Entity & Scale</th>
                        <th class="p-8">Revenue Analysis</th>
                        <th class="p-8">Operational Load</th>
                        <th class="p-8">Efficiency</th>
                        <th class="p-8 text-right">Net Contribution</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                    <tr v-for="pt in filteredPTPerformance" :key="pt.id" 
                        @click="fetchPTProjects(pt)"
                        class="hover:bg-indigo-50/50 transition-all group cursor-pointer">
                        <!-- Entity & Scale -->
                        <td class="p-8">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-black text-lg shadow-lg group-hover:bg-indigo-600 transition-colors">
                            {{ pt.name.substring(0,2).toUpperCase() }}
                            </div>
                            <div>
                            <p class="text-[13px] font-black text-slate-800 uppercase tracking-tight">{{ pt.name }}</p>
                            <p class="text-[9px] font-bold text-indigo-500 uppercase tracking-widest mt-1">{{ pt.project_count }} Active Projects</p>
                            </div>
                        </div>
                        </td>

                        <!-- Revenue Analysis -->
                        <td class="p-8">
                        <div class="space-y-1">
                            <p class="text-[11px] font-black text-slate-800">{{ formatCurrency(pt.revenue) }}</p>
                            <div class="w-24 h-1 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-500" :style="{ width: (pt.revenue > 0 ? 100 : 0) + '%' }"></div>
                            </div>
                        </div>
                        </td>

                        <!-- Operational Load (Expenses) -->
                        <td class="p-8">
                        <p class="text-[11px] font-bold text-rose-500 italic">{{ formatCurrency(pt.expense) }}</p>
                        </td>

                        <!-- Efficiency (Margin Badge) -->
                        <td class="p-8">
                        <span :class="getMarginStatus(pt.margin || 0).class" 
                            class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase border tracking-widest">
                            {{ (pt.margin || 0) }}% {{ getMarginStatus(pt.margin || 0).label }}
                        </span>
                        </td>

                        <!-- Net Contribution -->
                        <td class="p-8 text-right">
                        <div class="flex flex-col items-end">
                            <span class="text-lg font-black italic" :class="pt.profit >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                            {{ formatCurrency(pt.profit) }}
                            </span>
                            <span v-if="pt.profit < 0" class="text-[8px] font-black text-rose-300 uppercase tracking-tighter">Negative Cashflow</span>
                        </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-if="filteredPTPerformance.length === 0" class="py-24 text-center">
                    <i class="fas fa-layer-group text-4xl text-slate-100 mb-4"></i>
                    <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em]">No Entities Matching Your Search</p>
                </div>
                </div>
            </div>
        </div>
        <div v-if="selectedPTDetail" class="fixed inset-0 z-[1000] flex items-center justify-end">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeDetail"></div>
        
        <!-- Slide-over Panel -->
        <div class="bg-white w-full max-w-2xl h-full shadow-2xl relative z-10 animate-in slide-in-from-right duration-500 overflow-y-auto">
            
            <!-- Header Modal -->
            <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <div>
                <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">{{ selectedPTDetail.name }}</h3>
                <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">Ongoing Projects Portfolio</p>
            </div>
            <button @click="closeDetail" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 transition-all">
                <i class="fas fa-times"></i>
            </button>
            </div>

            <!-- Content -->
            <div class="p-8 space-y-6">
            <div v-if="isDetailLoading" class="py-20 text-center">
                <i class="fas fa-circle-notch fa-spin text-3xl text-indigo-500 mb-4"></i>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Syncing Project Data...</p>
            </div>

            <div v-else-if="projectsByPT.length > 0" class="space-y-4">
                <div v-for="proj in projectsByPT" :key="proj.id" 
                    class="p-6 rounded-[2rem] border border-slate-100 bg-slate-50/50 hover:bg-white hover:border-indigo-100 transition-all group">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shadow-sm">
                        <i class="fas fa-project-diagram text-indigo-500 text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-tight">{{ proj.project_title }}</h4>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ proj.client_name }}</p>
                    </div>
                    </div>
                    <span class="px-3 py-1 bg-white border border-slate-100 rounded-lg text-[9px] font-black uppercase text-slate-500">
                    {{ proj.status }}
                    </span>
                </div>

                <!-- Progress & Value -->
                <div class="grid grid-cols-2 gap-6 mt-4 pt-4 border-t border-slate-100/50">
                    <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Contract Value</p>
                    <p class="text-[11px] font-black text-slate-800">{{ formatCurrency(proj.contract_value) }}</p>
                    </div>
                    <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Completion</p>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500" :style="{ width: proj.progress + '%' }"></div>
                        </div>
                        <span class="text-[10px] font-black text-slate-800">{{ proj.progress }}%</span>
                    </div>
                    </div>
                </div>
                </div>
            </div>

            <div v-else class="py-20 text-center grayscale opacity-30">
                <i class="fas fa-folder-open text-5xl mb-4"></i>
                <p class="text-[10px] font-black uppercase tracking-widest">No active projects for this entity</p>
            </div>
            </div>
        </div>
        </div>  
        <!-- TAB CONTENT: CASH FLOW -->
        <div v-if="currentView === 'cash_flow'" class="py-10 animate-in fade-in duration-700 space-y-8">
        
        <!-- ROW 1: CASH FLOW ANALYTICS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Inflow Card -->
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-150"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Period Cash Inflow</p>
            <h4 class="text-3xl font-black text-emerald-600 tracking-tighter italic">+ {{ formatCurrency(cashFlowData.summary.total_inflow) }}</h4>
            <div class="mt-4 flex items-center gap-2 text-[9px] font-bold text-emerald-500 uppercase">
                <i class="fas fa-caret-up"></i> Collected Revenue
            </div>
            </div>

            <!-- Outflow Card -->
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-rose-50 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-150"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Period Cash Outflow</p>
            <h4 class="text-3xl font-black text-rose-500 tracking-tighter italic">- {{ formatCurrency(cashFlowData.summary.total_outflow) }}</h4>
            <div class="mt-4 flex items-center gap-2 text-[9px] font-bold text-rose-400 uppercase">
                <i class="fas fa-caret-down"></i> Burn Rate & Ops
            </div>
            </div>

            <!-- Net Position -->
            <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/20 to-transparent"></div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Net Cash Position</p>
            <h4 class="text-3xl font-black text-white tracking-tighter italic">{{ formatCurrency(cashFlowData.summary.net_flow) }}</h4>
            <div class="mt-4 flex items-center gap-2 text-[9px] font-black" :class="cashFlowData.summary.net_flow >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                <i class="fas" :class="cashFlowData.summary.net_flow >= 0 ? 'fa-check-circle' : 'fa-exclamation-triangle'"></i>
                {{ cashFlowData.summary.net_flow >= 0 ? 'Surplus Balance' : 'Deficit Awareness' }}
            </div>
            </div>
        </div>

        <!-- ROW 2: DETAILED TRANSACTION LEDGER -->
        <div class="bg-white border border-slate-200 rounded-[3.5rem] overflow-hidden shadow-sm">
            <div class="px-10 py-8 bg-slate-50/50 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-[0.2em]">Transaction General Ledger</h3>
                <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Chronological audit trail for 7 entities</p>
            </div>
            <button @click="fetchCashFlow" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-indigo-600 transition-all shadow-sm">
                <i class="fas fa-sync-alt text-xs"></i>
            </button>
            </div>

            <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50">
                    <th class="p-10">Date & Ref</th>
                    <th class="p-10">Entity & Project</th>
                    <th class="p-10">Description</th>
                    <th class="p-10">Account</th>
                    <th class="p-10 text-right">Movement</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                <tr v-for="item in cashFlowData.history" :key="item.id" class="hover:bg-slate-50/80 transition-all group">
                    <td class="p-10">
                    <p class="text-[11px] font-black text-slate-400 group-hover:text-slate-900 transition-colors">{{ item.transaction_date }}</p>
                    <p class="text-[8px] font-bold text-indigo-300 mt-1 uppercase">#{{ item.reference_type }}-{{ item.id }}</p>
                    </td>
                    <td class="p-10">
                    <div class="space-y-1">
                        <p class="text-[11px] font-black text-[#2E3A8C] uppercase tracking-tighter">{{ item.company_name }}</p>
                        <p class="text-[9px] font-bold text-slate-400 uppercase italic">{{ item.project_title || 'Non-Project Ops' }}</p>
                    </div>
                    </td>
                    <td class="p-10">
                    <p class="text-[11px] font-bold text-slate-600 uppercase leading-relaxed">{{ item.description }}</p>
                    </td>
                    <td class="p-10">
                    <span class="px-3 py-1 bg-slate-100 rounded-lg text-[9px] font-black uppercase text-slate-500 border border-slate-200 group-hover:bg-white group-hover:border-indigo-200 transition-all">
                        {{ item.account_name }}
                    </span>
                    </td>
                    <td class="p-10 text-right">
                    <span :class="item.debit > 0 ? 'text-emerald-600' : 'text-rose-500'" class="font-black text-sm italic">
                        {{ item.debit > 0 ? '+' : '-' }} {{ formatCurrency(item.debit > 0 ? item.debit : item.credit) }}
                    </span>
                    </td>
                </tr>

                <!-- EMPTY STATE -->
                <tr v-if="cashFlowData.history.length === 0">
                    <td colspan="5" class="p-24 text-center">
                    <div class="flex flex-col items-center opacity-20">
                        <i class="fas fa-receipt text-6xl mb-6"></i>
                        <p class="text-[10px] font-black uppercase tracking-[0.4em]">Zero transactions found in ledger</p>
                    </div>
                    </td>
                </tr>
                </tbody>
            </table>
            </div>
        </div>
        </div>
    </div>
  </div>
</template>

<style scoped>
.animate-in {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>