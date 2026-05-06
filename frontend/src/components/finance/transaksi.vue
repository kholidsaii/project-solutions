<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import api from '../../api/axios';

const formatCurrency = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);

// ==========================================
// 1. GLOBAL STATE & MASTER DATA
// ==========================================
const activeNav = ref('all');
const selectedProject = ref('all');
// const currentPage = ref(1);
// const totalPages = ref(1);
const transactions = ref<any[]>([]);

const dbProjects = ref<any[]>([]);
const dbCOAs = ref<any[]>([]);
const dbBanks = ref<any[]>([]);
const dbLabels = ref<any[]>([]);
const dbCompanies = ref<any[]>([]);

const sysConfig = JSON.parse(localStorage.getItem('kerjapro_finance_setup') || '{}');
const activeMenus = sysConfig.trx_menus ? sysConfig.trx_menus.filter((m: any) => m.active) : [
  { id: 'pending', label: 'Pending / Diajukan', icon: 'fa-clock' },
  { id: 'approved', label: 'Approved / Sukses', icon: 'fa-check-double' },
  { id: 'income', label: 'Uang Masuk (Income)', icon: 'fa-arrow-down' },
  { id: 'expense', label: 'Uang Keluar (Expense)', icon: 'fa-arrow-up' }
];

const transactionNavs = [
  { id: 'all', label: 'Semua Transaksi', icon: 'fa-list-ul' },
  ...activeMenus
];

// ==========================================
// 2. MODAL & FORM STATE
// ==========================================
const showAddModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const txForm = ref({ 
  type: 'outflow', 
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
  label_id: '', 
  attachment: null as File | null 
});

// === LOGIKA DINAMIS: Filter PT dan COA berdasarkan Project yang dipilih ===
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

// ==========================================
// 3. API ACTIONS
// ==========================================
const fetchMaster = async () => {
  try {
    const [resProj, resCOA, resBank, resLabels, resComp] = await Promise.all([
      api.get('/projects'), api.get('/accounting/coas'), api.get('/finance/banks'), api.get('/master-data/labels'), api.get('/companies')
    ]);
    dbProjects.value = resProj.data.data || resProj.data;
    dbCOAs.value = resCOA.data.data || resCOA.data;
    dbBanks.value = resBank.data.data || resBank.data;
    dbLabels.value = resLabels.data.data || resLabels.data;
    dbCompanies.value = resComp.data.data || resComp.data;
  } catch (error) { console.error(error); }
};

const fetchTransactions = async () => {
  try {
    const params = { 
       project_id: selectedProject.value, 
       status: activeNav.value === 'pending' || activeNav.value === 'approved' ? activeNav.value : 'all', 
       type: activeNav.value === 'income' ? 'inflow' : (activeNav.value === 'expense' ? 'outflow' : 'all') 
    };
    const res = await api.get('/finance/transactions', { params });
    transactions.value = res.data;
  } catch (error) { console.error(error); }
};

const handleFileUpload = (event: any) => { if (event.target.files[0]) txForm.value.attachment = event.target.files[0]; };

// --- FUNGSI SAVE (CREATE & UPDATE) ---
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
    if (txForm.value.company_id) formData.append('company_id', String(txForm.value.company_id));
    if (txForm.value.bank_from) formData.append('bank_from', String(txForm.value.bank_from));
    if (txForm.value.bank_to) formData.append('bank_to', String(txForm.value.bank_to));
    if (txForm.value.attachment) formData.append('attachment', txForm.value.attachment);
    
    if (isEditing.value) {
        formData.append('_method', 'PUT'); // Spoofer tetap diperlukan untuk upload file
        // URL harus sesuai: /api/finance/transactions/{id}
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

// --- FUNGSI EDIT ---
const openEditModal = (trx: any) => {
  isEditing.value = true;
  editingId.value = trx.id;
  txForm.value = {
    type: trx.type,
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
    attachment: null
  };
  showAddModal.value = true;
};
const getFileUrl = (path: string | null | number | undefined) => {
  // Pastikan path valid dan bukan string "0" atau angka 0
  if (!path || path === "0" || path === 0) return ''; // Kembalikan string kosong, bukan null

  const baseUrl = import.meta.env.VITE_API_URL 
    ? import.meta.env.VITE_API_URL.replace('/api', '') 
    : window.location.origin;

  return `${baseUrl}/uploads/${path}`;
};
// --- FUNGSI DELETE ---
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
  txForm.value = { type: 'outflow', date: new Date().toISOString().split('T')[0], ref_number: '', project_id: '', company_id: '', coa_id: '', method: 'transfer', bank_from: '', bank_to: '', amount: 0, description: '', label_id: '', attachment: null };
};

watch([activeNav, selectedProject], fetchTransactions);
onMounted(() => { fetchMaster(); fetchTransactions(); });
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
    <!-- SIDEBAR -->
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Main Filters</h3>
        <div class="space-y-2">
          <button v-for="nav in transactionNavs" :key="nav.id" @click="activeNav = nav.id" class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all text-left group" :class="activeNav === nav.id ? 'bg-indigo-50 border border-indigo-100' : 'hover:bg-slate-50 border border-transparent'">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors" :class="activeNav === nav.id ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-slate-600'"><i class="fas" :class="nav.icon"></i></div>
            <span class="text-xs font-black uppercase tracking-tight" :class="activeNav === nav.id ? 'text-indigo-600' : 'text-slate-500'">{{ nav.label }}</span>
          </button>
        </div>
      </div>
    </div>
    
    <!-- MAIN TABLE -->
    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px]">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4 w-full md:w-auto">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Filter Project:</span>
          <select v-model="selectedProject" class="bg-slate-50 border border-slate-100 text-slate-700 text-[10px] font-bold uppercase py-3 px-5 rounded-xl outline-none focus:ring-2 ring-indigo-100 cursor-pointer min-w-[250px]"><option value="all">-- Semua Project --</option><option v-for="pj in dbProjects" :key="pj.id" :value="pj.id">{{ pj.project_title }}</option></select>
        </div>
        <button @click="resetForm(); showAddModal = true" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-3"><i class="fas fa-plus"></i> Buat Transaksi</button>
      </div>

      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-100"><tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest"><th class="p-6">Date & Ref</th><th class="p-6">Project & Entity</th><th class="p-6">COA & Method</th><th class="p-6 text-right">Amount</th><th class="p-6 text-center">Status</th><th class="p-6 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="trx in transactions" :key="trx.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="p-6"><p class="text-[11px] font-black text-slate-700">{{ trx.transaction_date }}</p><p class="text-[9px] font-bold text-slate-400 mt-1 uppercase">{{ trx.transaction_number }}</p></td>
                <td class="p-6">
                    <p class="text-[11px] font-black text-indigo-600 uppercase">{{ trx.project_name || 'General' }}</p>
                    <p class="text-[9px] font-bold text-slate-400 mt-1 uppercase"><i class="fas fa-building mr-1"></i> {{ trx.company_name || 'Personal' }}</p>
                </td>
                <td class="p-6"><p class="text-[10px] font-bold text-slate-800 uppercase">{{ trx.coa_name ? `[${trx.coa_code}] ${trx.coa_name}` : '-' }}</p><span class="text-[8px] font-black text-slate-400 uppercase">via {{ trx.method }}</span></td>
                <td class="p-6 text-right"><span class="text-sm font-black italic" :class="trx.type === 'inflow' ? 'text-emerald-500' : 'text-rose-500'">{{ trx.type === 'inflow' ? '+' : '-' }} {{ formatCurrency(trx.amount) }}</span></td>
                <td class="p-6 text-center"><span class="px-3 py-1 rounded-lg text-[8px] font-black uppercase border" :class="trx.status === 'Approved' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-amber-50 text-amber-600 border-amber-100'">{{ trx.status }}</span></td>
                <td class="p-6 text-right">
                    <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                        <button @click="openEditModal(trx)" class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white">
                            <i class="fas fa-edit text-xs"></i>
                        </button>
                        <button @click="handleDelete(trx.id)" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white">
                            <i class="fas fa-trash text-xs"></i>
                        </button>

                        <!-- PENGECEKAN KETAT: file hanya muncul jika path bukan "0" -->
                        <template v-if="trx.attachment_path && trx.attachment_path !== '0'">
                            <a :href="getFileUrl(trx.attachment_path)" 
                            target="_blank"
                            class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all inline-flex items-center justify-center"
                            title="Lihat Bukti">
                                <i class="fas fa-paperclip"></i>
                            </a>
                        </template>
                        <span v-else class="text-slate-300 opacity-30 flex items-center justify-center w-8 h-8">
                            <i class="fas fa-times-circle"></i>
                        </span>
                    </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL FORM -->
    <div v-if="showAddModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddModal = false"></div>
      <div class="bg-white rounded-[3rem] w-full max-w-4xl max-h-[90vh] overflow-y-auto relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        <div class="sticky top-0 bg-white/80 backdrop-blur-md p-8 border-b border-slate-100 flex justify-between items-center z-20">
          <div><h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">{{ isEditing ? 'Edit Transaksi' : 'Draft Transaksi Baru' }}</h3></div>
          <button @click="showAddModal = false" class="w-10 h-10 rounded-2xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-8 space-y-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Tipe Arus Kas</label><div class="flex gap-4"><button @click="txForm.type = 'inflow'" class="flex-1 py-4 rounded-2xl border-2 text-[11px] font-black uppercase tracking-widest transition-all" :class="txForm.type === 'inflow' ? 'border-emerald-500 bg-emerald-50 text-emerald-600' : 'border-slate-100 bg-white text-slate-400 hover:bg-slate-50'"><i class="fas fa-arrow-down mr-2"></i> Income</button><button @click="txForm.type = 'outflow'" class="flex-1 py-4 rounded-2xl border-2 text-[11px] font-black uppercase tracking-widest transition-all" :class="txForm.type === 'outflow' ? 'border-rose-500 bg-rose-50 text-rose-600' : 'border-slate-100 bg-white text-slate-400 hover:bg-slate-50'"><i class="fas fa-arrow-up mr-2"></i> Expense</button></div></div>
            <div class="grid grid-cols-2 gap-4"><div class="space-y-2"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Tanggal Transaksi</label><input v-model="txForm.date" type="date" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 text-slate-700"></div><div class="space-y-2"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nomor Referensi</label><input v-model="txForm.ref_number" type="text" placeholder="Auto / Manual" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 placeholder:text-slate-300"></div></div>
          </div>
          
          <hr class="border-slate-100">
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Relasi Project</label>
              <select v-model="txForm.project_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
                <option value="">-- Non Project / General --</option>
                <option v-for="pj in dbProjects" :key="pj.id" :value="pj.id">{{ pj.project_title }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Entitas / PT</label>
              <select v-model="txForm.company_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
                <option value="">-- Personal / General --</option>
                <option v-for="comp in availableCompaniesForTx" :key="comp.id" :value="comp.id">Corporate: {{ comp.name }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Akun COA</label>
              <select v-model="txForm.coa_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700">
                <option value="" disabled>-- Pilih Klasifikasi Akun --</option>
                <option v-for="coa in availableCOAsForTx" :key="coa.id" :value="coa.id">[{{ coa.code }}] {{ coa.name }}</option>
              </select>
            </div>
          </div>

          <div class="bg-indigo-50/50 border border-indigo-100 p-6 rounded-[2rem] space-y-6"><div class="space-y-2"><label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Nominal Transaksi (IDR)</label><div class="relative"><span class="absolute left-6 top-1/2 -translate-y-1/2 text-lg font-black text-indigo-300">Rp</span><input v-model="txForm.amount" type="number" class="w-full bg-white border border-indigo-100 rounded-2xl pl-16 pr-5 py-4 text-xl font-black text-indigo-900 outline-none focus:ring-2 ring-indigo-200"></div></div><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div class="space-y-2"><label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Metode</label><select v-model="txForm.method" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 cursor-pointer"><option value="transfer">Bank Transfer</option><option value="cash">Uang Tunai (Cash)</option><option value="ewallet">E-Wallet</option></select></div><div class="space-y-2"><label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Dari Bank</label><select v-model="txForm.bank_from" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 cursor-pointer"><option value="">-- Asal Dana --</option><option v-for="bank in dbBanks" :key="bank.id" :value="bank.id">{{ bank.bank_name }}</option></select></div><div class="space-y-2"><label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest ml-1">Ke Bank</label><select v-model="txForm.bank_to" class="w-full bg-white border border-indigo-100 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-200 cursor-pointer"><option value="">-- Tujuan Dana --</option><option v-for="bank in dbBanks" :key="bank.id" :value="bank.id">{{ bank.bank_name }}</option></select></div></div></div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-2"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Keterangan</label><textarea v-model="txForm.description" rows="3" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 resize-none"></textarea></div><div class="space-y-2"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Bukti Dokumen</label><div class="relative w-full h-[90px] border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400 hover:bg-slate-50 cursor-pointer group overflow-hidden"><input type="file" @change="handleFileUpload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept=".pdf,.png,.jpg,.jpeg"><i class="fas fa-cloud-upload-alt text-xl mb-1 group-hover:text-indigo-50" :class="txForm.attachment ? 'text-indigo-500' : ''"></i><span class="text-[9px] font-black uppercase tracking-widest truncate max-w-[80%] text-center">{{ txForm.attachment ? txForm.attachment.name : 'Klik untuk Upload Dokumen' }}</span></div></div></div>
        </div>
        <div class="sticky bottom-0 bg-white border-t border-slate-100 p-6 flex justify-end gap-4 rounded-b-[3rem] z-20"><button @click="showAddModal = false" class="px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-all">Batal</button><button @click="handleSaveTransaction" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">{{ isEditing ? 'Simpan Perubahan' : 'Submit Transaksi' }}</button></div>
      </div>
    </div>
  </div>
</template>