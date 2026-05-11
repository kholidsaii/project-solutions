<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../../api/axios';

const formatCurrency = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);

// ==========================================
// 1. GLOBAL STATE & MASTER DATA
// ==========================================
// Menyimpan state filter aktif berdasarkan desain sidebar baru
const activeFilters = ref({
  status: 'all',
  metode: 'all',
  kategori_coa: 'all',
  type: 'all', // income, expense, all
  label: 'all'
});

const transactions = ref<any[]>([]);

const dbProjects = ref<any[]>([]);
const dbCOAs = ref<any[]>([]);
const dbBanks = ref<any[]>([]);
const dbLabels = ref<any[]>([]);
const dbCompanies = ref<any[]>([]);
const dbUsers = ref<any[]>([]); // Tambahan untuk 'Terhubung ke member'

// Opsi Statis berdasarkan permintaan
const statusOptions = ['diajukan', 'disetujui', 'dijadwalkan', 'direvisi', 'dibatalkan', 'selesai'];
const methodOptions = [
  { id: 'cash', label: 'Cash' },
  { id: 'transfer', label: 'Transfer' },
  { id: 'tarik_tunai', label: 'Tarik Tunai ATM/Teller' },
  { id: 'setor_tunai', label: 'Setor Tunai ATM/Teller' },
  { id: 'cek_giro', label: 'Cek Giro' },
  { id: 'qris', label: 'QRIS' }
];

// Opsi Navigasi Sidebar (Accordion / Active Menu control)
const openNavMenu = ref('status'); // Default menu sidebar yang terbuka

// ==========================================
// 2. MODAL & FORM STATE
// ==========================================
const showAddModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const txForm = ref({ 
  type: 'outflow', // default expense
  status: 'diajukan', // default status
  date: new Date().toISOString().split('T')[0], 
  ref_number: '', 
  project_id: '' as any, 
  company_id: '' as any, 
  coa_id: '' as any, 
  method: 'transfer', 
  bank_from: '', 
  bank_to: '', 
  amount: 0, 
  description: '', 
  label_id: '' as any, 
  member_id: '' as any, // Terhubung ke member
  attachment: null as File | null 
});

// Dinamis Form Options
const availableCompaniesForTx = computed(() => {
    if (txForm.value.project_id) {
        const pData = dbProjects.value.find(p => p.id === txForm.value.project_id);
        if (pData && pData.companies) return pData.companies;
        return [];
    }
    return dbCompanies.value;
});

const availableCOAsForTx = computed(() => {
    let filtered = dbCOAs.value;
    if (txForm.value.project_id) {
       filtered = filtered.filter(c => c.project_id === txForm.value.project_id || !c.project_id);
    }
    if (txForm.value.company_id) {
       filtered = filtered.filter(c => c.pt_id === txForm.value.company_id || !c.pt_id);
    }
    return filtered;
});

// Computed Filtering untuk List Transaksi
const filteredTransactions = computed(() => {
  return transactions.value.filter(trx => {
    const matchStatus = activeFilters.value.status === 'all' || trx.status.toLowerCase() === activeFilters.value.status.toLowerCase();
    const matchMethod = activeFilters.value.metode === 'all' || trx.method === activeFilters.value.metode;
    const matchType = activeFilters.value.type === 'all' || trx.type === activeFilters.value.type;
    const matchLabel = activeFilters.value.label === 'all' || trx.label_id == activeFilters.value.label;
    const matchCoa = activeFilters.value.kategori_coa === 'all' || trx.coa_id == activeFilters.value.kategori_coa;
    
    return matchStatus && matchMethod && matchType && matchLabel && matchCoa;
  });
});

// ==========================================
// 3. API ACTIONS
// ==========================================
const fetchMaster = async () => {
  try {
    const [resProj, resCOA, resBank, resLabels, resComp, resUsers] = await Promise.all([
      api.get('/projects'), 
      api.get('/accounting/coas'), 
      api.get('/finance/banks'), 
      api.get('/master-data/labels'), 
      api.get('/companies'),
      api.get('/users') // Pastikan endpoint ini ada untuk mengambil PIC/Member
    ]);
    dbProjects.value = resProj.data.data || resProj.data;
    dbCOAs.value = resCOA.data.data || resCOA.data;
    dbBanks.value = resBank.data.data || resBank.data;
    dbLabels.value = resLabels.data.data || resLabels.data;
    dbCompanies.value = resComp.data.data || resComp.data;
    dbUsers.value = resUsers.data.data || resUsers.data;
  } catch (error) { console.error(error); }
};

const fetchTransactions = async () => {
  try {
    // Di real API, parameter filter bisa dikirim ke backend. 
    // Untuk saat ini kita ambil semua lalu filter di frontend (computed).
    const res = await api.get('/finance/transactions');
    transactions.value = res.data;
  } catch (error) { console.error(error); }
};

const handleFileUpload = (event: any) => { if (event.target.files[0]) txForm.value.attachment = event.target.files[0]; };

const handleSaveTransaction = async () => {
  try {
    const formData = new FormData();
    formData.append('type', String(txForm.value.type)); 
    formData.append('status', String(txForm.value.status)); 
    formData.append('date', String(txForm.value.date)); 
    formData.append('amount', String(txForm.value.amount)); 
    formData.append('coa_id', String(txForm.value.coa_id)); 
    formData.append('method', String(txForm.value.method)); 
    formData.append('description', String(txForm.value.description));
    
    if (txForm.value.ref_number) formData.append('ref_number', String(txForm.value.ref_number));
    if (txForm.value.project_id) formData.append('project_id', String(txForm.value.project_id));
    if (txForm.value.company_id) formData.append('company_id', String(txForm.value.company_id));
    if (txForm.value.bank_from) formData.append('bank_from', String(txForm.value.bank_from));
    if (txForm.value.bank_to) formData.append('bank_to', String(txForm.value.bank_to));
    if (txForm.value.label_id) formData.append('label_id', String(txForm.value.label_id));
    if (txForm.value.member_id) formData.append('member_id', String(txForm.value.member_id));
    if (txForm.value.attachment) formData.append('attachment', txForm.value.attachment);
    
    // NOTE BACKEND: Pastikan di Controller saat menerima status === 'selesai', buatkan entri jurnal akuntansi.
    if (isEditing.value) {
        formData.append('_method', 'PUT'); 
        await api.post(`/finance/transactions/${editingId.value}`, formData, { 
            headers: { 'Content-Type': 'multipart/form-data' } 
        });
        alert("Transaksi berhasil diperbarui!");
    } else {
       await api.post('/finance/transactions', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
       alert("Transaksi berhasil diajukan!");
    }

    showAddModal.value = false; 
    await fetchTransactions();
    resetForm();
  } catch (error: any) { alert(error.response?.data?.error || "Terjadi kesalahan sistem."); }
};

const openEditModal = (trx: any) => {
  isEditing.value = true;
  editingId.value = trx.id;
  txForm.value = {
    type: trx.type,
    status: trx.status || 'diajukan',
    date: trx.transaction_date,
    ref_number: trx.ref_number || '',
    project_id: trx.project_id || '',
    company_id: trx.company_id || '',
    coa_id: trx.coa_id || '',
    method: trx.method || 'transfer',
    bank_from: trx.bank_from || '',
    bank_to: trx.bank_to || '',
    amount: parseFloat(trx.amount),
    description: trx.description || '',
    label_id: trx.label_id || '',
    member_id: trx.member_id || '',
    attachment: null
  };
  showAddModal.value = true;
};

const getFileUrl = (path: string | null | number | undefined): string => {
  if (!path || path === "0" || path === 0) return '';
  if (typeof path === 'string' && (path.startsWith('data:') || path.startsWith('http'))) return path;
  
  // Gunakan fallback localhost:8000 persis seperti di DataProject.vue
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'; 
  const baseUrl = apiUrl.replace('/api', '');
  
  let cleanPath = String(path).startsWith('/') ? String(path).substring(1) : String(path);
  return cleanPath.toLowerCase().startsWith('uploads/') ? `${baseUrl}/${cleanPath}` : `${baseUrl}/uploads/${cleanPath}`;
};

const handleDelete = async (id: number) => {
  if (!confirm("Yakin ingin menghapus data transaksi ini secara permanen?")) return;
  try {
    await api.delete(`/finance/transactions/${id}`);
    alert("Transaksi dihapus!");
    await fetchTransactions();
  } catch (e) { alert("Gagal menghapus data."); }
};

const resetForm = () => {
  isEditing.value = false; editingId.value = null;
  txForm.value = { type: 'outflow', status: 'diajukan', date: new Date().toISOString().split('T')[0], ref_number: '', project_id: '', company_id: '', coa_id: '', method: 'transfer', bank_from: '', bank_to: '', amount: 0, description: '', label_id: '', member_id: '', attachment: null };
};

onMounted(() => { fetchMaster(); fetchTransactions(); });
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 text-center">
          <h3 class="text-sm font-black text-slate-800 tracking-tight">Navigation</h3>
        </div>
        
        <div class="flex flex-col">
          <button @click="openNavMenu = openNavMenu === 'status' ? '' : 'status'" class="px-5 py-3 flex justify-between items-center text-xs font-bold text-slate-700 hover:bg-slate-50 border-b border-slate-100 transition-colors">
            <div class="flex items-center gap-3"><i class="fas fa-folder text-slate-400"></i> Status</div>
            <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'status' ? 'rotate-180' : ''"></i>
          </button>
          <div v-show="openNavMenu === 'status'" class="bg-slate-50 px-5 py-2 space-y-1 border-b border-slate-100">
             <div class="flex items-center gap-2 cursor-pointer p-1 hover:text-indigo-600 text-xs text-slate-600" @click="activeFilters.status = 'all'"><i class="fas fa-circle text-[6px]" :class="activeFilters.status === 'all' ? 'text-indigo-500' : 'text-slate-300'"></i> Semua Status</div>
             <div v-for="st in statusOptions" :key="st" class="flex items-center gap-2 cursor-pointer p-1 hover:text-indigo-600 text-xs text-slate-600 capitalize" @click="activeFilters.status = st">
               <i class="fas fa-circle text-[6px]" :class="activeFilters.status === st ? 'text-indigo-500' : 'text-slate-300'"></i> {{ st }}
             </div>
          </div>

          <button @click="openNavMenu = openNavMenu === 'metode' ? '' : 'metode'" class="px-5 py-3 flex justify-between items-center text-xs font-bold text-slate-700 hover:bg-slate-50 border-b border-slate-100 transition-colors">
            <div class="flex items-center gap-3"><i class="fas fa-folder text-slate-400"></i> Metode</div>
            <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'metode' ? 'rotate-180' : ''"></i>
          </button>
          <div v-show="openNavMenu === 'metode'" class="bg-slate-50 px-5 py-2 space-y-1 border-b border-slate-100">
             <div class="flex items-center gap-2 cursor-pointer p-1 hover:text-indigo-600 text-xs text-slate-600" @click="activeFilters.metode = 'all'"><i class="fas fa-circle text-[6px]" :class="activeFilters.metode === 'all' ? 'text-indigo-500' : 'text-slate-300'"></i> Semua Metode</div>
             <div v-for="mt in methodOptions" :key="mt.id" class="flex items-center gap-2 cursor-pointer p-1 hover:text-indigo-600 text-xs text-slate-600" @click="activeFilters.metode = mt.id">
               <i class="fas fa-circle text-[6px]" :class="activeFilters.metode === mt.id ? 'text-indigo-500' : 'text-slate-300'"></i> {{ mt.label }}
             </div>
          </div>

          <button @click="openNavMenu = openNavMenu === 'kategori' ? '' : 'kategori'" class="px-5 py-3 flex justify-between items-center text-xs font-bold text-slate-700 hover:bg-slate-50 border-b border-slate-100 transition-colors">
            <div class="flex items-center gap-3"><i class="fas fa-folder text-slate-400"></i> Kategory COA</div>
            <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'kategori' ? 'rotate-180' : ''"></i>
          </button>
          <div v-show="openNavMenu === 'kategori'" class="bg-slate-50 px-5 py-2 space-y-1 border-b border-slate-100 max-h-40 overflow-y-auto">
             <div class="flex items-center gap-2 cursor-pointer p-1 hover:text-indigo-600 text-xs text-slate-600" @click="activeFilters.kategori_coa = 'all'"><i class="fas fa-circle text-[6px]" :class="activeFilters.kategori_coa === 'all' ? 'text-indigo-500' : 'text-slate-300'"></i> Semua COA</div>
             <div v-for="coa in dbCOAs" :key="coa.id" class="flex items-center gap-2 cursor-pointer p-1 hover:text-indigo-600 text-xs text-slate-600" @click="activeFilters.kategori_coa = coa.id">
               <i class="fas fa-circle text-[6px]" :class="activeFilters.kategori_coa === coa.id ? 'text-indigo-500' : 'text-slate-300'"></i> [{{ coa.code }}] {{ coa.name }}
             </div>
          </div>

          <button @click="activeFilters.type = activeFilters.type === 'outflow' ? 'all' : 'outflow'" class="px-5 py-3 flex justify-between items-center text-xs font-bold text-slate-700 hover:bg-slate-50 border-b border-slate-100 transition-colors" :class="activeFilters.type === 'outflow' ? 'bg-rose-50 text-rose-600' : ''">
            <div class="flex items-center gap-3"><i class="fas fa-folder" :class="activeFilters.type === 'outflow' ? 'text-rose-500' : 'text-slate-400'"></i> Expanse (Keluar)</div>
          </button>

          <button @click="activeFilters.type = activeFilters.type === 'inflow' ? 'all' : 'inflow'" class="px-5 py-3 flex justify-between items-center text-xs font-bold text-slate-700 hover:bg-slate-50 border-b border-slate-100 transition-colors" :class="activeFilters.type === 'inflow' ? 'bg-emerald-50 text-emerald-600' : ''">
            <div class="flex items-center gap-3"><i class="fas fa-folder" :class="activeFilters.type === 'inflow' ? 'text-emerald-500' : 'text-slate-400'"></i> Income (Masuk)</div>
          </button>

          <button @click="openNavMenu = openNavMenu === 'label' ? '' : 'label'" class="px-5 py-3 flex justify-between items-center text-xs font-bold text-slate-700 hover:bg-slate-50 border-b border-slate-100 transition-colors">
            <div class="flex items-center gap-3"><i class="fas fa-folder text-slate-400"></i> Label</div>
            <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'label' ? 'rotate-180' : ''"></i>
          </button>
          <div v-show="openNavMenu === 'label'" class="bg-slate-50 px-5 py-2 space-y-1">
             <div class="flex items-center gap-2 cursor-pointer p-1 hover:text-indigo-600 text-xs text-slate-600" @click="activeFilters.label = 'all'"><i class="fas fa-circle text-[6px]" :class="activeFilters.label === 'all' ? 'text-indigo-500' : 'text-slate-300'"></i> Semua Label</div>
             <div v-for="lbl in dbLabels" :key="lbl.id" class="flex items-center gap-2 cursor-pointer p-1 hover:text-indigo-600 text-xs text-slate-600" @click="activeFilters.label = lbl.id">
               <i class="fas fa-circle text-[6px]" :class="activeFilters.label === lbl.id ? 'text-indigo-500' : 'text-slate-300'"></i> {{ lbl.name }}
             </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="lg:col-span-9 flex flex-col gap-4 min-h-[600px]">
      
      <div class="bg-white border border-slate-200 rounded-lg p-2 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-2 w-full px-2">
          <div class="bg-indigo-500 text-white px-4 py-2 rounded-md flex items-center gap-2 text-xs font-bold shadow-sm w-40 justify-center">
            <i class="fas fa-home"></i> All Transaksi
          </div>
          <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
            <input type="text" placeholder="Search..." class="w-full pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-md text-xs font-medium outline-none focus:ring-1 ring-indigo-300">
          </div>
          <button class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-md flex items-center gap-2 text-xs font-bold hover:bg-slate-50">
            <i class="fas fa-filter text-indigo-500"></i> Filter
          </button>
          <div class="flex items-center gap-1">
            <button class="bg-indigo-500 text-white w-8 h-8 rounded-md flex items-center justify-center"><i class="fas fa-bars"></i></button>
            <button class="bg-indigo-500 text-white w-8 h-8 rounded-md flex items-center justify-center"><i class="fas fa-th-large"></i></button>
          </div>
          <button @click="resetForm(); showAddModal = true" class="bg-indigo-500 text-white w-8 h-8 rounded-md flex items-center justify-center hover:bg-indigo-600 shadow-md">
            <i class="fas fa-plus"></i>
          </button>
        </div>
      </div>

      <div class="flex-1 space-y-3 mt-2">
        <div v-if="filteredTransactions.length === 0" class="p-10 text-center text-slate-400 border-2 border-dashed rounded-xl">
           <i class="fas fa-folder-open text-4xl mb-3"></i>
           <p class="text-xs font-bold uppercase tracking-widest">Tidak ada transaksi ditemukan</p>
        </div>

        <div v-for="trx in filteredTransactions" :key="trx.id" class="bg-white border border-slate-200 rounded-lg p-4 flex flex-col md:flex-row items-center gap-4 relative overflow-hidden group shadow-sm hover:shadow-md transition-all">
          
          <div class="absolute left-0 top-0 bottom-0 w-2" :class="trx.type === 'inflow' ? 'bg-cyan-400' : 'bg-fuchsia-500'"></div>
          
          <div class="w-16 h-16 rounded-full border-2 border-slate-100 flex items-center justify-center flex-shrink-0 bg-slate-50 ml-2 overflow-hidden shadow-sm">
              <img v-if="trx.project_logo" :src="getFileUrl(trx.project_logo)" alt="Logo Project" class="w-full h-full object-cover">
              
              <i v-else class="fas fa-briefcase text-2xl text-slate-300"></i>
            </div>

          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap gap-2 mb-2">
              <span class="px-3 py-1 rounded-full text-[10px] font-black text-white capitalize shadow-sm" :class="trx.type === 'inflow' ? 'bg-cyan-500' : 'bg-rose-500'">{{ trx.type === 'inflow' ? 'Income' : 'Expense' }}</span>
              <span class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-green-500 uppercase shadow-sm">{{ trx.status }}</span>
              <span class="px-3 py-1 rounded-full text-[10px] font-black text-slate-700 bg-yellow-300 capitalize shadow-sm">{{ trx.method }}</span>
              <span class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-blue-600 uppercase shadow-sm">COA: {{ trx.coa_name || 'N/A' }}</span>
            </div>
            
            <h4 class="text-sm font-black text-slate-800 uppercase tracking-tight truncate">{{ trx.description || 'TRANSAKSI KEUANGAN' }}</h4>
            
            <div class="grid grid-cols-2 text-[10px] font-bold text-slate-500 mt-1">
              <div>Customer / PT: <span class="text-slate-700">{{ trx.company_name || 'Personal' }}</span></div>
              <div>Date: <span class="text-slate-700">{{ trx.transaction_date }}</span></div>
              <div>Project: <span class="text-slate-700">{{ trx.project_name || 'Non-Project' }}</span></div>
              <div>Bank: <span class="text-slate-700">{{ trx.bank_from ? 'From Bank Linked' : 'Manual' }}</span></div>
            </div>
            <p class="text-[9px] text-slate-400 mt-2">Ref ID: {{ trx.transaction_number }} | PIC: {{ dbUsers.find(u => u.id == trx.member_id)?.name || 'Not Assigned' }}</p>
          </div>

          <div class="flex flex-col items-end gap-3 flex-shrink-0">
            <div class="w-24 h-24 rounded-full flex flex-col items-center justify-center shadow-inner border-4 border-white ring-2" :class="trx.type === 'inflow' ? 'bg-green-100 ring-green-300' : 'bg-red-100 ring-red-300'">
               <span class="text-[10px] font-black text-center px-2" :class="trx.type === 'inflow' ? 'text-green-600' : 'text-red-600'">{{ formatCurrency(trx.amount) }}</span>
               <span class="text-[8px] font-bold text-slate-500 uppercase mt-1">Nominal</span>
            </div>
            
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
               <button @click="openEditModal(trx)" class="w-6 h-6 rounded bg-slate-100 text-indigo-600 hover:bg-indigo-500 hover:text-white flex items-center justify-center"><i class="fas fa-edit text-[10px]"></i></button>
               <button @click="handleDelete(trx.id)" class="w-6 h-6 rounded bg-slate-100 text-rose-500 hover:bg-rose-500 hover:text-white flex items-center justify-center"><i class="fas fa-trash text-[10px]"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showAddModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddModal = false"></div>
      <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto relative z-10 shadow-2xl">
        <div class="sticky top-0 bg-white border-b border-slate-100 p-6 flex justify-between items-center z-20">
          <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">{{ isEditing ? 'Edit Transaksi' : 'Buat Transaksi Baru' }}</h3>
          <button @click="showAddModal = false" class="text-slate-400 hover:text-rose-500"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-slate-50 rounded-xl border border-slate-100">
            <div class="space-y-2">
               <label class="text-[10px] font-black text-slate-500 uppercase">Tipe Arus Kas</label>
               <div class="flex gap-2">
                 <button @click="txForm.type = 'inflow'" class="flex-1 py-3 rounded-lg border-2 text-xs font-black uppercase transition-all" :class="txForm.type === 'inflow' ? 'border-emerald-500 bg-emerald-50 text-emerald-600' : 'border-slate-200 bg-white text-slate-400'"><i class="fas fa-arrow-down mr-1"></i> Income</button>
                 <button @click="txForm.type = 'outflow'" class="flex-1 py-3 rounded-lg border-2 text-xs font-black uppercase transition-all" :class="txForm.type === 'outflow' ? 'border-rose-500 bg-rose-50 text-rose-600' : 'border-slate-200 bg-white text-slate-400'"><i class="fas fa-arrow-up mr-1"></i> Expense</button>
               </div>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-500 uppercase">Status Transaksi</label>
              <select v-model="txForm.status" class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 cursor-pointer text-slate-700">
                <option v-for="st in statusOptions" :key="st" :value="st">{{ st }}</option>
              </select>
              <p class="text-[8px] text-amber-600 font-bold">*Status 'Selesai' akan mencatat data ke buku jurnal Accounting.</p>
            </div>
          </div>
          
          <hr class="border-slate-100">
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="space-y-2"><label class="text-[10px] font-black text-slate-500 uppercase">Tanggal</label><input v-model="txForm.date" type="date" class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100"></div>
            <div class="space-y-2"><label class="text-[10px] font-black text-slate-500 uppercase">Nomor Referensi</label><input v-model="txForm.ref_number" type="text" placeholder="Opsional" class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100"></div>
            <div class="space-y-2"><label class="text-[10px] font-black text-slate-500 uppercase">Label Kategori</label><select v-model="txForm.label_id" class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 text-slate-700"><option value="">-- Pilih Label --</option><option v-for="lbl in dbLabels" :key="lbl.id" :value="lbl.id">{{ lbl.name }}</option></select></div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="space-y-2"><label class="text-[10px] font-black text-slate-500 uppercase">Relasi Project</label><select v-model="txForm.project_id" class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 text-slate-700"><option value="">-- Non Project --</option><option v-for="pj in dbProjects" :key="pj.id" :value="pj.id">{{ pj.project_title }}</option></select></div>
            <div class="space-y-2"><label class="text-[10px] font-black text-slate-500 uppercase">Entitas PT</label><select v-model="txForm.company_id" class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 text-slate-700"><option value="">-- Personal --</option><option v-for="comp in availableCompaniesForTx" :key="comp.id" :value="comp.id">{{ comp.name }}</option></select></div>
            <div class="space-y-2"><label class="text-[10px] font-black text-slate-500 uppercase">Akun COA</label><select v-model="txForm.coa_id" class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 text-slate-700"><option value="" disabled>-- Pilih COA --</option><option v-for="coa in availableCOAsForTx" :key="coa.id" :value="coa.id">[{{ coa.code }}] {{ coa.name }}</option></select></div>
          </div>

          <div class="bg-indigo-50/50 border border-indigo-100 p-6 rounded-xl space-y-5">
            <div class="space-y-2">
               <label class="text-[10px] font-black text-indigo-500 uppercase">Nominal Transaksi (IDR)</label>
               <div class="relative"><span class="absolute left-4 top-1/2 -translate-y-1/2 font-black text-indigo-300">Rp</span><input v-model="txForm.amount" type="number" class="w-full bg-white border border-indigo-200 rounded-lg pl-12 pr-4 py-3 text-lg font-black text-indigo-900 outline-none focus:ring-2 ring-indigo-300"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="space-y-2">
                 <label class="text-[10px] font-black text-indigo-500 uppercase">Metode</label>
                 <select v-model="txForm.method" class="w-full bg-white border border-indigo-200 rounded-lg px-3 py-2 text-xs font-bold uppercase outline-none text-slate-700">
                    <option v-for="mt in methodOptions" :key="mt.id" :value="mt.id">{{ mt.label }}</option>
                 </select>
              </div>
              <div class="space-y-2">
                 <label class="text-[10px] font-black text-indigo-500 uppercase">Bank Asal (Dari)</label>
                 <select v-model="txForm.bank_from" class="w-full bg-white border border-indigo-200 rounded-lg px-3 py-2 text-xs font-bold uppercase outline-none text-slate-700">
                    <option value="">-- Kas/Bank Lain --</option>
                    <option v-for="bank in dbBanks" :key="bank.id" :value="bank.id">{{ bank.bank_name }} ({{ bank.account_name }})</option>
                 </select>
              </div>
              <div class="space-y-2">
                 <label class="text-[10px] font-black text-indigo-500 uppercase">Bank Tujuan (Ke)</label>
                 <select v-model="txForm.bank_to" class="w-full bg-white border border-indigo-200 rounded-lg px-3 py-2 text-xs font-bold uppercase outline-none text-slate-700">
                    <option value="">-- Penerima Luar --</option>
                    <option v-for="bank in dbBanks" :key="bank.id" :value="bank.id">{{ bank.bank_name }} ({{ bank.account_name }})</option>
                 </select>
              </div>
              <div class="space-y-2">
                 <label class="text-[10px] font-black text-indigo-500 uppercase">Member / PIC</label>
                 <select v-model="txForm.member_id" class="w-full bg-white border border-indigo-200 rounded-lg px-3 py-2 text-xs font-bold uppercase outline-none text-slate-700">
                    <option value="">-- Pilih PIC --</option>
                    <option v-for="user in dbUsers" :key="user.id" :value="user.id">{{ user.name }}</option>
                 </select>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2"><label class="text-[10px] font-black text-slate-500 uppercase">Keterangan / Judul Transaksi</label><textarea v-model="txForm.description" rows="3" class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 resize-none" placeholder="Cth: Pinjaman Modal ke Pak Bram..."></textarea></div>
            <div class="space-y-2"><label class="text-[10px] font-black text-slate-500 uppercase">Bukti Dokumen</label><div class="relative w-full h-[80px] border-2 border-dashed border-slate-300 rounded-lg flex flex-col items-center justify-center hover:bg-slate-50 cursor-pointer overflow-hidden bg-white"><input type="file" @change="handleFileUpload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept=".pdf,.png,.jpg,.jpeg"><i class="fas fa-cloud-upload-alt text-lg mb-1 text-slate-400" :class="txForm.attachment ? 'text-indigo-500' : ''"></i><span class="text-[9px] font-black text-slate-500 uppercase truncate max-w-[80%]">{{ txForm.attachment ? txForm.attachment.name : 'Upload File / Bukti TF' }}</span></div></div>
          </div>
        </div>
        
        <div class="sticky bottom-0 bg-white border-t border-slate-100 p-6 flex justify-end gap-3 z-20 rounded-b-2xl">
           <button @click="showAddModal = false" class="px-6 py-3 rounded-lg text-xs font-black uppercase text-slate-500 hover:bg-slate-100">Batal</button>
           <button @click="handleSaveTransaction" class="bg-indigo-600 text-white px-8 py-3 rounded-lg text-xs font-black uppercase shadow-lg hover:bg-indigo-700">{{ isEditing ? 'Simpan Perubahan' : 'Submit Transaksi' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
select {
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg fill='%2394a3b8' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
  background-repeat: no-repeat;
  background-position-x: 95%;
  background-position-y: 50%;
}
</style>