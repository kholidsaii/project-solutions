<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import api from '../api/axios';

// ==========================================
// 1. GLOBAL STATE & MASTER DATA
// ==========================================
const mainTabs = ['overview', 'transaksi', 'accounting', 'banking', 'setup'];
const currentMainTab = ref('transaksi');

// Master Data State (Tersentralisasi untuk semua tab)
const dbProjects = ref<any[]>([]);
const dbCOAs = ref<any[]>([]);
const dbBanks = ref<any[]>([]);
const dbCompanies = ref<any[]>([]);
const dbLabels = ref<any[]>([]);

// Global Utility
const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

// ==========================================
// 2. GLOBAL API FETCHERS
// ==========================================
// Mengambil Data Master Sekaligus untuk Pilihan Form & Filter
const fetchMasterData = async () => {
  try {
    const [resProj, resCOA, resBank, resComp, resLabels] = await Promise.all([
      api.get('/projects'),
      api.get('/accounting/coas'),
      api.get('/finance/banks'),
      api.get('/companies'),
      api.get('/master-data/labels')
    ]);
    dbProjects.value = resProj.data.data || resProj.data;
    dbCOAs.value = resCOA.data.data || resCOA.data;
    dbBanks.value = resBank.data.data || resBank.data;
    dbCompanies.value = resComp.data.data || resComp.data;
    dbLabels.value = resLabels.data.data || resLabels.data;
  } catch (error) {
    console.error("Gagal load Master Data", error);
  }
};

// ==========================================
// 3. TAB: OVERVIEW (EXECUTIVE DASHBOARD)
// ==========================================
const overviewStats = ref({
  total_revenue: 0,
  total_expense: 0,
  total_receivable: 0,
  net_profit: 0,
  active_projects_count: 0,
  pt_performance: [] as any[]
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

// ==========================================
// 4. TAB: TRANSAKSI
// ==========================================
const activeNav = ref('all');
const selectedProject = ref('all');
const currentPage = ref(1);
const totalPages = ref(1);
const transactions = ref<any[]>([]);

const transactionNavs = [
  { id: 'all', label: 'Semua Transaksi', icon: 'fa-list-ul' },
  { id: 'pending', label: 'Pending / Diajukan', icon: 'fa-clock' },
  { id: 'approved', label: 'Approved', icon: 'fa-check-double' },
  { id: 'income', label: 'Uang Masuk (Income)', icon: 'fa-arrow-down text-emerald-500' },
  { id: 'expense', label: 'Uang Keluar (Expense)', icon: 'fa-arrow-up text-rose-500' },
];

const showAddModal = ref(false);
const txForm = ref({
  type: 'outflow',
  date: new Date().toISOString().split('T')[0],
  ref_number: '',
  project_id: '',
  coa_id: '',
  method: 'transfer',
  bank_from: '',
  bank_to: '',
  amount: 0,
  description: '',
  label_id: '',
  attachment: null as File | null
});

const fetchTransactions = async () => {
  try {
    const params = {
      project_id: selectedProject.value,
      status: activeNav.value === 'pending' || activeNav.value === 'approved' ? activeNav.value : 'all',
      type: activeNav.value === 'income' ? 'inflow' : (activeNav.value === 'expense' ? 'outflow' : 'all')
    };
    const res = await api.get('/finance/transactions', { params });
    transactions.value = res.data;
  } catch (error) {
    console.error("Gagal load Data Transaksi", error);
  }
};

const handleFileUpload = (event: any) => {
  const file = event.target.files[0];
  if (file) txForm.value.attachment = file;
};

const handleSaveTransaction = async () => {
  try {
    const formData = new FormData();
    formData.append('type', String(txForm.value.type));
    formData.append('date', String(txForm.value.date));
    formData.append('amount', String(txForm.value.amount));
    formData.append('coa_id', String(txForm.value.coa_id));
    formData.append('method', String(txForm.value.method));
    formData.append('description', String(txForm.value.description));
    
    if (txForm.value.ref_number) formData.append('ref_number', String(txForm.value.ref_number));
    if (txForm.value.project_id) formData.append('project_id', String(txForm.value.project_id));
    if (txForm.value.bank_from) formData.append('bank_from', String(txForm.value.bank_from));
    if (txForm.value.bank_to) formData.append('bank_to', String(txForm.value.bank_to));
    
    if (txForm.value.attachment) formData.append('attachment', txForm.value.attachment);

    await api.post('/finance/transactions', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    alert("Transaksi berhasil diajukan dan menunggu approval!");
    showAddModal.value = false;
    
    await fetchTransactions();
    txForm.value.amount = 0;
    txForm.value.description = '';
    txForm.value.attachment = null;
  } catch (error: any) {
    console.error("Gagal simpan transaksi", error);
    alert("Gagal menyimpan transaksi. Pastikan semua data wajib telah diisi.");
  }
};

// ==========================================
// 5. TAB: ACCOUNTING (COA MANAGEMENT)
// ==========================================
const activeAccEntity = ref('all'); 
const activeAccCategory = ref('all'); 
const searchCOA = ref('');

const coaCategories = [
  { id: 'all', label: 'Semua Kategori', icon: 'fa-layer-group', color: 'text-slate-500' },
  { id: 'Asset', label: 'Harta (Assets)', icon: 'fa-wallet', color: 'text-emerald-500' },
  { id: 'Liability', label: 'Kewajiban (Liabilities)', icon: 'fa-hand-holding-usd', color: 'text-rose-500' },
  { id: 'Equity', label: 'Modal (Equity)', icon: 'fa-scale-balanced', color: 'text-indigo-500' },
  { id: 'Revenue', label: 'Pendapatan (Revenue)', icon: 'fa-arrow-trend-up', color: 'text-emerald-400' },
  { id: 'Expense', label: 'Beban (Expenses)', icon: 'fa-arrow-trend-down', color: 'text-rose-400' },
];

const filteredCOAs = computed(() => {
  let data = [...dbCOAs.value];
  if (activeAccEntity.value !== 'all') {
    if (activeAccEntity.value === 'personal') data = data.filter(c => c.pt_id === null || c.pt_id === '');
    else data = data.filter(c => c.pt_id === activeAccEntity.value);
  }
  if (activeAccCategory.value !== 'all') data = data.filter(c => c.category === activeAccCategory.value);
  if (searchCOA.value) {
    const q = searchCOA.value.toLowerCase();
    data = data.filter(c => c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q));
  }
  return data;
});

const showCoaModal = ref(false);
const coaForm = ref({
  id: null as number | null,
  pt_id: '' as string | null,
  code: '',
  name: '',
  category: 'Asset'
});

const openCoaModal = (coa: any = null) => {
  if (coa) coaForm.value = { ...coa, pt_id: coa.pt_id || '' };
  else coaForm.value = { id: null, pt_id: '', code: '', name: '', category: 'Asset' };
  showCoaModal.value = true;
};

const handleSaveCOA = async () => {
  try {
    const payload: Record<string, any> = { ...coaForm.value };
    if (!payload.pt_id) payload.pt_id = null;

    if (payload.id) {
      await api.put(`/accounting/coas/${payload.id}`, payload);
      alert("Akun COA berhasil diperbarui!");
    } else {
      await api.post('/accounting/coas', payload);
      alert("Akun COA baru berhasil dibuat!");
    }
    
    showCoaModal.value = false;
    await fetchMasterData(); 
  } catch (error) {
    console.error("Gagal simpan COA", error);
    alert("Terjadi kesalahan saat menyimpan data akun.");
  }
};

const handleDeleteCOA = async (id: number) => {
  if (!confirm("Yakin ingin menghapus akun COA ini? Semua transaksi terkait mungkin akan terdampak.")) return;
  try {
    await api.delete(`/accounting/coas/${id}`);
    alert("Akun COA berhasil dihapus!");
    await fetchMasterData(); 
  } catch (error) {
    console.error("Gagal hapus COA", error);
    alert("Gagal menghapus COA. Akun ini mungkin sudah dipakai di transaksi.");
  }
};

// ==========================================
// 6. TAB: BANKING
// ==========================================
const activeBankEntity = ref('all'); 
const searchBank = ref('');

const filteredBanks = computed(() => {
  let data = [...dbBanks.value];
  if (activeBankEntity.value !== 'all') {
    if (activeBankEntity.value === 'personal') data = data.filter(b => b.pt_id === null || b.pt_id === '');
    else data = data.filter(b => b.pt_id === activeBankEntity.value);
  }
  if (searchBank.value) {
    const q = searchBank.value.toLowerCase();
    data = data.filter(b => b.bank_name.toLowerCase().includes(q) || b.account_name.toLowerCase().includes(q) || b.account_number.includes(q));
  }
  return data;
});

const showBankModal = ref(false);
const bankForm = ref({
  id: null as number | null,
  pt_id: '' as string | null,
  bank_name: '',
  account_name: '',
  account_number: '',
  branch_office: ''
});

const openBankModal = (bank: any = null) => {
  if (bank) bankForm.value = { ...bank, pt_id: bank.pt_id || '' };
  else bankForm.value = { id: null, pt_id: '', bank_name: '', account_name: '', account_number: '', branch_office: '' };
  showBankModal.value = true;
};

const handleSaveBank = async () => {
  try {
    const payload: Record<string, any> = { ...bankForm.value };
    if (!payload.pt_id) payload.pt_id = null;

    if (payload.id) {
      await api.put(`/finance/banks/${payload.id}`, payload);
      alert("Rekening berhasil diperbarui!");
    } else {
      await api.post('/finance/banks', payload);
      alert("Rekening baru berhasil dibuat!");
    }
    
    showBankModal.value = false;
    await fetchMasterData(); 
  } catch (error) {
    console.error("Gagal simpan Bank", error);
    alert("Terjadi kesalahan saat menyimpan data rekening.");
  }
};

const handleDeleteBank = async (id: number) => {
  if (!confirm("Yakin ingin menghapus rekening ini?")) return;
  try {
    await api.delete(`/finance/banks/${id}`);
    alert("Rekening berhasil dihapus!");
    await fetchMasterData();
  } catch (error) {
    console.error("Gagal hapus Bank", error);
    alert("Gagal menghapus rekening. Akun ini mungkin sudah dipakai di transaksi.");
  }
};

// ==========================================
// 7. TAB: SETUP (CONFIGURATION)
// ==========================================
const activeSetupNav = ref('labels'); 
const showLabelModal = ref(false);

const labelForm = ref({
  id: null as number | null,
  name: '',
  color: 'bg-slate-100 text-slate-600'
});

const colorOptions = [
  { class: 'bg-rose-100 text-rose-600', name: 'Merah (Urgent)' },
  { class: 'bg-indigo-100 text-indigo-600', name: 'Ungu (Vendor/B2B)' },
  { class: 'bg-emerald-100 text-emerald-600', name: 'Hijau (Income/Approve)' },
  { class: 'bg-amber-100 text-amber-600', name: 'Kuning (Ops/Warning)' },
  { class: 'bg-slate-100 text-slate-600', name: 'Abu-abu (General)' },
];

const openLabelModal = (label: any = null) => {
  if (label) labelForm.value = { ...label };
  else labelForm.value = { id: null, name: '', color: 'bg-slate-100 text-slate-600' };
  showLabelModal.value = true;
};

const handleSaveLabel = async () => {
  try {
    const payload = { name: labelForm.value.name, color: labelForm.value.color };
    if (labelForm.value.id) {
      await api.put(`/master-data/labels/${labelForm.value.id}`, payload);
      alert("Label berhasil diperbarui!");
    } else {
      await api.post('/master-data/labels', payload);
      alert("Label baru berhasil ditambahkan!");
    }
    showLabelModal.value = false;
    await fetchMasterData(); 
  } catch (error) {
    console.error("Gagal simpan Label", error);
    alert("Terjadi kesalahan saat menyimpan label.");
  }
};

const handleDeleteLabel = async (id: number) => {
  if (!confirm("Yakin ingin menghapus label ini? Label akan hilang dari menu transaksi.")) return;
  try {
    await api.delete(`/master-data/labels/${id}`);
    alert("Label berhasil dihapus!");
    await fetchMasterData();
  } catch (error) {
    console.error("Gagal hapus Label", error);
  }
};

// ==========================================
// 8. LIFECYCLE & WATCHERS
// ==========================================
onMounted(() => {
  fetchMasterData();
  fetchTransactions();
  fetchOverviewData(); 
});

// Watcher untuk Filter Transaksi
watch([activeNav, selectedProject], () => {
  fetchTransactions();
});

// Watcher untuk Refresh Data Saat Pindah Tab
watch(currentMainTab, (newTab) => {
  if (newTab === 'overview') {
    fetchOverviewData();
  } else if (newTab === 'transaksi') {
    fetchTransactions();
  }
});
</script>

<template>
  <div class="min-h-screen bg-[#F1F5F9] pb-20 md:pl-20 font-sans overflow-x-hidden text-slate-900">
    <div class="max-w-7xl mx-auto px-6 mt-8 space-y-6">
      
      <!-- 1. HEADER & MAIN 5 TABS NAVIGATION -->
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="flex items-center gap-5">
          <div class="w-14 h-14 bg-slate-900 rounded-2xl flex items-center justify-center text-white text-xl shadow-lg transform -rotate-6">
            <i class="fas fa-wallet"></i>
          </div>
          <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tighter uppercase italic">Finance & Accounting</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Enterprise Resource Planning</p>
          </div>
        </div>

        <!-- 5 Main Tabs -->
        <div class="flex gap-2 bg-slate-50 p-2 rounded-2xl border border-slate-100 overflow-x-auto w-full md:w-auto">
          <button v-for="tab in mainTabs" :key="tab" @click="currentMainTab = tab"
            class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap"
            :class="currentMainTab === tab ? 'bg-white text-indigo-600 shadow-sm border border-slate-200/50' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-100'">
            {{ tab }}
          </button>
        </div>
      </div>
      <!-- ========================================== -->
      <!-- TAB CONTENT: OVERVIEW (EXECUTIVE DASHBOARD)-->
      <!-- ========================================== -->
      <div v-if="currentMainTab === 'overview'" class="animate-in fade-in duration-700 space-y-6">
        
        <!-- ROW 1: 4 KARTU METRIK UTAMA -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          
          <!-- Card 1: Total Revenue -->
          <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-emerald-200 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
            <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Total Pendapatan</p>
            <h3 class="text-2xl font-black tracking-tighter">{{ formatCurrency(overviewStats.total_revenue) }}</h3>
            <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full">
              <i class="fas fa-arrow-trend-up"></i> Uang Masuk
            </div>
            <i class="fas fa-wallet absolute right-8 bottom-8 text-5xl opacity-20"></i>
          </div>

          <!-- Card 2: Total Expense -->
          <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-rose-200 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
            <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Total Pengeluaran</p>
            <h3 class="text-2xl font-black tracking-tighter">{{ formatCurrency(overviewStats.total_expense) }}</h3>
            <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full">
              <i class="fas fa-arrow-trend-down"></i> Operasional
            </div>
            <i class="fas fa-file-invoice-dollar absolute right-8 bottom-8 text-5xl opacity-20"></i>
          </div>

          <!-- Card 3: Receivables (Piutang) -->
          <div class="bg-gradient-to-br from-amber-400 to-amber-500 rounded-[2.5rem] p-8 text-white shadow-xl shadow-amber-200 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
            <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Piutang (Belum Cair)</p>
            <h3 class="text-2xl font-black tracking-tighter">{{ formatCurrency(overviewStats.total_receivable) }}</h3>
            <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full">
              <i class="fas fa-clock"></i> Menunggu
            </div>
            <i class="fas fa-hand-holding-usd absolute right-8 bottom-8 text-5xl opacity-20"></i>
          </div>

          <!-- Card 4: Net Profit -->
          <div class="bg-gradient-to-br from-indigo-600 to-slate-900 rounded-[2.5rem] p-8 text-white shadow-xl shadow-indigo-200 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
            <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Net Profit Group</p>
            <h3 class="text-2xl font-black tracking-tighter" :class="overviewStats.net_profit < 0 ? 'text-rose-300' : ''">
              {{ formatCurrency(overviewStats.net_profit) }}
            </h3>
            <div class="mt-4 flex items-center gap-2 text-[10px] font-bold bg-white/20 w-max px-3 py-1 rounded-full">
              <i class="fas fa-scale-balanced"></i> {{ overviewStats.active_projects_count }} Proyek Aktif
            </div>
            <i class="fas fa-vault absolute right-8 bottom-8 text-5xl opacity-20"></i>
          </div>
        </div>

        <!-- ROW 2: CHART & AKTIVITAS -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          
          <!-- KIRI: Native CSS Bar Chart (Kinerja per PT) -->
          <div class="lg:col-span-8 bg-white rounded-[3rem] p-8 shadow-sm border border-slate-200">
            <div class="flex justify-between items-center mb-8">
              <div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Kinerja Entitas (PT)</h3>
                <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Perbandingan Pendapatan & Pengeluaran</p>
              </div>
              <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center">
                <i class="fas fa-chart-bar"></i>
              </div>
            </div>

            <!-- Area Grafik Batang CSS -->
            <div class="space-y-6">
              <div v-if="overviewStats.pt_performance.length === 0" class="py-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">
                Belum ada data kinerja entitas
              </div>

              <div v-for="pt in overviewStats.pt_performance" :key="pt.id" class="space-y-2">
                <div class="flex justify-between items-end">
                  <span class="text-[11px] font-black text-slate-700 uppercase tracking-tight">{{ pt.name }}</span>
                  <span class="text-[10px] font-black text-emerald-600">{{ pt.margin }}% Margin</span>
                </div>
                
                <!-- Bar Container -->
                <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex">
                  <!-- Revenue Bar (Hijau) -->
                  <div class="h-full bg-emerald-500 transition-all duration-1000" 
                       :style="{ width: `${(pt.revenue / maxPtRevenue) * 100}%` }">
                  </div>
                  <!-- Expense overlap marker (Merah) -->
                  <div v-if="pt.expense > 0" class="h-full bg-rose-500 transition-all duration-1000 opacity-80" 
                       :style="{ width: `${(pt.expense / maxPtRevenue) * 100}%`, marginLeft: `-${(pt.expense / maxPtRevenue) * 100}%` }">
                  </div>
                </div>
                
                <div class="flex justify-between text-[8px] font-black uppercase text-slate-400">
                  <span>Masuk: {{ formatCurrency(pt.revenue) }}</span>
                  <span>Keluar: {{ formatCurrency(pt.expense) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- KANAN: Aktivitas Transaksi Terbaru -->
          <div class="lg:col-span-4 bg-white rounded-[3rem] p-8 shadow-sm border border-slate-200">
            <div class="flex justify-between items-center mb-8">
              <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Aktivitas Terkini</h3>
              <button @click="currentMainTab = 'transaksi'" class="text-[9px] font-black text-indigo-500 uppercase hover:underline">Lihat Semua</button>
            </div>

            <div class="space-y-4">
              <div v-if="recentTransactions.length === 0" class="py-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">
                Belum ada transaksi
              </div>

              <!-- List Transaksi -->
              <div v-for="trx in recentTransactions" :key="trx.id" class="flex items-center gap-4 p-3 rounded-2xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100 cursor-pointer">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm"
                     :class="trx.type === 'inflow' ? 'bg-emerald-50 text-emerald-500' : 'bg-rose-50 text-rose-500'">
                  <i class="fas" :class="trx.type === 'inflow' ? 'fa-arrow-down' : 'fa-arrow-up'"></i>
                </div>
                <div class="flex-1">
                  <p class="text-[10px] font-black text-slate-700 uppercase truncate max-w-[120px]">{{ trx.description || trx.project_name || 'General' }}</p>
                  <p class="text-[8px] font-bold text-slate-400 mt-0.5">{{ trx.transaction_date }}</p>
                </div>
                <div class="text-right">
                  <p class="text-[11px] font-black italic" :class="trx.type === 'inflow' ? 'text-emerald-600' : 'text-rose-600'">
                    {{ formatCurrency(trx.amount) }}
                  </p>
                  <span class="text-[8px] font-bold px-2 py-0.5 rounded uppercase" :class="trx.status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ trx.status }}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
         <!-- TAB CONTENT: TRANSAKSI           -->
      <div v-if="currentMainTab === 'transaksi'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
        
        <!-- SIDEBAR NAVIGATION (Kiri) -->
        <div class="lg:col-span-3 space-y-6">
          <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Main Filters</h3>
            <div class="space-y-2">
              <button v-for="nav in transactionNavs" :key="nav.id" @click="activeNav = nav.id"
                class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all text-left group"
                :class="activeNav === nav.id ? 'bg-indigo-50 border border-indigo-100' : 'hover:bg-slate-50 border border-transparent'">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors"
                  :class="activeNav === nav.id ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-slate-600'">
                  <i class="fas" :class="nav.icon"></i>
                </div>
                <span class="text-xs font-black uppercase tracking-tight" :class="activeNav === nav.id ? 'text-indigo-600' : 'text-slate-500'">
                  {{ nav.label }}
                </span>
              </button>
            </div>

            <!-- LABELS DARI SETUP -->
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-8 mb-4 ml-2">Tags / Labels</h3>
            <div class="flex flex-wrap gap-2 px-2">
              <button v-for="label in dbLabels" :key="label.id" @click="activeNav = label.id"
                class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border transition-all"
                :class="[label.color, activeNav === label.id ? 'ring-2 ring-offset-2 ring-slate-300' : 'opacity-70 hover:opacity-100']">
                # {{ label.name }}
              </button>
            </div>
          </div>
        </div>

        <!-- MAIN CONTENT TABLE (Kanan) -->
        <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px]">
          
          <!-- Top Bar: Project Filter & Add Button -->
          <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-4 w-full md:w-auto">
              <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Filter Project:</span>
              <select v-model="selectedProject" class="bg-slate-50 border border-slate-100 text-slate-700 text-[10px] font-bold uppercase py-3 px-5 rounded-xl outline-none focus:ring-2 ring-indigo-100 cursor-pointer min-w-[250px]">
                <option value="all">-- Semua Project --</option>
                <!-- PERUBAHAN: dummyProjects jadi dbProjects dan pj.name jadi pj.project_title -->
                <option v-for="pj in dbProjects" :key="pj.id" :value="pj.id">{{ pj.project_title }}</option>
              </select>
            </div>

            <button @click="showAddModal = true" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-3">
              <i class="fas fa-plus"></i> Buat Transaksi
            </button>
          </div>

          <!-- Table Area -->
          <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col">
            <div class="overflow-x-auto flex-1">
              <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                  <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                    <th class="p-6">Date & Ref</th>
                    <th class="p-6">Project & Details</th>
                    <th class="p-6">COA & Method</th>
                    <th class="p-6 text-right">Amount (In/Out)</th>
                    <th class="p-6 text-center">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  
                  <!-- Peringatan Jika Data Kosong -->
                  <tr v-if="transactions.length === 0">
                    <td colspan="5" class="p-12 text-center text-slate-300 text-[10px] font-bold uppercase tracking-widest">
                      Tidak ada transaksi ditemukan
                    </td>
                  </tr>

                  <tr v-for="trx in transactions" :key="trx.id" class="hover:bg-slate-50/50 transition-colors group cursor-pointer">
                    <td class="p-6">
                      <!-- PERUBAHAN: trx.date -> trx.transaction_date, trx.id -> trx.transaction_number -->
                      <p class="text-[11px] font-black text-slate-700">{{ trx.transaction_date }}</p>
                      <p class="text-[9px] font-bold text-slate-400 mt-1 uppercase">{{ trx.transaction_number }}</p>
                    </td>
                    <td class="p-6">
                      <!-- PERUBAHAN: trx.project -> trx.project_name, trx.desc -> trx.description -->
                      <p class="text-[11px] font-black text-indigo-600 uppercase tracking-tight">{{ trx.project_name || 'Non-Project / General' }}</p>
                      <p class="text-[10px] font-bold text-slate-500 mt-1">{{ trx.description || '-' }}</p>
                    </td>
                    <td class="p-6">
                      <!-- PERUBAHAN: Menampilkan Code COA + Nama COA hasil Join API -->
                      <p class="text-[10px] font-bold text-slate-800 uppercase">{{ trx.coa_name ? `[${trx.coa_code}] ${trx.coa_name}` : '-' }}</p>
                      <span class="inline-block mt-1 px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[8px] font-black uppercase border border-slate-200">
                        Via {{ trx.method }}
                      </span>
                    </td>
                    <td class="p-6 text-right">
                      <span class="text-sm font-black italic" :class="trx.type === 'inflow' ? 'text-emerald-500' : 'text-rose-500'">
                        {{ trx.type === 'inflow' ? '+' : '-' }} {{ formatCurrency(trx.amount) }}
                      </span>
                    </td>
                    <td class="p-6 text-center">
                      <span class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border"
                        :class="trx.status === 'Approved' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : (trx.status === 'Pending' ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-rose-50 text-rose-600 border-rose-100')">
                        {{ trx.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="p-6 border-t border-slate-100 bg-slate-50 flex justify-between items-center">
              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Showing Page {{ currentPage }} of {{ totalPages }}</p>
              <div class="flex items-center gap-2">
                <button class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 flex items-center justify-center shadow-sm"><i class="fas fa-chevron-left text-[10px]"></i></button>
                <div class="flex items-center gap-1">
                  <button class="w-8 h-8 rounded-xl bg-indigo-600 text-white text-[10px] font-black shadow-sm">1</button>
                  <button class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 text-[10px] font-black shadow-sm">2</button>
                </div>
                <button class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 flex items-center justify-center shadow-sm"><i class="fas fa-chevron-right text-[10px]"></i></button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- ========================================== -->
      <!-- TAB CONTENT: ACCOUNTING (COA MASTER)       -->
      <!-- ========================================== -->
      <div v-if="currentMainTab === 'accounting'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
        
        <!-- SIDEBAR NAVIGATION (Kiri) -->
        <div class="lg:col-span-3 space-y-6">
          
          <!-- Filter 1: Entitas Bisnis -->
          <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Business Entities</h3>
            <div class="space-y-2">
              <button @click="activeAccEntity = 'all'" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left" :class="activeAccEntity === 'all' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                <i class="fas fa-globe text-sm w-4"></i> <span class="text-[11px] font-black uppercase">Consolidated (All)</span>
              </button>
              <button @click="activeAccEntity = 'personal'" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left" :class="activeAccEntity === 'personal' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                <i class="fas fa-user-tie text-sm w-4"></i> <span class="text-[11px] font-black uppercase">Personal / General</span>
              </button>
              <!-- Looping Daftar PT -->
              <button v-for="pt in dbCompanies" :key="pt.id" @click="activeAccEntity = pt.id" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left" :class="activeAccEntity === pt.id ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                <i class="fas fa-building text-sm w-4"></i> <span class="text-[11px] font-black uppercase truncate">{{ pt.name }}</span>
              </button>
            </div>
          </div>

          <!-- Filter 2: Kategori COA -->
          <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Account Categories</h3>
            <div class="space-y-2">
              <button v-for="cat in coaCategories" :key="cat.id" @click="activeAccCategory = cat.id" class="w-full flex items-center justify-between px-5 py-3 rounded-2xl transition-all text-left group" :class="activeAccCategory === cat.id ? 'bg-slate-900 border border-slate-800' : 'hover:bg-slate-50 border border-transparent'">
                <div class="flex items-center gap-4">
                  <i class="fas text-sm" :class="[cat.icon, activeAccCategory === cat.id ? 'text-white' : cat.color]"></i>
                  <span class="text-[10px] font-black uppercase tracking-tight" :class="activeAccCategory === cat.id ? 'text-white' : 'text-slate-500'">{{ cat.label }}</span>
                </div>
              </button>
            </div>
          </div>

        </div>

        <!-- MAIN CONTENT TABLE (Kanan) -->
        <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px]">
          
          <!-- Top Bar: Search & Add Button -->
          <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="relative w-full md:w-96">
              <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
              <input v-model="searchCOA" type="text" placeholder="Cari Kode atau Nama Akun..." class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-[11px] font-black outline-none focus:ring-2 ring-indigo-100 uppercase tracking-widest text-slate-700">
            </div>

            <button @click="openCoaModal()" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-3">
              <i class="fas fa-plus"></i> Tambah COA
            </button>
          </div>

          <!-- Table Area -->
          <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col">
            <div class="overflow-x-auto flex-1 p-2">
              <table class="w-full text-left">
                <thead class="bg-slate-50 rounded-t-[2.5rem]">
                  <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                    <th class="p-6 rounded-tl-[2rem]">Account Code & Name</th>
                    <th class="p-6">Category Type</th>
                    <th class="p-6">Affiliated Entity</th>
                    <th class="p-6 text-right rounded-tr-[2rem]">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-if="filteredCOAs.length === 0">
                    <td colspan="4" class="p-20 text-center text-slate-300">
                      <i class="fas fa-folder-open text-4xl mb-4"></i>
                      <p class="text-[10px] font-bold uppercase tracking-widest">Tidak ada akun COA ditemukan</p>
                    </td>
                  </tr>

                  <tr v-for="coa in filteredCOAs" :key="coa.id" class="hover:bg-slate-50/50 transition-colors group">
                    <td class="p-6">
                      <div class="flex items-center gap-4">
                        <div class="px-3 py-1.5 bg-indigo-50 border border-indigo-100 rounded-lg text-[10px] font-black text-indigo-600">
                          {{ coa.code }}
                        </div>
                        <p class="text-xs font-black text-slate-700 uppercase">{{ coa.name }}</p>
                      </div>
                    </td>
                    <td class="p-6">
                      <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                        <i class="fas fa-circle text-[8px] mr-1" 
                           :class="{
                             'text-emerald-500': coa.category === 'Asset',
                             'text-rose-500': coa.category === 'Liability' || coa.category === 'Expense',
                             'text-indigo-500': coa.category === 'Equity',
                             'text-emerald-400': coa.category === 'Revenue'
                           }"></i>
                        {{ coa.category }}
                      </span>
                    </td>
                    <td class="p-6">
                      <span v-if="coa.pt_id" class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 rounded-full border border-slate-200 text-[9px] font-black text-slate-600 uppercase tracking-widest">
                        <i class="fas fa-building text-slate-400"></i> PT terikat
                      </span>
                      <span v-else class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 rounded-full border border-indigo-100 text-[9px] font-black text-indigo-500 uppercase tracking-widest">
                        <i class="fas fa-user-tie"></i> Personal / General
                      </span>
                    </td>
                    <td class="p-6 text-right">
                      <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="openCoaModal(coa)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 flex items-center justify-center shadow-sm transition-all"><i class="fas fa-edit text-[10px]"></i></button>
                        <button @click="handleDeleteCOA(coa.id)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 flex items-center justify-center shadow-sm transition-all"><i class="fas fa-trash text-[10px]"></i></button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <!-- ========================================== -->
      <!-- TAB CONTENT: BANKING (ACCOUNTS MASTER)     -->
      <!-- ========================================== -->
      <div v-if="currentMainTab === 'banking'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
        
        <!-- SIDEBAR NAVIGATION (Kiri) -->
        <div class="lg:col-span-3 space-y-6">
          <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Account Ownership</h3>
            <div class="space-y-2">
              <button @click="activeBankEntity = 'all'" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left" :class="activeBankEntity === 'all' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                <i class="fas fa-layer-group text-sm w-4"></i> <span class="text-[11px] font-black uppercase">All Accounts</span>
              </button>
              <button @click="activeBankEntity = 'personal'" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left" :class="activeBankEntity === 'personal' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                <i class="fas fa-user-circle text-sm w-4"></i> <span class="text-[11px] font-black uppercase">Personal / Staff</span>
              </button>
              <button v-for="pt in dbCompanies" :key="pt.id" @click="activeBankEntity = pt.id" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left" :class="activeBankEntity === pt.id ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                <i class="fas fa-building text-sm w-4"></i> <span class="text-[11px] font-black uppercase truncate">{{ pt.name }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- MAIN CONTENT TABLE (Kanan) -->
        <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px]">
          
          <!-- Top Bar: Search & Add Button -->
          <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="relative w-full md:w-96">
              <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
              <input v-model="searchBank" type="text" placeholder="Cari Nama Bank, Pemilik, atau No. Rek..." class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-[11px] font-black outline-none focus:ring-2 ring-indigo-100 uppercase tracking-widest text-slate-700">
            </div>

            <button @click="openBankModal()" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-3">
              <i class="fas fa-plus"></i> Tambah Rekening
            </button>
          </div>

          <!-- Table Area -->
          <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col">
            <div class="overflow-x-auto flex-1 p-2">
              <table class="w-full text-left">
                <thead class="bg-slate-50 rounded-t-[2.5rem]">
                  <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                    <th class="p-6 rounded-tl-[2rem]">Bank & Branch</th>
                    <th class="p-6">Account Details</th>
                    <th class="p-6">Affiliation</th>
                    <th class="p-6 text-right rounded-tr-[2rem]">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-if="filteredBanks.length === 0">
                    <td colspan="4" class="p-20 text-center text-slate-300">
                      <i class="fas fa-university text-4xl mb-4"></i>
                      <p class="text-[10px] font-bold uppercase tracking-widest">Tidak ada rekening ditemukan</p>
                    </td>
                  </tr>

                  <tr v-for="bank in filteredBanks" :key="bank.id" class="hover:bg-slate-50/50 transition-colors group">
                    <td class="p-6">
                      <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-sm">
                          <i class="fas fa-university"></i>
                        </div>
                        <div>
                          <p class="text-xs font-black text-slate-800 uppercase">{{ bank.bank_name }}</p>
                          <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Cab. {{ bank.branch_office || 'Pusat/Online' }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="p-6">
                      <p class="text-[11px] font-black text-slate-700 tracking-wider">{{ bank.account_number }}</p>
                      <p class="text-[10px] font-bold text-slate-500 uppercase mt-1">A.N: {{ bank.account_name }}</p>
                    </td>
                    <td class="p-6">
                      <span v-if="bank.pt_id" class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 rounded-full border border-slate-200 text-[9px] font-black text-slate-600 uppercase tracking-widest">
                        <i class="fas fa-building text-slate-400"></i> Corporate
                      </span>
                      <span v-else class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 rounded-full border border-indigo-100 text-[9px] font-black text-indigo-500 uppercase tracking-widest">
                        <i class="fas fa-user-tie"></i> Personal
                      </span>
                    </td>
                    <td class="p-6 text-right">
                      <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="openBankModal(bank)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 flex items-center justify-center shadow-sm transition-all"><i class="fas fa-edit text-[10px]"></i></button>
                        <button @click="handleDeleteBank(bank.id)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 flex items-center justify-center shadow-sm transition-all"><i class="fas fa-trash text-[10px]"></i></button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <!-- ========================================== -->
      <!-- TAB CONTENT: SETUP (SYSTEM CONFIGURATION)  -->
      <!-- ========================================== -->
      <div v-if="currentMainTab === 'setup'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
        
        <!-- SIDEBAR NAVIGATION (Kiri) -->
        <div class="lg:col-span-3 space-y-6">
          <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">System Preferences</h3>
            <div class="space-y-2">
              <button @click="activeSetupNav = 'labels'" class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all text-left" :class="activeSetupNav === 'labels' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                <i class="fas fa-tags text-sm w-4"></i> <span class="text-[11px] font-black uppercase tracking-tight">Transaction Labels</span>
              </button>
              <button @click="activeSetupNav = 'nav_config'" class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all text-left" :class="activeSetupNav === 'nav_config' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
                <i class="fas fa-bars-staggered text-sm w-4"></i> <span class="text-[11px] font-black uppercase tracking-tight">Sidebar Navigation</span>
              </button>
              <button class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all text-left hover:bg-slate-50 border border-transparent text-slate-500 opacity-50 cursor-not-allowed">
                <i class="fas fa-sliders-h text-sm w-4"></i> <span class="text-[11px] font-black uppercase tracking-tight">General Settings</span>
              </button>
            </div>
          </div>
        </div>

        <!-- MAIN CONTENT TABLE (Kanan) -->
        <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px]">
          
          <div v-if="activeSetupNav === 'labels'" class="flex-1 flex flex-col gap-6">
            <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex justify-between items-center">
              <div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Master Data: Labels</h3>
                <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Konfigurasi tag untuk filtering transaksi</p>
              </div>
              <button @click="openLabelModal()" class="bg-indigo-600 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center gap-3">
                <i class="fas fa-plus"></i> Buat Label
              </button>
            </div>

            <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden p-2">
              <table class="w-full text-left">
                <thead class="bg-slate-50 rounded-t-[2.5rem]">
                  <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                    <th class="p-6 rounded-tl-[2rem]">Label Name</th>
                    <th class="p-6">Visual Preview</th>
                    <th class="p-6 text-right rounded-tr-[2rem]">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-if="dbLabels.length === 0">
                    <td colspan="3" class="p-12 text-center text-slate-400 text-[10px] font-bold uppercase">Belum ada label dikonfigurasi.</td>
                  </tr>
                  <tr v-for="lbl in dbLabels" :key="lbl.id" class="hover:bg-slate-50/50 transition-colors group">
                    <td class="p-6 text-xs font-black text-slate-800 uppercase">{{ lbl.name }}</td>
                    <td class="p-6">
                      <span class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border border-white/20 shadow-sm" :class="lbl.color">
                        # {{ lbl.name }}
                      </span>
                    </td>
                    <td class="p-6 text-right">
                      <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="openLabelModal(lbl)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 flex items-center justify-center shadow-sm"><i class="fas fa-edit text-[10px]"></i></button>
                        <button @click="handleDeleteLabel(lbl.id)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-rose-600 flex items-center justify-center shadow-sm"><i class="fas fa-trash text-[10px]"></i></button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Placeholder Nav Config -->
          <div v-else class="flex-1 bg-white rounded-[3rem] border border-slate-200 flex flex-col items-center justify-center text-slate-300 opacity-60">
            <i class="fas fa-tools text-6xl mb-6"></i>
            <p class="text-[11px] font-black uppercase tracking-[0.3em]">Menu Navigation Configuration</p>
            <p class="text-[9px] font-bold mt-2 uppercase">Coming Soon in Next Update</p>
          </div>

        </div>
      </div>

      <!-- batas div '' -->
    </div>
    
      <!-- ========================================== -->
      <!-- MODAL FORM ADD TRANSAKSI                   -->
      <!-- ========================================== -->
      <div v-if="showAddModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddModal = false"></div>
        
        <div class="bg-white rounded-[3rem] w-full max-w-4xl max-h-[90vh] overflow-y-auto relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
          
          <!-- Modal Header -->
          <div class="sticky top-0 bg-white/80 backdrop-blur-md p-8 border-b border-slate-100 flex justify-between items-center z-20">
            <div>
              <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">Draft Transaksi Baru</h3>
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pencatatan Ledger & Kas</p>
            </div>
            <button @click="showAddModal = false" class="w-10 h-10 rounded-2xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all flex items-center justify-center">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Modal Body Form -->
          <div class="p-8 space-y-8">
            
            <!-- SECTION 1: Tipe & Detail Dasar -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="space-y-4">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Tipe Arus Kas</label>
                <div class="flex gap-4">
                  <button @click="txForm.type = 'inflow'" class="flex-1 py-4 rounded-2xl border-2 text-[11px] font-black uppercase tracking-widest transition-all"
                    :class="txForm.type === 'inflow' ? 'border-emerald-500 bg-emerald-50 text-emerald-600' : 'border-slate-100 bg-white text-slate-400 hover:bg-slate-50'">
                    <i class="fas fa-arrow-down mr-2"></i> Income
                  </button>
                  <button @click="txForm.type = 'outflow'" class="flex-1 py-4 rounded-2xl border-2 text-[11px] font-black uppercase tracking-widest transition-all"
                    :class="txForm.type === 'outflow' ? 'border-rose-500 bg-rose-50 text-rose-600' : 'border-slate-100 bg-white text-slate-400 hover:bg-slate-50'">
                    <i class="fas fa-arrow-up mr-2"></i> Expense
                  </button>
                </div>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Tanggal Transaksi</label>
                  <input v-model="txForm.date" type="date" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 text-slate-700">
                </div>
                <div class="space-y-2">
                  <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nomor Referensi</label>
                  <input v-model="txForm.ref_number" type="text" placeholder="Auto / Manual" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 placeholder:text-slate-300">
                </div>
              </div>
            </div>

            <hr class="border-slate-100">

            <!-- SECTION 2: Alokasi Proyek & Akunting -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Relasi Project</label>
                <select v-model="txForm.project_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
                  <option value="" disabled>-- Pilih Project (Optional) --</option>
                  <!-- PERUBAHAN KE DB LOKAL: dbProjects & project_title -->
                  <option v-for="pj in dbProjects" :key="pj.id" :value="pj.id">{{ pj.project_title }}</option>
                </select>
              </div>
              
              <div class="space-y-2">
                <div class="flex justify-between items-center ml-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Akun COA (Chart of Account)</label>
                  <span class="text-[8px] font-bold text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded">Multi-PT Support</span>
                </div>
                <select v-model="txForm.coa_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
                  <option value="" disabled>-- Pilih Klasifikasi Akun --</option>
                  <!-- PERUBAHAN KE DB LOKAL: dbCOAs -->
                  <option v-for="coa in dbCOAs" :key="coa.id" :value="coa.id">[{{ coa.code }}] {{ coa.name }}</option>
                </select>
              </div>
            </div>

            <!-- SECTION 3: Nominal & Metode Banking -->
            <div class="bg-indigo-50/50 border border-indigo-100 p-6 rounded-[2rem] space-y-6">
              <div class="space-y-2">
                <label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Nominal Transaksi (IDR)</label>
                <div class="relative">
                  <span class="absolute left-6 top-1/2 -translate-y-1/2 text-lg font-black text-indigo-300">Rp</span>
                  <input v-model="txForm.amount" type="number" class="w-full bg-white border border-indigo-100 rounded-2xl pl-16 pr-5 py-4 text-xl font-black text-indigo-900 outline-none focus:ring-2 ring-indigo-200">
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                  <label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Metode</label>
                  <select v-model="txForm.method" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 cursor-pointer">
                    <option value="transfer">Bank Transfer</option>
                    <option value="cash">Uang Tunai (Cash)</option>
                    <option value="ewallet">E-Wallet</option>
                  </select>
                </div>
                <div class="space-y-2">
                  <label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Dari Bank / Dompet</label>
                  <select v-model="txForm.bank_from" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 cursor-pointer">
                    <option value="">-- Asal Dana --</option>
                    <!-- PERUBAHAN KE DB LOKAL: dbBanks -->
                    <option v-for="bank in dbBanks" :key="bank.id" :value="bank.id">{{ bank.name }}</option>
                  </select>
                </div>
                <div class="space-y-2">
                  <label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Ke Bank / Dompet (Tujuan)</label>
                  <select v-model="txForm.bank_to" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 cursor-pointer">
                    <option value="">-- Tujuan Dana --</option>
                    <!-- PERUBAHAN KE DB LOKAL: dbBanks -->
                    <option v-for="bank in dbBanks" :key="bank.id" :value="bank.id">{{ bank.name }}</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- SECTION 4: Detail & Lampiran -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Keterangan / Catatan</label>
                <textarea v-model="txForm.description" rows="3" placeholder="Tuliskan tujuan atau rincian transaksi..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 resize-none"></textarea>
              </div>
              
              <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Bukti Dokumen / Invoice</label>
                <!-- PERBAIKAN: Menambahkan elemen <input type="file"> yang transparan namun bisa diklik -->
                <div class="relative w-full h-[90px] border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400 hover:bg-slate-50 hover:border-indigo-300 transition-all cursor-pointer group overflow-hidden">
                  <input type="file" @change="handleFileUpload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept=".pdf,.png,.jpg,.jpeg">
                  <i class="fas fa-cloud-upload-alt text-xl mb-1 group-hover:text-indigo-500" :class="txForm.attachment ? 'text-indigo-500' : ''"></i>
                  <span class="text-[9px] font-black uppercase tracking-widest truncate max-w-[80%] text-center" :class="txForm.attachment ? 'text-indigo-600' : ''">
                    {{ txForm.attachment ? txForm.attachment.name : 'Klik untuk Upload Dokumen' }}
                  </span>
                </div>
              </div>
            </div>
            
          </div>

          <!-- Modal Footer -->
          <div class="sticky bottom-0 bg-white border-t border-slate-100 p-6 flex justify-end gap-4 rounded-b-[3rem] z-20">
            <button @click="showAddModal = false" class="px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-all">
              Batal
            </button>
            <button @click="handleSaveTransaction" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">
              Submit Transaksi
            </button>
          </div>

        </div>
      </div>
      <!-- ========================================== -->
      <!-- MODAL FORM ADD/EDIT COA                    -->
      <!-- ========================================== -->
      <div v-if="showCoaModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCoaModal = false"></div>
        
        <div class="bg-white rounded-[3rem] w-full max-w-lg relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
          <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-[3rem]">
            <div>
              <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">{{ coaForm.id ? 'Edit Akun COA' : 'Buat Akun COA Baru' }}</h3>
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pengaturan Master Ledger</p>
            </div>
            <button @click="showCoaModal = false" class="w-10 h-10 rounded-2xl bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 hover:border-rose-100 transition-all flex items-center justify-center shadow-sm">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="p-8 space-y-6">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kepemilikan Akun (Entity)</label>
              <select v-model="coaForm.pt_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
                <option value="">-- Personal / General (Berlaku Semua) --</option>
                <option v-for="pt in dbCompanies" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kode Akun</label>
                <input v-model="coaForm.code" type="text" placeholder="Contoh: 1-1001" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase placeholder:text-slate-300">
              </div>
              <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kategori Akun</label>
                <select v-model="coaForm.category" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
                  <option value="Asset">Asset (Harta)</option>
                  <option value="Liability">Liability (Kewajiban)</option>
                  <option value="Equity">Equity (Modal)</option>
                  <option value="Revenue">Revenue (Pendapatan)</option>
                  <option value="Expense">Expense (Beban)</option>
                </select>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nama Akun (Account Name)</label>
              <input v-model="coaForm.name" type="text" placeholder="Contoh: Kas Kecil Proyek A" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase placeholder:text-slate-300">
            </div>
          </div>

          <div class="p-8 border-t border-slate-100 flex justify-end gap-4 bg-slate-50/50 rounded-b-[3rem]">
            <button @click="showCoaModal = false" class="px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-all">Batal</button>
            <button @click="handleSaveCOA" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">
              {{ coaForm.id ? 'Simpan Perubahan' : 'Buat Akun' }}
            </button>
          </div>
        </div>
      </div>
      <!-- ========================================== -->
      <!-- MODAL FORM ADD/EDIT BANKING                -->
      <!-- ========================================== -->
      <div v-if="showBankModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showBankModal = false"></div>
        
        <div class="bg-white rounded-[3rem] w-full max-w-xl relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
          <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-[3rem]">
            <div>
              <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">{{ bankForm.id ? 'Edit Rekening' : 'Tambah Rekening Baru' }}</h3>
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Manajemen E-Wallet & Bank</p>
            </div>
            <button @click="showBankModal = false" class="w-10 h-10 rounded-2xl bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 hover:border-rose-100 transition-all flex items-center justify-center shadow-sm">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="p-8 space-y-6">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kepemilikan (Affiliation)</label>
              <select v-model="bankForm.pt_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
                <option value="">-- Personal / Individu --</option>
                <option v-for="pt in dbCompanies" :key="pt.id" :value="pt.id">Corporate: {{ pt.name }}</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nama Bank / E-Wallet</label>
                <input v-model="bankForm.bank_name" type="text" placeholder="Contoh: BCA, OVO, dll" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase placeholder:text-slate-300">
              </div>
              <div class="space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kantor Cabang (Opsional)</label>
                <input v-model="bankForm.branch_office" type="text" placeholder="Contoh: KCP Depok" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase placeholder:text-slate-300">
              </div>
            </div>

            <div class="bg-indigo-50/50 p-6 rounded-[2rem] border border-indigo-100 space-y-4">
              <div class="space-y-2">
                <label class="text-[9px] font-black text-indigo-500 uppercase tracking-widest ml-1">Nomor Rekening</label>
                <input v-model="bankForm.account_number" type="text" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-sm font-black tracking-widest outline-none focus:ring-2 ring-indigo-200 text-slate-800">
              </div>
              <div class="space-y-2">
                <label class="text-[9px] font-black text-indigo-500 uppercase tracking-widest ml-1">Atas Nama (Sesuai Buku Tabungan)</label>
                <input v-model="bankForm.account_name" type="text" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 text-slate-800">
              </div>
            </div>
          </div>

          <div class="p-8 border-t border-slate-100 flex justify-end gap-4 bg-slate-50/50 rounded-b-[3rem]">
            <button @click="showBankModal = false" class="px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-all">Batal</button>
            <button @click="handleSaveBank" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">
              {{ bankForm.id ? 'Simpan Perubahan' : 'Simpan Rekening' }}
            </button>
          </div>
        </div>
      </div>
      <!-- ========================================== -->
       <!-- ========================================== -->
    <!-- MODAL FORM SETUP LABEL                     -->
    <!-- ========================================== -->
    <div v-if="showLabelModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showLabelModal = false"></div>
      
      <div class="bg-white rounded-[3rem] w-full max-w-sm relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-[3rem]">
          <div>
            <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">{{ labelForm.id ? 'Edit Label' : 'Label Baru' }}</h3>
          </div>
          <button @click="showLabelModal = false" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-rose-500 transition-all flex items-center justify-center">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="p-8 space-y-6">
          <div class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nama Label</label>
            <input v-model="labelForm.name" type="text" placeholder="Contoh: Urgent" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase">
          </div>

          <div class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Warna Label</label>
            <select v-model="labelForm.color" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer">
              <option v-for="c in colorOptions" :key="c.class" :value="c.class">{{ c.name }}</option>
            </select>
          </div>
          
          <!-- Live Preview -->
          <div class="p-6 border-2 border-dashed border-slate-200 rounded-2xl flex items-center justify-center">
            <span class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-sm" :class="labelForm.color">
              # {{ labelForm.name || 'Preview Label' }}
            </span>
          </div>
        </div>

        <div class="p-6 border-t border-slate-100 bg-slate-50/50 rounded-b-[3rem]">
          <button @click="handleSaveLabel" class="w-full bg-indigo-600 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">
            Simpan Konfigurasi
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.animate-in {
  animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
/* Menyembunyikan panah default select agar terlihat lebih clean */
select {
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg fill='black' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
  background-repeat: no-repeat;
  background-position-x: 95%;
  background-position-y: 50%;
}
</style>